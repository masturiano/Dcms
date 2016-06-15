<?php

/**
 * @catagory USAPTool_Modules
 * @package Dcms_Controller
 * @author mardiente
 * @copyright 2011
 * @version $Id$
 */
class Dcms_SitesController extends USAP_Controller_Action {

    public function init() {
        parent::init();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $this->bootstrap = $bootstraps['Dcms'];
        $this->service = $this->bootstrap->getOption('service');
        $this->baseService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Base", array());
    }

    public function indexAction() {
                
        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);
        
        $request['domainName'] = $this->_request->getParam('domainName', "");

        $domainName = array(
            "mongoregex" => array(
                "name" => "{$request['domainName']}"
            )
        );
        
        $request['query'] = $domainName;

        /**
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_DomainList", array());
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        if (!empty($serviceOutput['error'])) {
            echo $serviceOutput['message'];
            $this->view->message = !empty($serviceOutput['message']) ? $serviceOutput['message'] : null;
        } else {
            $this->view->paginator = $serviceOutput['result'];
        }
    }
    
    public function addsiteAction() {
        //DISPLAY FORM IN /forms/Sites/Add.php
        $siteForm = new Dcms_Form_Sites_Add(array());
        //END OF DISPLAY FORM IN /forms/Sites/Add.php
        
        //SAVE SITE
        if($this->_request->isPost()) {

            $name        = strtolower($_POST['name']);
            $channelCode = $_POST['channelCode'];
            $serverName  = $_POST['serverName'];
            $acronym     = strtoupper($_POST['acronym']);
            $createdAt   = time();
            $updatedAt   = time();
            $creator     = Zend_Registry::get('user')->getUsername();
            
            $isUsapSite = false;
            if($_POST['isUsapSite']==1){
                $isUsapSite = true;
            }            
            
            $siteId =  $this->baseService->getUniqueKey();
            if(is_string($siteId)) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "An error occured while requesting on the API"));
            }
            
            $data = array(
                "type" => "domains", 
                "env" => "live", 
                "query" => array(
                    array(
                        "siteId"      => (int)$siteId, 
                        "name"        => $name, 
                        "channelCode" => $channelCode, 
                        "serverName"  => $serverName, 
                        "acronym"     => $acronym, 
                        "isUsapSite"  => $isUsapSite, 
                        "createdAt"   => $createdAt,
                        "updatedAt"   => $updatedAt, 
                        "creator"     => $creator
                    )
                )
            );
            $resultHydra = $this->_hydraConnect("create", $data);
            
            if(is_string($resultHydra)) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "An error occured while requesting on the API."));
            } else {
                if (!is_string($resultHydra) && isset($resultHydra['error']['database_result'])) {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => $resultHydra['error']['database_result']));
                } elseif(isset($resultHydra['error']['message'])) { 
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => $resultHydra['error']['message']));
                } else {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "Site succesfully saved."));
                }
            }
        } else {
            $this->view->domainaddform = $siteForm;
        }
    }
    
    public function updatesiteAction() {
        //GET EXISTING SITE
        $data = array(
            "type" => "domains", 
            "env" => "live", 
            "query" => array("siteId" => (int)trim($this->_request->getParam('siteId')))
        );
        $readsite = $this->_hydraConnect("read", $data, true);

        if(is_string($readsite)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => $readsite));
        }
        
        if(!isset($readsite[0])) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => 'Record not found'));
        }
        
        $this->view->readsite = $readsite;
        
        $rec = $readsite[0];
        
        $existing = array(
            'siteId'      => (int)$rec['siteId'], 
            'name'        => $rec['name'], 
            'channelCode' => $rec['channelCode'], 
            'serverName'  => $rec['serverName'], 
            'acronym'     => $rec['acronym'],
            'isUsapSite'  => $rec['isUsapSite']
        );
        //END OF GET EXISTING SITE
        
        //DISPLAY FORM IN /forms/Sites/Update.php
        $siteForm = new Dcms_Form_Sites_Update();
        $siteForm->populateForm($existing);
        $this->view->domainupdateform = $siteForm;
        //END OF DISPLAY FORM IN /forms/Sites/Update.php
        
        //UPDATE SITE
        if(isset($_POST['domain_update'])){
            $siteId      = (int)$rec['siteId'];
            $name        = strtolower($_POST['name']);
            $channelCode = $_POST['channelCode'];
            $serverName  = $_POST['serverName'];
            $acronym     = $this->baseService->lettersOnly($_POST['acronym']);
            $acronym     = strtoupper($_POST['acronym']);
            /*$createdAt   = $rec['createdAt'];*/
            $updatedAt   = time();
            $creator     = Zend_Registry::get('user')->getUsername();
                        
