<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Dcms_Service_CouponList extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $config;

    /**
     *
     * @var type 
     */
    protected $collectionObject;
    protected $hydraServiceInfo;
    protected $base;

    /**
     *
     * @var Dcms_Model_Form
     */
    protected $couponFormModel;

    /**
     *
     * @param Zend_Config $config
     * @param USAP_Resource_Mongomultidb $mongoResource
     */
    public function __construct(Zend_Config $config, $base) {
        $this->base = $base;
        $this->couponFormModel = new Dcms_Model_CouponValues();
        $this->config = $config;
        $this->hydraServiceInfo = $config->service->sourceobject->toArray();
        $session = Zend_Registry::get('dcms_switch');
        $this->killswitch = $session->killswitch;
    }

    public function displayCouponList($request) {        
        $return = array();
        $couponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Coupon", array());
        $sites = $this->couponFormModel->getSites();
        $expiration = $this->couponFormModel->getExpirations();
        $return['site'] = array_merge(array('all' => "All"), $sites);
        $return['expiration'] = array_merge(array('all' => "All"), $expiration);
        $return['formValues'] = $this->couponFormModel;
        $return['owner'] = $this->couponFormModel->getOwner();
        $return['couponService'] = $couponService;
        $return['form'] = $this->couponSearchForm();
        $return['deleteForm'] = new Dcms_Form_Coupon_Delete();
        $return['base'] = $this->base;

        $request['sort'] = isset($request['sort']) ? $request['sort'] : "1" ;
        $this->couponListFiltering($request);
        $return['setToView'] = $request;

        if (isset($request['query']['coupon.code'])) {
            $request['query']['mongoregex'] = array('coupon.code' => trim($request['query']['coupon.code']));
            unset($request['query']['coupon.code']);
        }

        $newRequest = $request;
        if (isset($request['query']['mongoregex']))
            unset($newRequest['query']['mongoregex']);

        $return['request'] = $newRequest;

        /**
         * Initialize DCMS paginator
         */
        $return['paginator'] = $this->getPaginator($request);
        return $return;
    }

    /**
     * 
     */
    public function getPaginator($request) {
        $hydraService = $this->hydraServiceInfo['coupon'];

        $hydraService['data'] = array(
            'query' => array(),
            'type' => "coupon",
            'env' => "working"
        );
        $hydraService['method'] = "read";

        isset($request['query']) ? $hydraService['data']['query'] = $request['query'] : "";
        $hydraService['data']['sort'] = (isset($request['sortby'])) ? array($request['sortby'] => $request['sort']) : array('coupon.code' => "1");

        $excludeOnetimeuse = array(
            'expiration.type' => array(
                '$ne' => "onetimeuse")
        );
        $excludeFirstXusers = array();
        //switch for first x users; if true disable
        if (isset($this->killswitch['firstxusers']) && $this->killswitch['firstxusers']) {
            $excludeFirstXusers = array(
                   'expiration.type' => array(
                            '$ne' => "firstxusers"
                            ));
        }
        $excludeAppeasement = array();
        //switch for appeasement; if true disable
        if (isset($this->killswitch['appeasement']) && $this->killswitch['appeasement']) {
            $excludeAppeasement = array(
                   'coupon.appeasement' => false,
            );
        }
        $countExcludeFirstXusers = count($excludeFirstXusers);
        $countExcludeAppeasement = count($excludeAppeasement);
        if($countExcludeFirstXusers > 0 || $countExcludeAppeasement > 0){
            $exclusions['$and'] = array();
            array_push($exclusions['$and'], $excludeOnetimeuse);
            if($countExcludeFirstXusers > 0){
                array_push($exclusions['$and'], $excludeFirstXusers);
            }
            if($countExcludeAppeasement > 0){
                array_push($exclusions['$and'], $excludeAppeasement);
            }
            $excludeOnetimeuse = $exclusions;
        }
        
        if (isset($hydraService['data']['query']['expiration.type'])) {

            unset($hydraService['data']['query']['expiration.type']);

            if (isset($request['query']['mongoregex'])) {
                unset($request['query']['mongoregex']);
            }

            $excludeOnetimeuse = array(
                '$and' => array(
                    $request['query'],
                    $excludeOnetimeuse
                )
            );
        }
        $hydraService['data']['query'] = array_merge($this->base->queryIfDeleted($hydraService['data']['query']), $excludeOnetimeuse);
        $paginator = $this->base->getPaginator($hydraService, $request);
        return $paginator;
    }

    public function couponSearchForm() {
        $form = new Dcms_Form_Coupon_Search();
        $dcmsForm = new Dcms_Form_DcmsForm();

        $form->buildSearchForm();

//        $publish_checkbox = $dcmsForm->createCheckbox("status", "Published")->setAttrib('onClick', "document.home.submit();");
//        $checkAll = $dcmsForm->createCheckbox("checkall", "");
//        $checkAll->setAttrib('onClick', "toggleCheckbox(this, document.homedelete.checkone)");
//        $checkone = $dcmsForm->createCheckbox("checkone", "")->setAttribs(array(
//            'name' => "checkone[]",
////            'onChange' => "toggleDeleteList(this)"
//                ));
//        $form->setAction('/dcms/index/delete/');
//        $couponContainer = $dcmsForm->createElement("select", "couponContainer")->setAttrib('multiple', "multiple");
        $numberOfPages = $this->couponFormModel->getCountPerPage();
        $countPerpage = $dcmsForm->createSelectElement("countperpage", "Coupon per page: ")->addMultiOptions($numberOfPages);
        $countPerpage->setAttrib('onChange', "document.home.submit();");

        $form->addElements(
                array(
//                    $publish_checkbox,
//                    $checkAll,
//                    $checkone,
//                    $couponContainer,
                    $countPerpage
        ));

        $sites = array('all' => "All");
        $sites = array_merge($sites, $this->base->getSites());
        $form->getElement('coupon_domains')->addMultiOptions($sites);
        $expiration = array('all' => "All");
        $expiration = array_merge($expiration, $this->couponFormModel->getExpirations());
        $form->getElement('expiration_type')->addMultiOptions($expiration);
        return $form;
    }

    public function couponListFiltering(&$request) {
        $coupon_code = array();
        if (isset($request['coupon_code'])) {
            if (trim($request['coupon_code']) == "") {
                unset($request['coupon_code']);
            } else {
                $coupon_code = array('coupon.code' => preg_replace("/[\*\%]/", '', $request['coupon_code']));
            }
        }
        $coupon_domains = array();
        if (isset($request['coupon_domains'])) {
            if ($request['coupon_domains'] == "all") {
                unset($request['coupon_domains']);
            } else {
                $coupon_domains = array('coupon.domains' => $request['coupon_domains']);
            }
        }
        $expiration_type = array();
        if (isset($request['expiration_type'])) {
            if ($request['expiration_type'] == "all") {
                unset($request['expiration_type']);
            } else {
                $expiration_type = array('expiration.type' => $request['expiration_type']);
            }
        }

        $status = array();
        if (isset($request['status']) && $request['status'] == true) {
            $status = array('status' => ($request['status']) ? "published" : "testing");
        }
        $request['query'] = array_merge($coupon_code, $coupon_domains, $expiration_type, $status);
    }

    /**
     * 
     */
    public function validateParameters($arraParams, $requiredKeys = array(), $empty = array()) {
        $output = array();
        foreach ($requiredKeys as $key) {
            if ((count($empty) > 0 && in_array($key, $empty)) ? isset($arraParams[$key]) : empty($arraParams[$key])) {
                $output['message'] = "$key parameter is required";
                $output['error'] = Zend_Log::ERR;
                return $output;
                break;
            }
        }
    }

}
?>

