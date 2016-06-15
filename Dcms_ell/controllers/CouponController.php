<?php

/**
 * @catagory USAPTool_Modules
 * @package Dcms_Controller
 * @author gconstantino
 * @copyright 2011
 * @version $Id$
 */
class Dcms_CouponController extends USAP_Controller_Action {

    protected $bootstrap;
    protected $service;
    protected $baseService;
    protected $timezone;
    protected $couponService;
    protected $workerStatus;
    protected $killswitch;
    
    public function init() {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('searchcoupon', 'json')
                ->addActionContext('export', 'json')
                ->addActionContext('coupons', 'html')
                ->addActionContext('pages', 'html')
                ->initContext();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $this->bootstrap = $bootstraps['Dcms'];
        $this->service = $this->bootstrap->getOption('service');
        $this->baseService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Base", "");
        $this->timezone = $this->bootstrap->getOption('timezone');
		$this->view->timezone = $this->timezone['value'];
		$this->workerStatus = new Zend_Session_Namespace('worker');     
        $session = Zend_Registry::get('dcms_switch');
        $this->killswitch = $session->killswitch;
    }

    /**
     * can't use isValid because zend form can't re-populate multiple input text having names with []
     * error generated:  Warning: htmlspecialchars() expects parameter 1 to be string, array given in /usr/share/pear/Zend/View/Abstract.php on line 905
     */
    public function indexAction() {
       $this->_redirect('/dcms/coupon/add'); 
    }

    public function addAction() {
       $this->_action("add", "Add");
    }

    public function editAction() {
        $this->_action("edit", "Edit");
    }
	
    private function _action($action, $title){
		try {
			$couponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Coupon", "");
            $this->view->title = "Coupon"; 
            $this->view->headTitle($title . ' Coupon');
            $result = $couponService->$action($this->_request);
            if (isset($result['result']) && isset($result['result']['success'])) {
				$this->_buildRedirect($result['result']['message'], "/dcms/index", $result['result']['code']);
            }
			$this->setToView($result['setToView']);
        } catch (Exception $e) {
			$this->_buildRedirect("An error occurred.", "/dcms/index", Zend_Log::ERR);
        }
	}

    public function viewAction() {
        try {
            $couponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Coupon", "");
            $id = $this->_getParam('id');
            $values = $couponService->viewCoupon(array('_id' => $id));
			$this->view->headTitle($values['headTitle']);
            if (isset($values['error'])) {
                throw new Exception('Coupon not found');
            }
            $this->setToView($values);
        } catch (Exception $e) {
            $this->addMessage($e->getMessage(), Zend_Log::ERR);
            $this->_redirect('/dcms/index/homeguest');
        }
    }

    public function searchcouponAction() {
		$couponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Coupon", "");
        unset($this->view->user);
        unset($this->view->alltoollist);
		
        $couponCode = $this->_getParam('coupon');
		$result = $couponService->searchCoupon($couponCode);
		$this->setToView($result);
    }

    public function setToView($input) {
        foreach ($input as $k => $v) {
            $this->view->{$k} = $v;
        }
    }

    public function productionAction() {
        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);

        $request['type'] = $this->_request->getParam('type', "");
        $request['fromDate'] = $this->_request->getParam('fromDate', "");
        $request['toDate'] = $this->_request->getParam('toDate', "");

        if ($request['type'] != "") {
            $type_arr = array("couponType" => $request['type']);
        } else {
            $type_arr = array();
        }

        if ($request['fromDate'] != "" && $request['toDate'] != "") {
            $cutOfDate_arr = array(
                "log_date" => array(
                    '$gte' => strtotime($request['fromDate'] . " 12:00:00 am"),
                    '$lte' => strtotime($request['toDate'] . " 12:59:00 pm")
                )
            );
        } else {
            $cutOfDate_arr = array();
        }
        $merged_arrays = array_merge($type_arr, $cutOfDate_arr);

        $request['query'] = $merged_arrays;

