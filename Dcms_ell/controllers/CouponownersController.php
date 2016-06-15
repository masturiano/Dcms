<?php

/**
 * @catagory USAPTool_Modules
 * @package Dcms_Controller
 * @author mardiente
 * @copyright 2011
 * @version $Id$
 */
class Dcms_CouponownersController extends USAP_Controller_Action {

    public function init() {
        parent::init();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $this->bootstrap = $bootstraps['Dcms'];
        $this->service = $this->bootstrap->getOption('service');
    }

    public function indexAction() {
        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);
        $request['query'] = array();

        /**
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_CouponownerList", array());
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        if (!empty($serviceOutput['error'])) {
            echo $serviceOutput['message'];
            $this->view->message = !empty($serviceOutput['message']) ? $serviceOutput['message'] : null;
        } else {
            $this->view->paginator = $serviceOutput['result'];
        }
    }
    
     public function viewAction() {
        $request = array();
        $request['countperpage'] = $this->_request->getParam('countperpage', 10);
        $request['page'] = $this->_request->getParam('page', 1);
        $request['query'] = array();
        
        /**
         * Initialize DCMS paginator
         */
        $this->view->request = $request;
        $this->view->readonly = true;
        $dcmsPaginator = USAP_Service_ServiceAbstract::getService("Dcms_Service_CouponownerList", array());
        $serviceOutput = $dcmsPaginator->getPaginator($request);
        if (!empty($serviceOutput['error'])) {
            echo $serviceOutput['message'];
            $this->view->message = !empty($serviceOutput['message']) ? $serviceOutput['message'] : null;
        } else {
            $this->view->paginator = $serviceOutput['result'];
        }
    }
    
    public function addownerAction() {
        $ownerForm = new Dcms_Form_Couponowners_Add(array());
        
        if($this->_request->isPost()) {
            $posts = $this->_request->getPost();
            if($ownerForm->isValid($posts)) {
                $owner = $ownerForm->getValue('owner');
                $value     = str_replace('-', '_', str_replace(' ', '_', strtolower($owner)));
                $creator   = Zend_Registry::get('user')->getUsername();                
                
                $data = array(
                    "type" => "owners", 
                    "env" => "live", 
                    "query" => array(
                        array(
                            "owner"     => $owner, 
                            "value"     => $value, 
                            "createdAt" => time(),
                            "updatedAt" => time(), 
                            "creator"   => $creator
                        )
                    )
                );

                $resultHydra = $this->_hydraConnect("create", $data);

                if(is_string($resultHydra)) {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "An error occured while requesting on the API."));
                } else {
                    if (!is_string($resultHydra) && isset($resultHydra['error']['database_result'])) {
                        $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => $resultHydra['error']['database_result']));
                    } elseif(isset($resultHydra['error']['message'])) { 
                        $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => $resultHydra['error']['message']));
                    } else {
                        $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "Owner succesfully saved."));
                    }
                }
            } else {
                $this->view->owneraddform = $ownerForm;
            }
        } else {
            $this->view->owneraddform = $ownerForm;
        }
    }
    
    public function updateownerAction() {
        $data = array(
            "type" => "owners", 
            "env" => "live", 
            "query" => array("_id" => $this->_request->getParam('id'))
        );
        
        $readowner = $this->_hydraConnect("read", $data, true);
        
        if(is_string($readowner)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {
            if(count($readowner) <= 0) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "Owner not found."));
            }
        }

        $rec = $readowner[0];
        
        $existing = array('owner' => $rec['owner']);
        
        $ownerForm = new Dcms_Form_Couponowners_Update();
        $ownerForm->populateForm($existing);
        $this->view->ownerupdateform = $ownerForm;
        
        if($this->_request->isPost()) {
            $posts = $this->_request->getPost();
            if($ownerForm->isValid($posts)) {
                $owner = $ownerForm->getValue('owner');
                $value     = str_replace('-', '_', str_replace(' ', '_', strtolower($owner)));
                $creator   = Zend_Registry::get('user')->getUsername();
                
                $dataowner = array(
                    "type" => "owners", 
                    "env" => "live", 
                    "query" => array(
                        array(
                            "set" => array(
                                "owner"     => $owner, 
                                "value"     => $value, 
                                /*"createdAt" => $rec['createdAt'],*/
                                "updatedAt" => time(), 
                                "creator"   => $creator
                            ),
                            "where" => array(
                                "_id" => $rec['id']
                            )
                        )
                    )
                );
                $resultHydra = $this->_hydraConnect("update", $dataowner);
                if(is_string($resultHydra)) {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "An error occured while requesting on the API."));
                } else {
                    if (!is_string($resultHydra) && isset($resultHydra['error']['database_result'])) {
                        $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => $resultHydra['error']['database_result']));
                    } elseif(isset($resultHydra['error']['message'])) { 
                        $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => $resultHydra['error']['message']));
                    } else {
                        $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "Owner successfully updated."));
                    }
                }             
                
            } else {
                $this->view->owneraddform = $ownerForm;
            }
        } else {
            $this->view->owneraddform = $ownerForm;
        }
    }
    
    public function deleteownerAction() {
        
        $dataSearch = array(
            "type" => "coupon", 
            "env" => "live", 
            "query" => array("coupon.owner" => $this->_request->getParam('id'))
        );        
        
        $searchResult = $this->_hydraConnect("read", $dataSearch, true);

        if(is_string($searchResult)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {        
            if(count($searchResult) > 0) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "You cannot delete this owner. One or more coupons are using this owner."));
            }
        }
        
        $dataWorkingSearch = array(
            "type" => "coupon", 
            "env" => "working", 
            "query" => array("coupon.owner" => $this->_request->getParam('id'))
        );
        
        $searchWorkingResult = $this->_hydraConnect("read", $dataWorkingSearch, true);
        
        if(is_string($searchWorkingResult)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {        
            if(count($searchWorkingResult) > 0) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "You cannot delete this owner. One or more coupons are using this owner."));
            }
        }
        
        $dataOwner = array(
            "type" => "owners", 
            "env" => "live", 
            "query" => array("_id" => $this->_request->getParam('id'))
        );
        
        $searchIdHydra = $this->_hydraConnect("read", $dataOwner, true);
        
        if(is_string($searchIdHydra)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {        
            if(count($searchIdHydra) <= 0) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "Invalid owner id."));
            }
        }        
        
        $resultHydra = $this->_hydraConnect("delete", $dataOwner);
        
        if(is_string($resultHydra)) {
            $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "An error occured while requesting on the API."));
        } else {
            if (!is_string($resultHydra) && isset($resultHydra['error']['database_result'])) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => $resultHydra['error']['database_result']));
            } elseif(isset($resultHydra['error']['message'])) { 
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => $resultHydra['error']['message']));
            } else {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/couponowners/index', 'addmessage' => "Owner successfully deleted."));
            }
        }
    }
    
    public function ajaxAction(){
        $this->_helper->layout()->disableLayout();

        if($_GET['action']=='checkowner'){
            $data = array(
                "type" => "owners", 
                "env" => "live", 
                "query" => array("value" => str_replace('-', '_', str_replace(' ', '_', strtolower(trim($_GET['owner'])))))
            );
            $this->view->readowner = $this->_hydraConnect("read", $data, true);
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
        
//        return $getResults['_payload']['result'][$method]['result']['records'];
    }
    
    private function _buildRedirectAndMessage($result) {

        (isset($result['addmessage'])) ? $this->addMessage($result['addmessage']) : '';
        (isset($result['redirect'])) ? $this->_redirect($result['redirect']) : '';
    }    
    
}