            $isUsapSite = false;
            if($_POST['isUsapSite']==1){
                $isUsapSite = true;
            }

            $data = array(
                "type" => "domains", 
                "env" => "live", 
                "query" => array(
                    array(
                        "set" => array(
                            "siteId"      => $siteId, 
                            "name"        => $name, 
                            "channelCode" => $channelCode, 
                            "serverName"  => $serverName, 
                            "acronym"     => $acronym, 
                            "isUsapSite"  => $isUsapSite, 
                            /*"createdAt"   => $createdAt,*/
                            "updatedAt"   => $updatedAt, 
                            "creator"     => $creator
                        ),
                        "where" => array(
                            "siteId" => $siteId
                        )
                    )
                )
            );
            $resultHydra = $this->_hydraConnect("update", $data);

            if(is_string($resultHydra)) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "An error occured while requesting on the API."));
            } else {
                if (!is_string($resultHydra) && isset($resultHydra['error']['database_result'])) {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => $resultHydra['error']['database_result']));
                } elseif(isset($resultHydra['error']['message'])) { 
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => $resultHydra['error']['message']));
                } else {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "Site successfully updated."));
                }
            }            
        }
    }
    
    public function deletesiteAction() {
        
        $dataSearch = array(
            "type" => "coupon", 
            "env" => "live", 
            "query" => array("coupon.siteId" => (int)$this->_request->getParam('siteId'))
        );
        $searchResult = $this->_hydraConnect("read", $dataSearch, true);

        if(is_string($searchResult)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {        
            if(count($searchResult) > 0) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "You cannot delete this site. One or more coupons are using this site."));
            }
        }
        
        $dataWorkingSearch = array(
            "type" => "coupon", 
            "env" => "working", 
            "query" => array("coupon.siteId" => (int)$this->_request->getParam('siteId'))
        );
        $searchWorkingResult = $this->_hydraConnect("read", $dataWorkingSearch, true);

        if(is_string($searchWorkingResult)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {        
            if(count($searchWorkingResult) > 0) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "You cannot delete this site. One or more coupons are using this site."));
            }
        }
        
        $dataSite = array(
            "type" => "domains", 
            "env" => "live", 
            "query" => array("siteId" => (int)$this->_request->getParam('siteId'))
        );
        
        $searchIdHydra = $this->_hydraConnect("read", $dataSite, true);
        
        if(is_string($searchIdHydra)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {        
            if(count($searchIdHydra) <= 0) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "Invalid site id."));
            }
        }
        
        $resultHydra = $this->_hydraConnect("delete", $dataSite);
        
        if(is_string($resultHydra)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {
            if (!is_string($resultHydra) && isset($resultHydra['error']['database_result'])) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => $resultHydra['error']['database_result']));
            } elseif(isset($resultHydra['error']['message'])) { 
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => $resultHydra['error']['message']));
            } else {
                $exclusiondata = array(
                    "type" => "exclusions", 
                    "env" => "live", 
                    "query" => array("siteId" => $this->_request->getParam('siteId'))
                );
                $this->_hydraConnect("delete", $exclusiondata);
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/sites/index', 'addmessage' => "Site successfully deleted."));
            }
        }
    }
    
    public function ajaxAction(){
        $this->_helper->layout()->disableLayout();

        if($_GET['action']=='checkdomain'){
            $dataname = array(
                "type" => "domains", 
                "env" => "live", 
                "query"  => array("name" => strtolower(trim($_GET['name'])))
            );
            $this->view->readdomain = $this->_hydraConnect("read", $dataname, true);
        }
        
        if($_GET['action']=='checkchannelcode'){
            $t = count($_GET['channelCode']);
            
            if($t){
                for($i=0; $i<$t; $i++){
                    $datachannelcode = array(
                        "type" => "domains", 
                        "env" => "live", 
                        "query"  => array("channelCode" => $_GET['channelCode'][$i])
                    );
                    $readchannelcode = $this->_hydraConnect("read", $datachannelcode, true);
                    
                    $t2 = count($readchannelcode);
                    
                    if($t2){
                        $this->view->readchannelcode = $_GET['channelCode'][$i];
                        
                        break;
                    }
                }
            }
        }
        
        if($_GET['action']=='checkchannelcodeUPDATE'){
            $name = trim($_GET['name']);
            $t = count($_GET['channelCode']);
            
            if($t){
                for($i=0; $i<$t; $i++){
                    $datachannelcode = array(
                        "type" => "domains", 
                        "env" => "live", 
                        "query"  => array(
                            "name" => $name,
                            "channelCode" => $_GET['channelCode'][$i]
                        )
                    );
                    $readchannelcode = $this->_hydraConnect("read", $datachannelcode, true);
                    $t2 = count($readchannelcode);
                    
                    if(!$t2){
                        $datachannelcodex = array(
                            "type" => "domains", 
                            "env" => "live", 
                            "query"  => array("channelCode" => $_GET['channelCode'][$i])
                        );
                        $readchannelcodex = $this->_hydraConnect("read", $datachannelcodex, true);
                        
                        $tx = count($readchannelcodex);
                        
                        if($tx){
                            $this->view->readchannelcode = $_GET['channelCode'][$i];

                            break;
                        }
                    }
                }
            }
        }
        
        if($_GET['action']=='checkservername'){
            $t3 = count($_GET['serverName']);           
            if($t3){
                for($i3=0; $i3<$t3; $i3++){
                    $dataservername = array(
                        "type" => "domains", 
                        "env" => "live", 
                        "query"  => array(
                            "serverName" => $_GET['serverName'][$i3]
                        )
                    );
                    $readservername = $this->_hydraConnect("read", $dataservername, true);
                    
                    $t4 = count($readservername);
                    
                    if($t4){
                        $this->view->readservername = $_GET['serverName'][$i3];
                        
                        break;
                    }
                }
            }
        }
        
        if($_GET['action']=='checkservernameUPDATE'){
            $name = trim($_GET['name']);
            $t3 = count($_GET['serverName']);
            
            if($t3){
                for($i3=0; $i3<$t3; $i3++){
                    $dataservername = array(
                        "type" => "domains", 
                        "env" => "live", 
                        "query"  => array(
                            "name" => $name,
                            "serverName" => $_GET['serverName'][$i3]
                        )
                    );
                    $readservername = $this->_hydraConnect("read", $dataservername, true);
                    
                    $t4 = count($readservername);
                    
                    if(!$t4){
                        $dataservernamey = array(
                            "type" => "domains", 
                            "env" => "live", 
                            "query"  => array("serverName" => $_GET['serverName'][$i3])
                        );
                        $readservernamey = $this->_hydraConnect("read", $dataservernamey, true);

                        $ty = count($readservernamey);

                        if($ty){
                            $this->view->readservername = $_GET['serverName'][$i3];

                            break;
                        }
                    }
                }
            }
        }

        if($_GET['action']=='checkacronym'){
            $dataacronym = array(
                "type" => "domains", 
                "env" => "live", 
                "query"  => array("acronym" => strtoupper(trim($_GET['acronym'])))
            );
            
            $this->view->readacronym = $this->_hydraConnect("read", $dataacronym, true);
        }
    }
    
    private function _hydraConnect($method, $data, $readRequest = false) {
        $serviceObject = $this->service['sourceobject']['coupon'];
        $getResults = Hydra_Helper::loadClass(
            $serviceObject['url'], 
            $serviceObject['version'], 
            $serviceObject['service'], 
            $method, 
            $data, 
            $serviceObject['httpmethod'], 
            $serviceObject['id'], 
            $serviceObject['format']
        );

        if($readRequest) {
            if(!isset($getResults['_payload']['result'][$method]['result']['records'])) {
                return "error";
            } else {
                return $getResults['_payload']['result'][$method]['result']['records'];
            }            
        } else {
            if(!isset($getResults['_payload']['result'][$method])) {
                return "error";
            } else {
                return $getResults['_payload']['result'][$method];
            }    
        }
        //$getResults['_payload']['result'][$method]['result']['records'];
    }
    
    private function _buildRedirectAndMessage($result) {

        (isset($result['addmessage'])) ? $this->addMessage($result['addmessage']) : '';
        (isset($result['redirect'])) ? $this->_redirect($result['redirect']) : '';
    }
   
}