        /**
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_ProductionList", "");
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        if (!empty($serviceOutput['error'])) {
            echo $serviceOutput['message'];
            $this->view->message = !empty($serviceOutput['message']) ? $serviceOutput['message'] : null;
        } else {
            $this->view->paginator = $serviceOutput['result'];
        }
    }

    public function couponsecondaryAction() {
        //GET SITES
        $data = array(
            "type" => "domains",
            "env" => "live",
            "query" => array(),
            "sort" => array("name" => 1)
        );
        $this->view->readsites = $this->_hydraConnect("read", $data);
        //END OF GET SITES

        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);
        $request['batch_id'] = $this->_request->getParam('batch_id', "");

        $batchId_arr = array(
            "mongoregex" => array(
                "batch_id" => $request['batch_id']
            )
        );
        $request['query'] = $batchId_arr;

        /**
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_CouponsecondaryList", "");
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        if (!empty($serviceOutput['error'])) {
            echo $serviceOutput['message'];
            $this->view->message = !empty($serviceOutput['message']) ? $serviceOutput['message'] : null;
        } else {
            $this->view->paginator = $serviceOutput['result'];
        }
    }
    
    public function otucouponsecondaryAction() {
        //GET SITES
        $data = array(
            "type" => "domains",
            "env" => "live",
            "query" => array(),
            "sort" => array("name" => 1)
        );
        $this->view->readsites = $this->_hydraConnect("read", $data);
        //END OF GET SITES

        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);

        $request['siteId'] = $this->_request->getParam('siteId', "");
        $request['batch_id'] = $this->_request->getParam('batch_id', "");

        $type_arr = array("expiration.type" => "onetimeuse");
        $deleted = array("deleted" => array('$ne' => true));

        if ($request['siteId'] != "") {
            $siteId_arr = array("coupon.siteId.0" => $request['siteId']);
        } else {
            $siteId_arr = array();
        }

        $batchId_arr = array(
            "mongoregex" => array(
                "coupon.name" => $request['batch_id']
            )
        );

        $merged_arrays = array_merge($type_arr, $deleted, $siteId_arr, $batchId_arr);
        $request['query'] = $merged_arrays;

        /** 
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_OtucouponsecondaryList", "");
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        if (!empty($serviceOutput['error'])) {
            echo $serviceOutput['message'];
            $this->view->message = !empty($serviceOutput['message']) ? $serviceOutput['message'] : null;
        } else {
            $this->view->paginator = $serviceOutput['result'];
        }
    }
    
    public function viewotucouponAction() {
        $data = array(
            "type" => "coupon",
            "env" => "working",
            "query" => array("coupon.name" => $this->_request->getParam('name', ""))
        );
        $this->view->readotucoupon = $this->_hydraConnect("read", $data);
        
        $class_arr = array(0=>"Offline", 1=>"Online", 3=>"Both");
        $this->view->readotucouponclass = $class_arr[$this->view->readotucoupon[0]['coupon']['class']];
        
        $datasite = array(
            "type" => "domains",
            "env" => "live",
            "query" => array("siteId" => $this->view->readotucoupon[0]['coupon']['siteId'][0])
        );
        $this->view->readotucouponsite = $this->_hydraConnect("read", $datasite);

        $appliesto_arr = array('ORDERTOTAL'=>'Order Total', 'SUBTOTAL'=>'Sub-total', 'SHIPPING'=>'Shipping', 'TAX'=>'Tax', 'ITEM'=>'Order Line Item');
        $this->view->readotucouponappliesto = $appliesto_arr[$this->view->readotucoupon[0]['coupon']['couponAppliesTo']];
        
        $dataowner = array(
            "type" => "owners",
            "env" => "live",
            "query" => array("value" => $this->view->readotucoupon[0]['coupon']['owner'])
        );
        $this->view->readotucouponowner = $this->_hydraConnect("read", $dataowner);
        
        $databatch = array(
            "type" => "batches",
            "env" => "live",
            "query" => array("batch_id" => $this->view->readotucoupon[0]['coupon']['name'])
        );
        $this->view->readotucouponbatch = $this->_hydraConnect("read", $databatch);
//        $otuCouponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Otucoupon", "");
//        $otuCoupon = $otuCouponService->view($this->_request);
//        if(isset($otuCoupon['setToView'])){
//            $this->setToView($otuCoupon['setToView']);
//        }else{
//            $this->_buildRedirect("An error occurred", "/dcms/coupon/otucoupon", $code = Zend_Log::INFO);
//        }
        
    }

    public function otucouponAction() {
        $this->_redirect("/dcms/coupon/otubatchlist");
        //GET SITES
        $data = array(
            "type" => "domains",
            "env" => "live",
            "query" => array(),
            "sort" => array("name" => 1)
        );
        $this->view->readsites = $this->_hydraConnect("read", $data);
        //END OF GET SITES

        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);

        $request['siteId'] = $this->_request->getParam('siteId', "");
        $request['batch_id'] = $this->_request->getParam('batch_id', "");

        $type_arr = array("expiration.type" => "onetimeuse");
        $deleted = array("deleted" => array('$ne' => true));

        if ($request['siteId'] != "") {
            $siteId_arr = array("coupon.siteId.0" => $request['siteId']);
        } else {
            $siteId_arr = array();
        }

        if($this->_request->isPost()){
        	$getPost = $this->_request->getPost();
        	$batchId_arr = array( 
                "coupon.name" => strtoupper($getPost['batchId'])
        	);
        	
        	
        }else if($request['batch_id'] != ""){
        	$batchId_arr = array(
            "mongoregex" => array( 
                "coupon.name" => strtoupper($request['batch_id'])
           	 	)
        	);
        }else{
        	$batchId_arr = array();
        }
        

        $merged_arrays = array_merge($type_arr, $deleted, $siteId_arr, $batchId_arr);
        $request['query'] = $merged_arrays;

		$this->view->baseService = $this->baseService; 
        /** 
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_OtucouponList", "");
        $serviceOutput = $dcmsPaginator->getPaginator($request);

        if (!empty($serviceOutput['error'])) {
            echo $serviceOutput['message'];
            $this->view->message = !empty($serviceOutput['message']) ? $serviceOutput['message'] : null;
        } else {
            $this->view->paginator = $serviceOutput['result'];
        }
    }
    


    public function addotucouponAction() {         
        $otuCouponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Otucoupon", "");
        $otuCoupon = $otuCouponService->add($this->_request);
        if(isset($otuCoupon['setToView'])){
            $this->setToView($otuCoupon['setToView']);
        }else if(isset($otuCoupon['result'])){
            $result = $otuCoupon['result'];
            $this->_buildRedirect($result['message'], $result['path'], $result['code']);
        }
        //END OF ADD NEW OTU COUPON
    }

    public function updateotucouponAction() {
    	$otuCouponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Otucoupon", "");
        $otuCoupon = $otuCouponService->edit($this->_request);
        if(isset($otuCoupon['setToView'])){
            $this->setToView($otuCoupon['setToView']);
        }else if(isset($otuCoupon['result'])){
            $result = $otuCoupon['result'];
            $this->_buildRedirect($result['message'], $result['path'], $result['code']);
        }                        
    }

    public function otubatchlistAction() {	
        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);

        $request['batch_id'] = $this->_request->getParam('batch_id', "");
        if($request['batch_id'] != ""){
           $batchId_arr = array(
                "mongoregex" => array(
                    "coupon.name" => strtoupper($request['batch_id']),
                )
            ); 
           $request['query'] = $batchId_arr;
        }
        
        /**
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_OtubatchList", "");
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        $this->view->paginator = $serviceOutput['result'];
        if($this->_request->isPost()){
            $post = $this->_request->getPost();
            if(isset($post['delete']) && isset($post['batchId'])){
                $batchIds = $post['batchId'];
                $otuCouponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Otucoupon", "");
                $result = $otuCouponService->delete($batchIds);
                $this->_buildRedirect($result['result']['message'], $result['result']['path'], $result['result']['code']);
            } else {
                echo '<script>alert("There are no item selected");</script>';
            }
        }
    }

    # Name: Del Date: April 2016
    public function dispensebatchlistAction() {  
        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);

        $request['batch_name'] = $this->_request->getParam('batch_name', "");
        $request['date_created'] = $this->_request->getParam('date_created', "");
        $request['batch_id'] = $this->_request->getParam('batch_id', "");
        $request['sort_by'] = $this->_request->getParam('sort_by', "");
        $request['order_by'] = $this->_request->getParam('order_by', "");

        if($request['batch_name'] != "" || $request['date_created'] != "" || $request['batch_id'] != "" || $request['sort_by'] != "" || $request['order_by'] != ""){
            # Filter via batch name
            if($request['batch_name'] != ""){
                $batch_name = "coupon.name";  
                $batch_name_var = strtoupper($request['batch_name']);
            }
            else{
                $batch_name = "auto_dispense";  
                $batch_name_var = true;
            }
            # Filter via date created
            if($request['date_created'] != ""){
                //$date_created = "createdAt";
                $date_created = "dateCreated";
                $date_created_var = array(
                    '$gte' => strtotime($request['date_created']." 12:00:00 am"),
                    '$lte' => strtotime($request['date_created']." 12:59:00 pm")
                );
            }
            else{
                $date_created = "auto_dispense";
                $date_created_var = true;
            }
            # Filter via batch id
            if($request['batch_id'] != ""){    
                $batch_id = "batchId";
                $batch_id_var = strtoupper($request['batch_id']);
            }  
            else{
                $batch_id = 'auto_dispense';
                $batch_id_var = true;
            }

            $batchId_arr = array(
                $batch_name => $batch_name_var,
                $date_created => $date_created_var,
                $batch_id => $batch_id_var,
            );
            $request['query'] = $batchId_arr;
            
            if($request['order_by'] == "asc"){
                $order_by = 1;
            }
            if($request['order_by'] == "desc"){
                $order_by = -1;
            }

            $sorting_arr = array(
                $request['sort_by'] => $order_by,
            );
            $request['sorting'] = $sorting_arr;
            //var_dump($request['sorting']);
        }

        /**
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_DispenseOtubatchList", "");
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        $this->view->paginator = $serviceOutput['result'];
                
        if($this->_request->isPost()){
            $post = $this->_request->getPost();
            
            if(isset($post['delete']) && isset($post['batchId'])){
                $batchIds = $post['batchId'];
                $otuCouponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Otucoupon", "");
                $result = $otuCouponService->delete($batchIds);
                $this->_buildRedirect($result['result']['message'], $result['result']['path'], $result['result']['code']);
            } else {
                echo '<script>alert("There are no item selected");</script>';
            }
            
        }
    }

    public function exportAction(){
        $otuService  = USAP_Service_ServiceAbstract::getService("Dcms_Service_Otucoupon", "");
        $request = $this->_request->getParams();
        $serviceResult = $otuService->export($request);
        if($serviceResult['success']){
            $this->_redirect($serviceResult['path']);
        }else{
            $this->_buildRedirect($serviceResult['message'], $serviceResult['path']);
        }
        
    }
    
    
    public function ajaxAction() {
        $this->_helper->layout()->disableLayout();

        if ($_GET['action'] == 'getchannelcodestoadd') {
            $otuCouponForm = new Dcms_Form_Coupon_Addotucoupon(array());

            //GET CHANNEL CODE
            $datachannelcode = array(
                "type" => "domains",
                "env" => "live",
                "query" => array("siteId" => (isset($_GET['siteId'])) ? (int) $_GET['siteId'] : null)
            );
            $this->view->readchannelcodes = $this->_hydraConnect("read", $datachannelcode);

            $rec2 = $this->view->readchannelcodes[0]['channelCode'];
            $t2 = count($rec2);

            $channelCode = array();
            if (trim($t2)) {
                for ($i2 = 0; $i2 < $t2; $i2++) {
                    $channelCode[$rec2[$i2]] = $rec2[$i2];
                }
            } else {
                $channelCode[''] = '-NA-';
            }
            
            uksort($channelCode, 'strcasecmp');
            $otuCouponForm->getElement('channelCode')->addMultiOptions($channelCode);
            
            if(in_array(500, $channelCode)){
                $otuCouponForm->getElement('channelCode')->setValue(500);
            }
            //END OF CHANNEL CODE
            //DISPLAY FORM IN /forms/Coupons/Addotucoupon.php
            $this->view->otucouponaddform = $otuCouponForm;
            //END OF DISPLAY FORM IN /forms/Coupons/Addotucoupon.php
        } else if ($_GET['action'] == 'checkotucouponname') {
            $otucoupondata = array(
                "type" => "batches",
                "env" => "live",
                "query" => array(
                    "deleted" => array('$ne' => true),
                    "coupon.name" => strtoupper($_GET['name'])
                )
            );
            $this->view->readotucoupon = $this->_hydraConnect("read", $otucoupondata);
        }
    }

    private function _hydraConnect($method, $data) {
        $serviceObject = $this->service['sourceobject']['coupon'];
        $getResults = Hydra_Helper::loadClass(
                        $serviceObject['url'], $serviceObject['version'], $serviceObject['service'], $method, $data, $serviceObject['httpmethod'], $serviceObject['id'], $serviceObject['format']
        );
		if(is_string($getResults)){
			$this->_buildRedirect("Could not connect to server.", "/dcms/index", Zend_Log::ERR);
		}
		
        return $getResults['_payload']['result'][$method]['result']['records'];
    }
	
    private function _buildRedirect($message, $path = "/dcms/coupon/index", $code = Zend_Log::INFO){
		$this->addMessage($message, $code);
                $this->_redirect($path);
	}
    
    public function couponsAction(){
        $limit = $this->_request->getParam('limit');
        $skip = $this->_request->getParam('skip');
        $couponSearch = $this->_request->getParam('couponSearch');
        $templateId = $this->_request->getParam('templateId');
        $sort = array('coupon.name' => '1');
        if($couponSearch != ""){
            $paginator = $this->baseService->getHydraPaginator($limit, $skip, "coupon", $sort, array("mongoregex" => array('coupon.name' => $couponSearch), "templateId" => (int) $templateId), "readCouponAndBatch","live");
        }else if($templateId != ""){
            $paginator = $this->baseService->getHydraPaginator($limit, $skip, "coupon", $sort, array("templateId" => (int) $templateId), "readCouponAndBatch", "live");
        }else{
            $paginator = array();
        }
        $paginator['templateId'] = $templateId;
        $this->setToView($paginator);

    }
    
    
    public function pagesAction(){
        $this->setToView($this->_request->getParams());
    }
        

}

?>
