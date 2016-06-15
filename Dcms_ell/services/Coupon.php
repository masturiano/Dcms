<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Dcms_Service_Coupon extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $config;

    /**
     *
     * @var Dcms_Model_Form
     */
    protected $couponFormModel;

    /**
     *
     * @var type 
     */
    protected $username;

    /**
     *
     * @var type 
     */
    public $base;
    protected $hydraService;
    protected $_counponDetails;
    protected $_oldCoupon;
    protected $_newCoupon;
    protected $_couponWhereClause;
    protected $killswitch;

    /**
     *
     * @param Zend_Config $config
     * @param USAP_Resource_Mongomultidb $mongoResource
     */
    public function __construct(Zend_Config $config, $base) {
        $this->base = $base;
        $this->couponFormModel = new Dcms_Model_CouponValues();
        $this->config = $config;
        $userReg = Zend_Registry::get('user');
        $this->username = $userReg->getUsername();
        $this->_couponDetails = array();
        $this->_newCoupon = array();
        $this->_oldCoupon = array();
        $this->timezone = $config->timezone->value;
        $this->_couponWhereClause = array();
        $session = Zend_Registry::get('dcms_switch');
        $this->killswitch = $session->killswitch;
    }

    public function getCouponFormValues() {
        return $this->couponFormModel;
    }

    /**
     * Builds add coupon form 
     * @return Dcms_Form_Coupon_Add 
     */
    public function createCouponAddForm() {
        $form = new Dcms_Form_Coupon_Add();
        $form->buildCouponForm();
        $this->setFormValuesForAdd($form);
        return $this->buildCouponForm($form);
    }

    public function createCouponEditForm($values) {
        $form = new Dcms_Form_Coupon_Add();
        $form->buildCouponForm();
        $this->setFormValuesToEdit($form, $values);
        return $this->buildCouponForm($form);
    }

    public function buildCouponForm($form) {
        
        $uiConfig = new Dcms_Model_UiConfig();
        //set dropdown list for type
        $type = $this->couponFormModel->getTypes();
        $form->getElement('type')->addMultiOptions($type);

        //set dropdown list for apply coupon to 
        $applycouponto = $this->couponFormModel->getApplyCouponTo();
        $form->getElement('class')->addMultiOptions($applycouponto);

        //set dropdown list for sites
        $siteList = $this->base->getSites();
        $form->getElement('site')->addMultiOptions($siteList);

        //set dropdown list for apply discount to 
        $applydiscounttoList = $this->couponFormModel->getApplyDiscountTo();
        $form->getElement('couponAppliesTo')->addMultiOptions($applydiscounttoList);

        //set dropdown list for owner 
        $owner = $this->base->getOwner();
        $form->getElement('owner')->addMultiOptions($owner);

        //set dropdown list for type of expiration
        $expiration = $this->couponFormModel->getExpirations();
        //switch for first x users; if true disable
        if (isset($this->killswitch['firstxusers']) && $this->killswitch['firstxusers']) {
            unset($expiration['firstxusers']);
        }
        $form->getElement('expiration')->addMultiOptions($expiration);

        //set dropdown list for recurring period
        $recurring_period = $this->couponFormModel->getNumberOfMonths();
        $form->getElement('recurring_period')->addMultiOptions($recurring_period);

        //set dropdown list for recurring period month
        $recurring_period_month = $this->couponFormModel->getMonths();
        /* $recurring_period_month = array();
          $currentMonth = date('m');
          for($x = $currentMonth; $x <= 12; $x++){
          $recurring_period_month["$x"] = $recurring_period_month_values["$x"];
          } */

        $form->getElement('recurring_period_month')->addMultiOptions($recurring_period_month);

        //set dropdown list for recurring period year
        $recurring_period_year = $this->couponFormModel->getYears();
        $form->getElement('recurring_period_year')->addMultiOptions($recurring_period_year);

        //set dropdown list for apply for restriction
//        $resConf = $uiConfig->getUiConfig(array('$and' => array(array("name" => "default"), array("category" => "restriction_search"))));
//        $paramRes = array();
//        $paramRes['limitskip'] = isset($resConf['limitskip']) ? $resConf['limitskip'] : array();
//        $restrictions = $this->base->getRestrictions("", $paramRes);
//        asort($restrictions);
//        $form->getElement('restrictions')->addMultiOptions($restrictions);

        //switch for appeasement
        if (isset($this->killswitch['appeasement']) && $this->killswitch['appeasement']) {
            //$form->removeElement('appeasement');
            $form->getElement('appeasement')->setAttribs(array(
                "style" => "display:none",
            ));
            $form->getElement('appeasement')->setLabel("");
        }

        return $form;
    }

    public function setFormValuesForAdd(&$form) {
        $form->getElement('expiration')->setValue("nonexpiring");
        $curMonth = date('m');
        $form->getElement('recurring_period_month')->setValue($curMonth);
        $curYear = date('Y');
        $form->getElement('recurring_period_year')->setValue($curYear);
        $form->getElement('restrictions')->setAttrib('disabled', true);
        return $form;
    }

    public function setFormValuesToEdit(&$form, $values) {
        $form->getElement('code')->setValue($values['coupon']['code']);

        isset($values['code_live']) ? $form->getElement('code_live')->setValue($values['code_live']) : "";

        $form->getElement('type')->setValue($values['coupon']['type']);

        $form->getElement('discountVal')->setValue(isset($values['coupon']['discountVal'][0]) ? $values['coupon']['discountVal'][0] : "" );

        $form->getElement('amountQualifier')->setValue(isset($values['coupon']['amountQualifier'][0]) ? $values['coupon']['amountQualifier'][0] : "");

        $form->getElement('ceilingAmount')->setValue($values['coupon']['ceilingAmount']);

        $form->getElement('class')->setValue($values['coupon']['class']);

        $form->getElement('site')->setValue($values['coupon']['domains'][0]);

        $form->getElement('couponAppliesTo')->setValue($values['coupon']['couponAppliesTo']);

        $form->getElement('owner')->setValue($values['coupon']['owner']);

        $form->getElement('expiration')->setValue($values['expiration']['type']);

        $form->getElement('appeasement')->setValue($values['coupon']['appeasement']);

        $form->getElement('free_shipping')->setValue($values['coupon']['couponAppliesTo'] == "FREESHIPPING" ? "1" : "0");

        if (isset($values['expiration']['startDate'])) {
            $startDateSec = $values['expiration']['startDate']['sec'];
            $date = date("m/d/Y", $startDateSec);
            $form->getElement('from')->setValue($date);
            if ($values['expiration']['type'] == "expiring") {
                $time = date("h:i a", $startDateSec);
                $form->getElement('from_min')->setValue($time);
            }
        }

        if (isset($values['expiration']['endDate'])) {
            $endDateSec = $values['expiration']['endDate']['sec'];
            $date = date("m/d/Y", $endDateSec);
            $form->getElement('to')->setValue($date);
            if ($values['expiration']['type'] == "expiring") {
                $time = date("h:i a", $endDateSec);
                $form->getElement('to_min')->setValue($time);
            }
        }

        if ($values['expiration']['type'] == "recurring") {
            $form->getElement('startday')->setValue($values['expiration']['startDayOfMonth']);
            $form->getElement('endday')->setValue($values['expiration']['endDayOfMonth']);
            $form->getElement('recurring_period')->setValue($values['expiration']['numberOfMonths']);
            $recurringPeriodMonthYear = explode(", ", $values['expiration']['monthYearStart']);
            $form->getElement('recurring_period_month')->setValue($recurringPeriodMonthYear[0]);
            $recurring_period_year[$recurringPeriodMonthYear[1]] = $recurringPeriodMonthYear[1];
            $form->getElement('recurring_period_year')->addMultiOptions($recurring_period_year);
            $form->getElement('recurring_period_year')->setValue($recurringPeriodMonthYear[1]);
        }

        if ($values['expiration']['type'] == "firstxusers") {
            $form->getElement('valid_first_x_users')->setValue($values['expiration']['remainingQuantity']);
        }

        if ($values['coupon']['couponAppliesTo'] == "ORDERTOTAL" || $values['coupon']['couponAppliesTo'] == "SHIPPING" || $values['coupon']['couponAppliesTo'] == "FREESHIPPING") {
            $form->getElement('applyrestriction_checkbox')->setAttrib('disabled', true);
            $form->getElement('restrictions')->setAttrib('disabled', true);
        } else {
            if (isset($values['templateId']) && $values['templateId'] != "") {
                $form->getElement('applyrestriction_checkbox')->setValue(1);
                $form->getElement('restrictions')->setValue($values['templateId']);
            } else {
                $form->getElement('applyrestriction_checkbox')->setValue(0);
                $form->getElement('restrictions')->setAttrib('disabled', true);
            }
        }

        $form->getElement('publish')->setValue(($values['status'] == "published") ? true : false);

        return $form;
    }

    /**
     *
      {
      "_id"    : MongoId,
      "coupon" : {
      "code" : <string>,
      "name":<string - can be used as coupon name and batch_id>
      "createdAt"  : [{Mongodate,time_zone}], //outside of coupon doc
      "updatedAt"  : [{Mongodate,time_zone}], //outside of coupon doc
      "type"     : <string; percent or amount>,
      "siteId"   : [ siteId1,… siteIdN | -1 ]
      "discountVal" : [<string|0 - this will contain discount values or percentage values proportional to the amountQualifier>],
      "amountQualifier" : [<string|0 - this will contain the qualifier amount values with a corresponding discountVal>],
      "ceilingAmount" : <string>,
      "appeasement" : <Boolean|false >,
      "couponAppliesTo" : <string|Order Total, SubTotal, ITEM, SHIPPING, etc]>,
      "creator"   : <string>,
      "versionId":<integer|use php time()>,
      "status":<string|testing or for publishing|published>,
      "class":<boolean or integer| 0 - offline or 1 - online or 3 - both>,
      "channelCode":<string>,
      "siteAcr":<string>,
      "promo_type":<string>
      },
      "expiration":{
      "type":<string|expiring or non-expiring or one-time-use or first-x-users or recurring>,
      "startDate":MongoDate,
      "endDate":MongoDate,
      "startTime":<string>,
      "endTime":<string>,
      "startDayOfMonth":<string>,
      "endDayOfMonth":<string>,
      "remainingQuantity":<integer>,
      "initialQuantity":<integer>,
      "status":<string|unused or used or expired or disabled>

      },
      "restrictions" : <string|templateName>,
      "createdvia":<string|api call or ui>
      }
     */
    public function mapCouponValues($formValues) {
        try {

            function valuesToInt($value) {
                return (int) $value;
            }

            $siteInfo = $this->base->getSiteInfo($formValues['site']);
            $couponModel = array(
                'coupon' => array(
                    "code" => strtoupper(trim($formValues['code'])),
                    "name" => strtoupper(trim($formValues['code'])),
                    "type" => $formValues['type'],
                    "appeasement" => ($formValues['appeasement']) ? true : false,
                    "couponAppliesTo" => $formValues['couponAppliesTo'],
                    "versionId" => time(),
                    "class" => (int) $formValues['class'],
                    "domains" => array($formValues['site']),
                    "owner" => $formValues['owner'],
                    "siteId" => array($siteInfo['siteId']),
                    "channelCode" => $siteInfo['channelCode'], //for offline | online | Both                        )
                )
                    )
            ;


            $couponModel['coupon']["discountVal"] = array();
            if (count($formValues['discountVal']) > 0 && !in_array("", $formValues['discountVal'])) {
                $couponModel['coupon']["discountVal"] = array_map("valuesToInt", $formValues['discountVal']);
            }

            $couponModel['coupon']["amountQualifier"] = array();
            if (count($formValues['amountQualifier']) > 0 && !in_array("", $formValues['amountQualifier'])) {
                $couponModel['coupon']["amountQualifier"] = array_map("valuesToInt", $formValues['amountQualifier']);
            }

            $couponModel['coupon']["ceilingAmount"] = "";
            if ($formValues['ceilingAmount'] != "") {
                $couponModel['coupon']["ceilingAmount"] = (int) $formValues['ceilingAmount'];
            }

            $couponModel['expiration']['type'] = $formValues['expiration'];
            if ($formValues['expiration'] == 'expiring' || $formValues['expiration'] == 'firstxusers') {
                $startDate = $formValues['from'];
                $endDate = $formValues['to'];
                if ($formValues['expiration'] == 'firstxusers') {
                    $couponModel['expiration']['initialQuantity'] = (int) $formValues['valid_first_x_users'];
                    $couponModel['expiration']['remainingQuantity'] = (int) $formValues['valid_first_x_users'];
                } else {
                    $startDate .= " " . $formValues['from_min'];
                    $endDate .= " " . $formValues['to_min'];
                }
                $startDate = $this->base->convertDateTimezone($startDate, $this->timezone);
                $endDate = $this->base->convertDateTimezone($endDate, $this->timezone);

                $couponModel['expiration']['startDate'] = $startDate->getTimestamp();
                $couponModel['expiration']['endDate'] = $endDate->getTimestamp();
            } else if ($formValues['expiration'] == 'recurring') {
                $couponModel['expiration']['startDayOfMonth'] = $formValues['startday'];
                $couponModel['expiration']['endDayOfMonth'] = $formValues['endday'];
                $couponModel['expiration']['numberOfMonths'] = $formValues['recurring_period'];
                $couponModel['expiration']['monthYearStart'] = $formValues['recurring_period_month'] . ", " . $formValues['recurring_period_year'];

                $startDate_time = strtotime($formValues['recurring_period_month'] . "/" . $formValues['startday'] . "/" . $formValues['recurring_period_year'] . " {$this->timezone}");
                $forEndDate_time = strtotime($formValues['recurring_period_month'] . "/" . $formValues['endday'] . "/" . $formValues['recurring_period_year'] . " {$this->timezone}");
                $endDate_time = strtotime(date("Y-m-d", $forEndDate_time) . " +{$formValues['recurring_period']} months");

                $couponModel['expiration']['startDate'] = $startDate_time;
                $couponModel['expiration']['endDate'] = $endDate_time;
            }
            if ($formValues['applyrestriction_checkbox']) {
                $restriction = (int) $formValues['restrictions'];
                $templateInfo = $this->base->getRestrictions($restriction);
                $couponModel['restrictions'] = $templateInfo['templateName'];
                $couponModel['templateId'] = $restriction;
            }
            $couponModel['expiration']['timezone'] = $this->timezone;
            $couponModel['createdvia'] = "ui";
            $couponModel['status'] = ($formValues['publish']) ? "published" : "testing";
            $couponModel['expiration']['status'] = "unused";

            return $couponModel;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param type $formValues
     * @param type $action
     * @return type
     * @throws Exception
     */
    public function buildCouponDocument($formValues, $action) {
        try {
            $formValues['timezone'] = $this->timezone;
            $couponModel = new Dcms_Model_Coupon($formValues, $action);
            $couponModel->setUsername($this->username);
            return $couponModel->getDocument();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function viewCoupon($queryFind) {
        $values = $this->search($queryFind, "coupon");
        if (count($values) == 0) {
            return array('error' => Zend_Log::ERR);
        }
        $values = $values[0];
        $couponValues = $this->getCouponFormValues();
        $expirations = $couponValues->getExpirations();
        $getApplyCouponTo = $couponValues->getApplyCouponTo();
        $couponAppliesTo = $couponValues->getApplyDiscountTo();
        $owner = $this->base->getOwner();

        $values['expiration']['coupontype'] = $expirations[$values['expiration']['type']];
        $values['coupon']['class'] = $getApplyCouponTo[$values['coupon']['class']];
        $values['coupon']['couponAppliesTo'] = in_array($values['coupon']['couponAppliesTo'], $couponAppliesTo) ? $couponAppliesTo[$values['coupon']['couponAppliesTo']] : $values['coupon']['couponAppliesTo'];
        $values['coupon']['owner'] = isset($owner[$values['coupon']['owner']]) ? $owner[$values['coupon']['owner']] : $values['coupon']['owner'];
        $values['base'] = $this->base;
        $values['title'] = "Coupon";
        $values['user_action'] = "View";
        $values['headTitle'] = "View Coupon";
        return $values;
    }

    public function createCoupon($formValues) {

        $couponDocument = $this->buildCouponDocument($formValues, "create");
        $couponDocument['expiration']['status'] = "unused";
        if (isset($couponDocument['error'])) {
            return array(
                'success' => false,
                'code' => Zend_Log::ERR,
                'message' => "Could not add coupon.",
                'error' => $couponDocument['error']
            );
        } else {
            //redandunt $couponModel, $couponModel, 
            $result = $this->saveCouponAndRestrictions($couponDocument, $couponDocument, "create");
            if ($result['error'] === null) {
                return array(
                    'success' => true,
                    'code' => Zend_Log::INFO,
                    'message' => "Coupon has been successfully added.",
                );
            } else {
                return array(
                    'success' => false,
                    'code' => Zend_Log::ERR,
                    'message' => $result['error'],
                );
            }
        }
    }

    public function preserveCouponDetails($couponDetails) {
        if (isset($couponDetails['expiration']['startDate']) && isset($couponDetails['expiration']['endDate'])) {
            $couponDetails['expiration']['startDate'] = $couponDetails['expiration']['startDate']['sec'];
            $couponDetails['expiration']['endDate'] = $couponDetails['expiration']['endDate']['sec'];
        }
        $this->_couponDetails = $couponDetails;
    }

    public function add($request) {
        $return = array();
        $return['setToView']['user_action'] = "Add";

        if ($request->isPost()) {
            $getPost = $request->getPost();
            //switch for first x users; if true disable
            if (isset($this->killswitch['firstxusers']) && $this->killswitch['firstxusers']) {
                if ($getPost['expiration'] == 'firstxusers') {
                    return array('result' => array(
                            'success' => false,
                            'code' => Zend_Log::ERR,
                            'message' => "Coupon is FirstXUsers. Function is currently disabled"));
                }
            }
            //switch for appeasement
            if (isset($this->killswitch['appeasement']) && $this->killswitch['appeasement']) {
                if (isset($getPost['appeasement']) && $getPost['appeasement']) {
                    return array('result' => array(
                            'success' => false,
                            'code' => Zend_Log::ERR,
                            'message' => "Coupon is Appeasement. Function is currently disabled"));
                }
            }
            $result = $this->createCoupon($getPost);
            if (!$result['success'] && isset($result['error'])) {
                $return['setToView']['discountVal'] = $getPost['discountVal'];
                $return['setToView']['amountQualifier'] = $getPost['amountQualifier'];
                unset($getPost['discountVal']);
                unset($getPost['amountQualifier']);
                $form = $this->createCouponAddForm();
                $form->populate($getPost);
                $return['setToView']['form'] = $form;
                $return['setToView']['error'] = $result['error'];
                //exit;
            } else {
                $return['result'] = $result;
            }
        } else {
            $return['setToView']['form'] = $this->createCouponAddForm();
        }
        $return['setToView']['template'] = $this->base->getHydraPaginator(10, 0, "template", array("templateName" => '1'));
        return $return;
    }

    public function edit($request) {
        $return = array();
        $getParams = $request->getParams();
        $id = $getParams['id'];
        $return['setToView']['user_action'] = "Edit";
        $values = $this->search(array('_id' => $id), "coupon");
        if (count($values) == 0) {
            throw new Exception('Coupon not found');
        }

        $this->preserveCouponDetails($values[0]);
        if ($request->isPost()) {
            //switch for first x users; if true disable
            if (isset($this->killswitch['firstxusers']) && $this->killswitch['firstxusers']) {
                if ($this->_couponDetails['expiration']['type'] == 'firstxusers') {
                    return array('result' => array(
                            'success' => false,
                            'code' => Zend_Log::ERR,
                            'message' => "Coupon was FirstXUsers. Function is currently disabled"));
                }
            }
            //switch for appeasement
            if (isset($this->killswitch['appeasement']) && $this->killswitch['appeasement']) {
                if (isset($this->_couponDetails['coupon']['appeasement']) && $this->_couponDetails['coupon']['appeasement']) {
                    return array('result' => array(
                            'success' => false,
                            'code' => Zend_Log::ERR,
                            'message' => "Coupon was Appeasement. Function is currently disabled"));
                }
            }
            $getPost = $request->getPost();
//			$couponCode = trim($getPost['code']);
            $this->findPublishedCoupon($getPost['publish'] ? "published" : "testing", array("_id" => isset($getPost['code_live']) ? $getPost['code_live'] : ""), $getPost);
            $getPost['createdAt'] = $this->_couponDetails['createdAt']['sec'];
            $getPost['creator'] = $this->_couponDetails['creator'];
            $getPost['createdvia'] = $this->_couponDetails['createdvia'];
            $result = $this->updateCoupon($getPost, array('_id' => $id));

            $return['setToView']['template'] = array();
            
            if (!$result['success'] && isset($result['error'])) {
                $form = $this->createCouponAddForm();
                $return['setToView']['expiration'] = $getPost['expiration'];
                $return['setToView']['status'] = $getPost['publish'];
                $return['setToView']['discountVal'] = $getPost['discountVal'];
                $return['setToView']['amountQualifier'] = $getPost['amountQualifier'];
                unset($getPost['discountVal']);
                unset($getPost['amountQualifier']);
                $form->populate($getPost);
                $return['setToView']['form'] = $form;
                $return['setToView']['error'] = $result['error'];

                return $return;
            } else {
                $return['result'] = $result;
            }
        } else {
            $this->findPublishedCoupon($values[0]['status'], array("coupon.code" => $values[0]['coupon']['code']), $values[0]);

            $return['setToView']['expiration'] = $values[0]['expiration']['type'];
            $return['setToView']['status'] = $values[0]['status'];
            $return['setToView']['discountVal'] = $values[0]['coupon']['discountVal'];
            $return['setToView']['amountQualifier'] = $values[0]['coupon']['amountQualifier'];
            $return['setToView']['template'] = $this->base->getHydraPaginator(10, 0, "template", array("templateName" => '1'));
            $return['setToView']['template']['templateName'] = $values[0]['restrictions'];
            $return['setToView']['template']['templateId'] = $values[0]['templateId'];
            
            $return['setToView']['form'] = $this->createCouponEditForm($values[0]);
            $return['setToView']['timezone'] = $this->timezone;
        }
        
        return $return;
    }

    public function findPublishedCoupon($status, $query, &$values) {
        if (isset($values) && $status == "published") {
            $searchForCouponInLive = $this->searchLive($query, "coupon");
            if (isset($searchForCouponInLive) && count($searchForCouponInLive) > 0) {
                $values['code_live'] = $searchForCouponInLive[0]['id'];
            }
        }
    }

    public function updateCoupon($valuesToset, $where) {
        $return = array();
        //back end validation here.. in case javascript crash
        $couponDocument = $this->buildCouponDocument($valuesToset, "update");
        $couponDocument['expiration']['status'] = $this->_couponDetails['expiration']['status'];
        if (isset($couponDocument['error'])) {
            return array(
                'success' => false,
                'code' => Zend_Log::ERR,
                'message' => "Could not modify coupon.",
                'error' => $couponDocument['error']
            );
        } else {
            $couponSetWhere = array(
                'set' => $couponDocument,
                'where' => $where
            );

            $this->_couponWhereClause = $where;
            if (isset($valuesToset['code_live'])) {
                $publishedCouponWhereClause = array("_id" => $valuesToset['code_live']);
            }

            $this->_newCoupon = $this->base->arrayRecursiveDiff($this->_couponDetails, $couponDocument);
            $this->_oldCoupon = $this->base->arrayRecursiveDiff($couponDocument, $this->_couponDetails);
            $result = $this->saveCouponAndRestrictions($couponSetWhere, $couponDocument, "update", $publishedCouponWhereClause);
            if ($result['error'] === null) {
                return array(
                    'success' => true,
                    'code' => Zend_Log::INFO,
                    'message' => "Coupon has been successfully modified.",
                );
            } else {
                return array(
                    'success' => false,
                    'code' => Zend_Log::ERR,
                    'message' => $result['error'],
                );
            }
        }
    }

    /**
     * Save coupon and template
     * If one add/update/delete fails rollback everything.
     * 
     * @param type $couponQuery
     * @param type $couponModel
     * @param type $action
     * @return type 
     */
    public function saveCouponAndRestrictions($couponQuery, $couponModel, $action, $publishedCouponWhereClause = array()) {
        try {
            $result = array();
            //update coupon working copy
            $result = $this->base->hydraConnect(array($couponQuery), "coupon", $action);
            $this->_throwException($result);
            ($action == "update") ? $couponQuery['where'] = $publishedCouponWhereClause : "";
            if ($couponModel['status'] == "published") {
                //publish coupon
                $result = $this->base->hydraConnect(array($couponQuery), "coupon", $action, "live");
                $this->_throwException($result);
                //dcms coupon publishing
                $result = $this->logCouponPublishing($couponModel, ($action == "create") ? "newcoupon" : "editcoupon");
            } else if ($couponModel['status'] != "published" && $action == "update") {
                //delete COUPON live copy if not published
                $result = $this->base->hydraConnect($couponQuery['where'], "coupon", "delete", "live");
                $this->_throwException($result);
            }

            //save template
            $this->saveRestrictions($couponModel);


            //dcms_logs
            $this->log($couponModel, ($action == "create") ? "newcoupon" : "editcoupon");

            return $result;
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    public function saveRestrictions($couponModel) {
        if (isset($couponModel['templateId']) && $couponModel['templateId'] != "") {
            $templateQuery = array(
                'set' => array(
                    'publish' => ($couponModel['status'] == "published") ? true : false,
                ),
                'where' => array(
                    'templateId' => (int) $couponModel['templateId']
                )
            );
//            ($couponModel['status'] == "published") ? $templateQuery['set']['modified'] = false : "";
            //update template status on working copy to publish = true
            $findRestriction = $this->base->hydraConnect(array('templateId' => (int) $couponModel['templateId']), "template", "read");
            if ($findRestriction['result']['record_count'] > 0) {
                $workingRestriction = $findRestriction['result']['records'][0];
                unset($workingRestriction['id']);
                $workingRestriction['publish'] = ($couponModel['status'] == "published") ? true : false;
                isset($workingRestriction['createdAt']) ? $workingRestriction['createdAt'] = $workingRestriction['createdAt']['sec'] : "";
                isset($workingRestriction['updatedAt']) ? $workingRestriction['updatedAt'] = $workingRestriction['updatedAt']['sec'] : "";
                ($couponModel['status'] == "published") ? $workingRestriction['modified'] = false : "";
                $where = array('templateId' => (int) $couponModel['templateId']);
                $templateQuery = array(
                    'set' => $this->base->arrayRecursiveEncode($workingRestriction),
                    'where' => $this->base->arrayRecursiveEncode($where)
                );
                $result = $this->base->hydraConnect(array($templateQuery), "template", "update", "working", "", true);

                $this->_throwException($result);

                if ($couponModel['status'] == "published") {
                    //upsert template on live
                    $result = $this->base->hydraConnect(array($templateQuery), "template", "update", "live", "", true);
                    $this->_throwException($result);
                } else {
                    //delete template live copy if not published
                    //if template is being used by another coupon it should not be deleted
                    $findCouponRestriction = $this->base->hydraConnect(
                            array('templateId' => (int) $couponModel['templateId']), "coupon", "read", "live");
                    $this->_throwException($findCouponRestriction);
                    if ($findCouponRestriction['result']['record_count'] == 0) {
                        $result = $this->base->hydraConnect(array('templateId' => (int) $couponModel['templateId']), "template", "delete", "live");
                        $this->_throwException($result);
                    }
                }
            }
        }
    }

    private function _throwException($result) {
        if (!empty($result['error'])) {
            $error = "";
            if (isset($result['error']['api_result'])) {
                $error = $result['error']['api_result'];
            } else if (isset($result['error']['message'])) {
                $error = $result['error']['message'];
            } else {
                $error = "An error occured on API.";
            }
            throw new Exception($error);
        }
    }

    public function deleteCouponMultiple($coupons = array()) {
        $result = array();//echo "<pre>",print_r($coupons);
        
        $checkCounter = 0;
        foreach ($coupons as $coupon) {
            if ($coupon['checkone'] == '1') {
                $checkCounter++;
                $code = $coupon['coupon_code'];
                $result = $this->deleteCouponSingle($code, $coupon);
            }
        }
        if ($checkCounter == 0) {
            $result['error'] = Zend_Log::ERR;
            $result['message'] = "No coupon was selected to be deleted";
        }//exit;
        return $result;
    }
    
    public function deleteCouponSingle($code, $coupon) {
        $where = array('coupon.code' => (string) $code, 'coupon.domains' => $coupon['domain']);
        $couponWorking = $this->search($where, "coupon", true);
        $couponDetailsWorking = $couponWorking[0];
        $couponDetailsWorking['deleted'] = true;
        $couponDetailsWorking['status'] = "testing";
        
        if(isset($couponDetailsWorking['expiration']['startDate']) && isset($couponDetailsWorking['expiration']['endDate'])){
           $couponDetailsWorking['expiration']['startDate'] = $couponDetailsWorking['expiration']['startDate']['sec'];
           $couponDetailsWorking['expiration']['endDate'] = $couponDetailsWorking['expiration']['endDate']['sec']; 
        }
        
        $couponDetailsWorking['createdAt'] = $couponDetailsWorking['createdAt']['sec'];
        (isset($couponDetailsWorking['updatedAt']['sec'])) ? $couponDetailsWorking['updatedAt'] = $couponDetailsWorking['updatedAt']['sec'] : "";
        $set_where = array(
            'set' => $couponDetailsWorking,
            'where' => $where
        );
        $result = $this->base->hydraConnect(array($set_where), "coupon", "update");//echo "<pre>",print_r($result);

        if (!isset($result['error'])) {
            $result = $this->base->hydraConnect($where, "coupon", "delete", "live");//echo "<pre>",print_r($result);
            $log = $this->log($couponDetailsWorking, "deletecoupon"); //echo "<pre> logs",print_r($log);
        }
        return $result;
    }

    public function search($query, $serviceType, $useQuery = false) {
        return $this->base->search($query, $serviceType, $useQuery);
    }

    public function searchLive($query, $serviceType) {
        $result = $this->base->hydraConnect($query, $serviceType, 'read', 'live');
        return isset($result['result']['records']) ? $result['result']['records'] : array();
    }

    /*
     * {
      "_id": ObjectId("504a89dc8a1ee8f36c0009b6"),
      "code": "25WHOMEXZCGT34N",
      "type": "newcoupon",
      "transDetails": "",
      "transData": {
      "1": "10.10.214.91",
      "2": "zsalud.usaptool.dev.usautoparts.com"
      },
      "user": "zsalud",
      "date": {
      "sec": 1347062235,
      "usec": 824000
      },
      "remarks": "",
      "status": "published",
      "couponType": "onetimeuse"
      }
      string| authentication, upload, newcoupon, editcoupon, deletecoupon, etc.>
     */

    public function log($values, $type) {
        $details = array(
            'code' => $values['coupon']['code'],
            'type' => $type,
            'transDetails' => "",
            'transData' => array(
                "1" => $_SERVER['REMOTE_ADDR'],
                "2" => $_SERVER['SERVER_NAME'],
            ),
            'user' => $this->username,
            'date' => time(),
            'log_date' => time(),
            'remarks' => "",
            'status' => $values['status'],
            'couponType' => $values['expiration']['type']
        );
        return $this->base->hydraConnect(array($details), "logs", "create", "live");
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
            'published_by' => $this->username,
        );
        if (count($this->_oldCoupon) > 0 && count($this->_newCoupon) > 0) {
            $details['old'] = $this->_oldCoupon;
            $details['new'] = $this->_newCoupon;
        }
        return $this->base->hydraConnect(array($details), "coupon_publishing", "create", "live");
    }

    public function isStringAllowed($value) {
        return preg_match('/^[A-Za-z0-9]+$/', $value);
    }

    public function searchCoupon($couponCode) {
        $return = array();
        if ($couponCode == "") {
            $return['error'] = "No coupon code was sent";
        } else {
            $query = array(
                '$or' => array(
                    array('coupon.code' => strtoupper(trim($couponCode))),
                    array('coupon.name' => strtoupper(trim($couponCode))),
                )
            );
            $record = $this->search($query, "coupon", true);
            $return['record'] = count($record);
            $return['error'] = !isset($record) ? "Unable to connect to server" : "";
            $return['pregmatch'] = $this->isStringAllowed($couponCode);
        }
        return $return;
    }
    
                    
                }
                
?>
