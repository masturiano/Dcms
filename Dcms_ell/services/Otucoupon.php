<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Dcms_Service_Otucoupon extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $_config;

    /**
     *
     * @var Dcms_Model_Form
     */
    protected $_couponFormModel;

    /**
     *
     * @var type 
     */
    protected $_username;

    /**
     *
     * @var type 
     */
    public $_baseService;
    protected $_counponDetails;
    protected $_oldCoupon;
    protected $_newCoupon;
    protected $_couponWhereClause;
    protected $_mongoResource;
    protected $_gearmanWorkerStatusCollection;
    protected $_oneTimeUseLimit;
    protected $_gearmanClient;
    protected $_exportSwitch;
    protected $killswitch;
    protected $_expirationType = "onetimeuse";

    /**
     *
     * @param Zend_Config $config
     * @param Dcms_Service_Base $base
     */
    public function __construct(Zend_Config $config, Dcms_Service_Base $base, Zend_Application_Resource_ResourceAbstract $mongoMultiDbResource) {
        $this->_mongoResource = $mongoMultiDbResource;
        $this->_baseService = $base;
        $this->_couponFormModel = new Dcms_Model_CouponValues();
        $this->_config = $config;
        $this->_couponDetails = array();
        $this->_newCoupon = array();
        $this->_oldCoupon = array();
        $this->_timezone = $config->timezone->value;
        $this->_couponWhereClause = array();
        $killSwitch = $config->killswitch;
        $this->_exportSwitch = $killSwitch->export;
        if (php_sapi_name() !== 'cli') {
            $userReg = Zend_Registry::get('user');
            $this->_username = $userReg->getUsername();
            $session = Zend_Registry::get('dcms_switch');
            $this->killswitch = $session->killswitch;
        }
    }

    public function add($request) {
        $return = array();
        if ($request->isPost()) {
            $getPost = $request->getPost();
            $otucouponDetails = $this->_createDetails($getPost);
            $result = $this->_creatOneTimeUseCoupon($otucouponDetails);
            //echo "<pre>", print_r($result);exit; # Name: Del # Date Modify: April 2016
            // exit; # Name: Del # Date Modify: April 2016
            if (isset($result['result'])) {
                $batchName = strtoupper($getPost['name']);
                if (isset($result['result']['job_id']) && $getPost['generate']) {
                    $message = "Generating coupons..";
                    $path = "dcms/coupon/updateotucoupon/name/" . $batchName;
                } else {
                    $message = "OTU Batch has been successfully saved.";
                    $path = "/dcms/coupon/otucoupon/batch_id/" . $batchName;
                }
                $return = $this->_buildResponse(true, $message, $path);
            } else if (isset($result['error'])) {
                $return = $this->_buildResponse(false, $result['error']['message'], "/dcms/coupon/otucoupon");
            } else {
                $return = $this->_buildResponse(false, "Could not generate OTU coupon.", "/dcms/coupon/otucoupon");
            }
        } else {
            $return['setToView'] = $this->setFormValuesCreate();
        }
        return $return; # Name: Del # Date Modify: April 2016
    }

    public function edit($request) {
        $batchName = trim($request->getParam('name'));
        $searchCoupon = $this->getBatchDetails($batchName);
        if (count($searchCoupon) == 0 || !isset($searchCoupon)) {
            return $this->_buildResponse(false, "Batch name not found.", "/dcms/coupon/otubatchlist");
        }
        if($searchCoupon['isGenerate']) {
            $workerStatus = $searchCoupon;
            $jobId = $workerStatus['jobId'];
            $status = $this->getJobDetails($jobId, "current");
            if (isset($status['job_details']['status'])) {
                if ($request->isPost() && ($status['job_details']['status'] == "processing" || $status['job_details']['status'] == "queued")) {
                    $message = "Unable to update batch. Currently generating coupons for {$batchName}.";
                    $path = "dcms/coupon/updateotucoupon/name/" . $batchName;
                    return $this->_buildResponse(false, $message, $path);
                } else if ($status['job_details']['status'] == "processing") {
                    $jobStatus = $this->getCurrentJobStatus($status);
                    $return = array(
                        'setToView' => array(
                            'worker' => array(
                                'message' => $jobStatus['message'],
                                'batch_name' => $batchName,
                                'numerator' => number_format((int) $jobStatus['numerator'], 0, '', ','),
                                'total' => number_format((int) $jobStatus['denominator'], 0, '', ',')
                            )
                        )
                    );
                    return $return;
                }else if ($status['job_details']['status'] == "queued"){
                    $jobStatus = $this->getCurrentJobStatus($status);
                    $return = array(
                        'setToView' => array(
                            'worker' => array(
                                'message' => "Currently Queued",
                                'batch_name' => $batchName,
                            )
                        )
                    );
                    return $return;
                }
            }
        }




        if ($request->isPost()) {
            $getPost = $request->getPost();
            $otucouponDetails = $this->_updateDetails($searchCoupon, $getPost);
            $result = $this->_creatOneTimeUseCoupon($otucouponDetails);
            if (isset($result['result'])) {
                if (isset($result['result']['job_id']) && $getPost['generate']) {
                    $message = "Generating coupons..";
                    $path = "dcms/coupon/updateotucoupon/name/" . $batchName;
                } else {
                    $message = "OTU Batch has been successfully saved.";
                    $path = "/dcms/coupon/otucoupon/batch_id/" . $batchName;
                }
                return $this->_buildResponse(true, $message, $path);
            } else if (isset($result['error'])) {
                $return = $this->_buildResponse(false, $result['error']['message'], "/dcms/coupon/otucoupon");
            } else {
                $return = $this->_buildResponse(false, "Could not generate OTU coupon.", "/dcms/coupon/otucoupon");
            }
        } else {
            $return['setToView'] = $this->setFormValuesUpdate($searchCoupon);
        }
        return $return;
    }

    public function setFormValuesCreate() {
        $return = array();
        $otuCouponForm = new Dcms_Form_Coupon_Addotucoupon(array());
        $uiConfig = new Dcms_Model_UiConfig();

        //GET TYPE
        $type = $this->_couponFormModel->getTypes();
        $otuCouponForm->getElement('type')->addMultiOptions($type);
        //END OF GET TYPE
        //GET CLASS
        $applycouponto = $this->_couponFormModel->getApplyCouponTo();
        $otuCouponForm->getElement('class')->addMultiOptions($applycouponto);
        //END OF GET CLASS
        //GET SITES
        $siteList = $this->_baseService->getSites();
        if (count($siteList) == 0) {
            $siteList[''] = 'No Sites Available';
        }
        $otuCouponForm->getElement('siteId')->addMultiOptions($siteList);

        //END OF GET SITES
        //GET COUPON APPLIES TO
        $couponAppliesTo = $this->_couponFormModel->getApplyDiscountTo();
        $otuCouponForm->getElement('couponAppliesTo')->addMultiOptions($couponAppliesTo);
        //END OF GET COUPON APPLIES TO
        //GET OWNER
        $owner = $this->_baseService->getOwner();
        $return['readowners'] = $owner;
        if (count($owner) == 0) {
            $owner[''] = 'No Owner Available';
        }
        $otuCouponForm->getElement('owner')->addMultiOptions($owner);
        //END OF GET OWNER
        //GET RESTRICTIONS
        $resConf = $uiConfig->getUiConfig(array('$and' => array(array("name" => "default"), array("category" => "restriction_search"))));
        $paramRes = array();
        $paramRes['limitskip'] = isset($resConf['limitskip']) ? $resConf['limitskip'] : array();
        $restrictions = $this->_baseService->getRestrictions("", $paramRes);
        $return['readrestrictions'] = $restrictions;
        $otuCouponForm->getElement('restrictions')->addMultiOptions($restrictions);
        //END OF GET RESTRICTIONS
        //DISPLAY FORM IN /forms/Coupons/Addotucoupon.php
        //switch for appeasement
        if (isset($this->killswitch['appeasement']) && $this->killswitch['appeasement']) {
            $appeasement = $otuCouponForm->getElement('appeasement');
            $appeasement->setLabel("");
            $appeasement->setAttribs(
                    array(
                        'disabled' => false,
                        'style' => "display:none",
            ));
        }
        $return['otucouponaddform'] = $otuCouponForm;
        //END OF DISPLAY FORM IN /forms/Coupons/Addotucoupon.php

        return $return;
    }

    public function setFormValuesUpdate($coupon) {
        $return = array();
        //GET EXISTING OTU COUPON
        $uiConfig = new Dcms_Model_UiConfig();
        $return['readotucoupons'] = $coupon;
        $rec = $coupon;


        $class_arr = $this->_couponFormModel->getApplyCouponTo();
        $class2 = $class_arr[$rec['coupon']['class']];

        $siteList = $this->_baseService->getSiteInfo(array("siteId" => $rec['coupon']['siteId'][0]));
        $return['readsites'] = $siteList;
        $siteName = $siteList['name'];

        $enabledisable_restrictions = false;
        if (isset($rec['restrictions']) && $rec['restrictions'] != "") {
            $enabledisable_restrictions = true;
        }

        $publish = false;
        if ($rec['status'] == "published") {
            $publish = true;
        }
        $return['readowners'] = $this->_baseService->getOwner($rec['coupon']['owner']);
        $ownerName = $return['readowners']['owner'];

        $existing = array(
            'code' => $rec['coupon']['name'],
            'name' => $rec['coupon']['name'],
            'description' => $rec['description'],
            'ceilingAmount' => $rec['coupon']['ceilingAmount'],
            'class' => $class2,
            'siteName' => $siteName,
            'channelCode' => $rec['coupon']['channelCode'][0],
            'couponAppliesTo' => $rec['coupon']['couponAppliesTo'],
            'owner' => $ownerName,
            'appeasement' => $rec['coupon']['appeasement'],
            'startDate' => date('m/d/Y', $rec['expiration']['startDate']['sec']),
            'endDate' => date('m/d/Y', $rec['expiration']['endDate']['sec']),
            'remainingQuantity' => $rec['remaining'],
            'initialQuantity' => $rec['initial'],
            'enabledisable_restrictions' => $enabledisable_restrictions,
            'publish' => $publish,
            'generate' => $rec['isGenerate'],
            'lowerLimit' => $rec['lower_limit'], # Name: Del # Date Modify: April 2015
            'dispense' => $rec['auto_dispense'], # Name: Del # Date Modify: April 2015
            'gated' => $rec['is_gated'], # Name: Del # Date Modify: April 2015
            'batchId' => $rec['batchId'] # Name: Del # Date Modify: April 2015
        );
        //END OF GET EXISTING OTU COUPON

        $otuCouponForm = new Dcms_Form_Coupon_Updateotucoupon(array());
        $otuCouponForm->populateForm($existing);

        //GET TYPE
        $type = $this->_couponFormModel->getTypes();
        $otuCouponForm->getElement('type')->addMultiOptions($type);
        $otuCouponForm->getElement('type')->setValue($rec['coupon']['type']);
        //END OF GET TYPE
        //GET RESTRICTIONS

        $recrestrictions = $this->_baseService->hydraConnect(array(), "template", "read");
        $recrestrictions = $this->_baseService->getRecords($recrestrictions);
        $return['readrestrictions'] = $recrestrictions;
        $recrestrictions = $recrestrictions;
        $trestrictions = count($recrestrictions);

        $resConf = $uiConfig->getUiConfig(array('$and' => array(array("name" => "default"), array("category" => "restriction_search"))));
        $paramRes = array();
        $paramRes['limitskip'] = isset($resConf['limitskip']) ? $resConf['limitskip'] : array();
        $restrictions = $this->_baseService->getRestrictions("", $paramRes);
        $otuCouponForm->getElement('restrictions')->addMultiOptions($restrictions);
        $otuCouponForm->getElement('restrictions')->setValue($rec['templateId']);
        //END OF GET RESTRICTIONS
        //DISPLAY FORM IN /forms/Coupons/Addotucoupon.php
        //switch for appeasement
        if (isset($this->killswitch['appeasement']) && $this->killswitch['appeasement']) {
            $appeasement = $otuCouponForm->getElement('appeasement');
            $appeasement->setLabel("");
            $appeasement->setAttribs(
                    array(
                        'disabled' => false,
                        'style' => "display:none",
            ));
        }
        $return['otucouponupdateform'] = $otuCouponForm;
        //END OF DISPLAY FORM IN /forms/Coupons/Addotucoupon.php

        # Name: Del # Date Modify: April, 2015 #Start
        Zend_Layout::getMvcInstance()->assign('nav', '<a href="'.$siteName.'/redeem/'.$rec['batchId'].'" target="_blank">'.$siteName.'/redeem/'.$rec['batchId'].'</a>');
        # Name: Del # Date Modify: April, 2015 #End

        return $return;
    }

    private function _createDetails($post) {
        try {
            $batchName = strtoupper($post['name']);
            $logType = "newcoupon";
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $host = $_SERVER['SERVER_NAME'];

            $restrictions = ($post['enabledisable_restrictions'] == 1) ? $post['restrictions'] : "";
            $publish = ($post['publish'] == 1) ? true : false;
            $appeasement = isset($post['appeasement'])? (($post['appeasement'] == 1) ? true : false) : false;
            $initialQuantity = $post['initialQuantity'];

            $datalogs = array(
                "code" => $batchName,
                "type" => $logType,
                "transDetails" => "",
                "transData" => array(
                    1 => $ipAddress,
                    2 => $host
                ),
                "user" => $this->_username,
                "date" => time(),
                "remarks" => "",
                "status" => $publish,
                "couponType" => $this->_expirationType
            );

            $coupon = array(
                'batch_name' => $batchName,
                'domain_name' => $post['siteId'],
                'description' => $post['description'],
                'start_date' => (int) $this->_baseService->convertDateTimezone($post['startDate'], $this->_timezone)->getTimestamp(),
                'end_date' => (int) $this->_baseService->convertDateTimezone($post['endDate'], $this->_timezone)->getTimestamp(),
                'discount_type' => $post['type'],
                'discount_amt' => $post['discountVal'],
                'discount_amt_qual' => $post['amountQualifier'],
                'discount_ceiling_amt' => $post['ceilingAmount'],
                'discount_apply' => $post['couponAppliesTo'],
                'creator' => $this->_username,
                'createdAt' => time(),
                'restrictions' => $restrictions,
                'publish' => $publish,
                'appeasement' => $appeasement,
                'class' => $post['class'],
                'owner' => $post['owner'],
                'createdvia' => "ui",
                'initial' => (int) $initialQuantity,
                'quantity' => (int) $initialQuantity,
                'action' => "create",
                'isGenerate' => $post['generate'],
                'lower_limit' => $post['lowerLimit'], # Name: Del # Date Modify: April 2015
                'auto_dispense' => $post['dispense'], # Name: Del # Date Modify: April 2015
                'is_gated' => $post['gated'], # Name: Del # Date Modify: April 2015
                'log' => $datalogs
            );
            return $coupon;
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    private function _updateDetails($couponDetails, $post) {
        try {
            $return = array();
            $publish = ($post['publish'] == 1) ? true : false;
            $logType = "editcoupon";
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $host = $_SERVER['SERVER_NAME'];
            $batchName = $couponDetails['coupon']['name'];
            $datalogs = array(
                "code" => $batchName,
                "type" => $logType,
                "transDetails" => "",
                "transData" => array(
                    1 => $ipAddress,
                    2 => $host
                ),
                "user" => $this->_username,
                "date" => time(),
                "remarks" => "",
                "status" => $publish,
                "couponType" => $this->_expirationType
            );


            $restrictions = $couponDetails['templateId'];

            $setUnused = $post['setUnused'];
            $coupon = array(
                'batch_name' => $batchName,
                'domain_name' => $couponDetails['coupon']['domains'][0],
                'description' => $couponDetails['description'],
                'start_date' => $couponDetails['expiration']['startDate']['sec'],
                'end_date' => $couponDetails['expiration']['endDate']['sec'],
                'discount_type' => $couponDetails['coupon']['type'],
                'discount_amt' => $couponDetails['coupon']['discountVal'],
                'discount_amt_qual' => $couponDetails['coupon']['amountQualifier'],
                'discount_ceiling_amt' => $couponDetails['coupon']['ceilingAmount'],
                'discount_apply' => $couponDetails['coupon']['couponAppliesTo'],
                'creator' => isset($couponDetails['coupon']['creator']) ? $couponDetails['coupon']['creator'] : "",
                'createdAt' => $couponDetails['createdAt']['sec'],
                'modified' => ($post['publish'] == 1) ? false : true,
                'modifiedby' => $this->_username,
                'updatedAt' => time(),
                'restrictions' => $restrictions,
                'publish' => $publish,
                'appeasement' => (isset($couponDetails['appeasement']) && $couponDetails['appeasement'] != "") ? $couponDetails['appeasement'] : false,
                'class' => $couponDetails['coupon']['class'],
                'owner' => $couponDetails['coupon']['owner'],
                'createdvia' => "ui",
                'quantity' => (int) $setUnused,
                'action' => "update",
                'previous_quantity' => (int) $post['initialQuantityHidden'],
                'initial' => $post['initialQuantityHidden'] + $setUnused,
                'remaining' => $post['remainingQuantityHidden'] + $setUnused,
                'isGenerate' => $post['generate'],
                'lower_limit' => $post['lowerLimitHidden'], # Name: Del # Date Modify: April 2015
                'auto_dispense' => $post['dispenseHidden'], # Name: Del # Date Modify: April 2015
                'is_gated' => $post['gatedHidden'], # Name: Del # Date Modify: April 2015
                'log' => $datalogs, 
            );
            return $coupon;
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    public function view($request) {
        $result = array();
        $data = array(
            "coupon.name" => $request->getParam('name', "")
        );
        $couponClass = $this->_baseService->hydraConnect($data, "coupon", "read");
        $result['setToView']['readotucoupon'] = $couponClass;

        $class_arr = $this->_couponFormModel->getApplyCouponTo();
        $result['setToView']['readotucouponclass'] = $class_arr[$couponClass[0]['coupon']['class']];

        $datasite = array(
            "siteId" => $this->view->readotucoupon[0]['coupon']['siteId'][0]
        );
        $result['setToView']['readotucouponsite'] = $this->_baseService->hydraConnect($datasite, "domains", "read", "live");

        $appliesto_arr = array('ORDERTOTAL' => 'Order Total', 'SUBTOTAL' => 'Sub-total', 'SHIPPING' => 'Shipping', 'TAX' => 'Tax', 'ITEM' => 'Order Line Item');
        $result['setToView']['readotucouponappliesto'] = $appliesto_arr[$this->view->readotucoupon[0]['coupon']['couponAppliesTo']];

        $dataowner = array(
            "value" => $this->view->readotucoupon[0]['coupon']['owner']
        );
        $result['setToView']['readotucouponowner'] = $this->_baseService->hydraConnect($dataowner, "owners", "read", "live");

        $databatch = array(
            "batch_id" => $this->view->readotucoupon[0]['coupon']['name']
        );
        $result['setToView']['readotucouponbatch'] = $this->_baseService->hydraConnect($databatch, "batches", "read", "live");
        return $result;
    }

    /**
     * 
     * @param array $batchIds
     */
    public function delete($batchIds) {
        try {
            return $this->_buildResponse(false, "Batch/es cannot be deleted. Function not available.", "/dcms/coupon/otubatchlist/");
            foreach ($batchIds as $batchId => $jobId) {
                if ($jobId != "" && $this->isJobProcessing($jobId)) {
                    
                } else {
                    $batchDetails = $this->getBatchDetails(array("batchId" => $batchId));
                    $publish = $batchDetails['status'];
                    $batchName = $batchDetails['coupon']['name'];
                    $expirationType = $batchDetails['expiration']['type'];

                    $set_where = array(
                        'set' => array('deleted' => true),
                        'where' => array('batchId' => $batchId)
                    );
                    $result = $this->_baseService->hydraConnect(array($set_where), "coupon", "update");
                    if (!isset($result['error'])) {
                        $result = $this->_baseService->hydraConnect(array($set_where), "coupon", "update", "live");
                        if ($publish == "published") {
                            $logType = "deletecoupon";
                            $ipAddress = $_SERVER['REMOTE_ADDR'];
                            $host = $_SERVER['SERVER_NAME'];
                            $creator = $this->_username;
                            $createdAt = time();
                            $datalogs = array(
                                "name" => $batchName,
                                "type" => $logType,
                                "transDetails" => "",
                                "transData" => array(
                                    1 => $ipAddress,
                                    2 => $host
                                ),
                                "user" => $creator,
                                "date" => $createdAt,
                                "remarks" => "",
                                "status" => $publish,
                                "couponType" => $expirationType
                            );
                            $this->_baseService->hydraConnect($datalogs, "logs", "create", "live");
                        }
                    }
                }
            }

            
        } catch (Exception $e) {
            return $this->_buildResponse(false, $e->getMessage(), "/dcms/coupon/otubatchlist/");
        }
    }

    /**
     * 
     * @param type $jobId
     * @param type $addinfo current|history
     * @return type
     */
    public function getJobDetails($jobId, $addinfo = "") {
        $query = array(
            'job_id' => $jobId,
        );

        if (!empty($addinfo)) {
            $query['add_info'] = $addinfo;
        }
        $getWorkerStatus = $this->hydraConnect($query, "getOneTimeUseStatus");
        return (isset($getWorkerStatus['result'])) ? $getWorkerStatus['result'] : array();
    }

    public function isJobProcessing($jobid) {
        $getWorkerStatus = $this->getJobDetails($jobid);
        if (count($getWorkerStatus) > 0 && $getWorkerStatus['job_details']['status'] == "processing") {
            return true;
        }
        return false;
    }
    
    public function isJobQueued($jobid) {
        $getWorkerStatus = $this->getJobDetails($jobid);
        if (count($getWorkerStatus) > 0 && $getWorkerStatus['job_details']['status'] == "queued") {
            return true;
        }
        return false;
    }
    
    public function isJobBusy($jobid){
        $getWorkerStatus = $this->getJobDetails($jobid);
        if (count($getWorkerStatus) > 0 && $getWorkerStatus['job_details']['status'] == "completed") {
            return true;
        }
        return false;
    }

    public function getCurrentJobStatus($jobStatus) {
        $currentStatus = $jobStatus['add_info']['current']['jobPayload'];
        $status = unserialize($currentStatus);
        return $status;
    }

    private function _buildResponse($success, $message, $path) {
        switch ($success) {
            case true:
                $logcode = Zend_Log::INFO;
                break;
            default:
                $logcode = Zend_Log::ERR;
                break;
        }
        return array(
            'result' => array(
                'success' => $success,
                'code' => $logcode,
                'message' => $message,
                'path' => $path
            )
        );
    }

    /*     * *
     * {
      "coupon_code": <>,
      "version": <integer|use php time()>,
      "type" : "newcoupon"
      "date":MongoDate,
      "status":<string|testing or for publishing|published>,
      "published_by":<string>,
      "old":<coupon document> //if modified
      "new":<coupon document> //if modified
      }
     */

    public function logCouponPublishing($values, $action) {
        $details = array(
            'coupon_code' => $values['coupon']['code'],
            'type' => $action,
            'version' => time(),
            'date' => time(),
            'status' => "published",
            'published_by' => $this->_username,
        );
        if (count($this->_oldCoupon) > 0 && count($this->_newCoupon) > 0) {
            $details['old'] = $this->_oldCoupon;
            $details['new'] = $this->_newCoupon;
        }
        return $this->base->hydraConnect(array($details), "coupon_publishing", "create", "live");
    }

    /**
     * 
     * @param type $data
     * @return type
     * Array
      (
      [result] => Array
      (
      [job_id] => 5131be9b8a1ee8bb1a00045f
      [batch_name] => TESTSTST
      [message] => Operation Successful (CRUD)
      [error_code] => 2000
      )

      [error] =>
      )
      1
     */
    private function _creatOneTimeUseCoupon($data) {
        $config = $this->_config->service->sourceobject->toArray();
        try {
            $serviceObject = $config['coupon'];
            $serviceObject['method'] = "createOneTimeUseCoupon";
            $getResults = Hydra_Helper::loadClass(
                $serviceObject['url'], 
                $serviceObject['version'], 
                $serviceObject['service'], 
                $serviceObject['method'], 
                $data, 
                $serviceObject['httpmethod'], 
                $serviceObject['id'], 
                $serviceObject['format']
            );
            if (is_string($getResults) || !isset($getResults['_payload']['result'][$serviceObject['method']])) {
                return array();
            }
            return $getResults['_payload']['result'][$serviceObject['method']];
        } catch (Exception $e) {
            return array();
        }
    }

    public function hydraConnect($data, $method, $env = "live") {
        $config = $this->_config->service->sourceobject->toArray();

        try {
            $serviceObject = $config['coupon'];
            $serviceObject['method'] = $method;
            $serviceObject['data'] = $data;

            $getResults = Hydra_Helper::loadClass(
                $serviceObject['url'], 
                $serviceObject['version'], 
                $serviceObject['service'], 
                $serviceObject['method'], 
                $serviceObject['data'], 
                $serviceObject['httpmethod'], 
                $serviceObject['id'], 
                $serviceObject['format']
            );
            if (is_string($getResults) || !isset($getResults['_payload']['result'][$serviceObject['method']])) {
                return array();
            }

            return $getResults['_payload']['result'][$serviceObject['method']];
        } catch (Exception $e) {
            return array();
        }
    }

    private function _getMessage($errorCode, $name = "") {
        $msg = $this->_config->otu->msg->$errorCode;
        if(!empty($name)){
            $msg = str_replace("<NAME>", $name, $msg);
            return $msg;
        }
        return $msg;
    }

    /**
     * 
     * @param type $query
     * @return type
     */
    public function getBatchDetails($queryParam) {
        if (is_string($queryParam)) {
            $queryParam = array("coupon.name" => $queryParam);
        }
        $query = array("deleted" => array('$ne' => true));
        $query = array_merge($query, $queryParam);
        $searchCoupon = $this->_baseService->hydraConnect($query, "batches", "read", "live");
        $batchRecord = $this->_baseService->getRecords($searchCoupon);
        return $batchRecord[0];
    }
    
    
      public function export($request){
        $jobid = $request['jobid'];
        $batchName = $request['batchname'];
        $batchId = $request['batchid'];
        if($this->isJobProcessing($jobid) || $this->isJobQueued($jobid)){
            return array(
                'success'   => false,
                'message'   => $this->_getMessage("COUPON_GENERATING_CANNOT_EXPORT", $batchName),
                'path'      => "/dcms/coupon/otubatchlist",
            );
        }else{
            return array(
                'success' => true,
                'message' => $this->_config->otu->export->url . "{$batchId}.zip",
                'path' => $this->_config->otu->export->url . "{$batchId}.zip"
            );
        }
    }

}

?>
