<?php

/**
 * @catagory USAPTool_Modules
 * @package Dcms_Controller
 * @author mardiente
 * @copyright 2011
 * @version $Id$
 */
class Dcms_ExclusionController extends USAP_Controller_Action {

    public function init() {
        parent::init();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $this->bootstrap = $bootstraps['Dcms'];
        $this->service = $this->bootstrap->getOption('service');
        $this->_baseService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Base", array());
        $this->post = $this->getRequest()->getPost();
    }

    public function listAction() {

        //GET SITES
        $data = array(
            "type"  => "domains", 
            "env" => "live", 
            "query" => array(),
            "sort" => array("name" => 1)
        );
        $this->view->readsites = $this->_hydraConnect("read", $data, true);
        $this->view->siteId = (int)$this->_request->getParam('siteId');

        if($this->_request->isPost()) {
           $post = $this->_request->getPost();
            $datasite = array(
                    "type" => "domains", 
                    "env" => "live", 
                    "query" => array("siteId" => (int) $post['siteId'])
                );
            $readsite = $this->_hydraConnect("read", $datasite, true);         
            
            if(is_string($readsite)) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/exclusion/list/siteId/' . $this->post['siteId'], 'addmessage' => "An error occured while requesting on the API."));
            } else {
                if(count($readsite) <= 0) {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/exclusion/list/siteId/' . $this->post['siteId'], 'addmessage' => "Requesting Site Id not found in Manage Site List."));
                }
            }

            $recordSite = $readsite[0];
            
            $checksite = array(
                "type"  => "exclusions", 
                "env" => "live", 
                "query" => array("siteId" => (int)$recordSite['siteId'])
            );
        
            $readchecksite = $this->_hydraConnect("read", $checksite, true);

            if(is_string($readchecksite)) {
                $this->_buildRedirectAndMessage(array('redirect' => '/dcms/exclusion/list/siteId/' . $this->post['siteId'], 'addmessage' => "An error occured while requesting on the API."));
            }

                   
            $submittedData = array(
                "siteId"    => (int)$recordSite['siteId'],
                "name"      => $recordSite['name'],
                "createdAt" => (isset($readchecksite[0]['createdAt'])) ? $readchecksite[0]['createdAt']['sec'] : time(),
                "updatedAt" => time(),
                "brands"    => (count($this->post['restriction_brand_selected']) > 0) ? $this->post['restriction_brand_selected'] : array(),
            );
            $brands = $this->_baseService->arrayRecursiveEncode($submittedData);
            
            $data = array(
                "type" => "exclusions", 
                "env" => "live", 
                "query" => array(
                    array(
                        "set" => $brands,
                        "where" => array(
                            $this->_baseService->baseEncoding("siteId") => (int)$recordSite['siteId']
                        )
                    )
                ),
                "decode" => true
            );
            $hydraUpsertResult = $this->_hydraConnect("update", $data);

            if(is_string($hydraUpsertResult)) {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/exclusion/list/siteId/' . $this->post['siteId'], 'addmessage' => "An error occured while requesting on the API."));
            } else {
                if (!is_string($hydraUpsertResult) && isset($hydraUpsertResult['error']['database_result'])) {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/exclusion/list/siteId/' . $this->post['siteId'], 'addmessage' => $hydraUpsertResult['error']['database_result']));
                } elseif(isset($hydraUpsertResult['error']['message'])) { 
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/exclusion/list/siteId/' . $this->post['siteId'], 'addmessage' => $hydraUpsertResult['error']['message']));
                } else {
                    $this->_buildRedirectAndMessage(array('redirect' => '/dcms/exclusion/list/siteId/' . $this->post['siteId'], 'addmessage' => $hydraUpsertResult['result']['database_result']));
                }
            }
        }
    }
    
    public function exclusionlistAction() {

        $this->_disableLayout();
         
        $datacurrentsite = array(
                "type"  => "exclusions", 
                "env" => "live", 
                "query" => array("siteId" => (int) $this->post['site_id'])
            );
        
        $readcurrentsite = $this->_hydraConnect("read", $datacurrentsite, true);
        $this->view->readcurrentsite = $readcurrentsite;
    }
    
    public function existsearchAction() {
        
        $this->_disableLayout();
        
        $templateService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Template", array());
        $params = $templateService->listing($this->post);
        
        $params['type'] = 'mysql';
        $params['restriction'] = 'brandsexact';
        $params['pagesize'] = 1;
        $params['offset'] = "0";
        
        $brandlist = array();
        $brandlist = $templateService->getResults($params, 'brand_name');

        if(is_string($brandlist)) {
            $this->view->brandlist = "An error occured while requesting on the API.";
        } else {
            if(isset($brandlist['records']['record_count']) && $brandlist['records']['record_count'] > 0) {
                $this->view->brandlist = "The brand <b>" . $this->post['post'] . "</b> is already in the brandlist.<br />You can search and/or select that brand on the list.";
            } else {
                $this->view->brandlist = "";
            }
        }
    }
    
    private function _disableLayout() {

        $this->_helper->layout->disableLayout();
    }    
    
    private function _buildRedirectAndMessage($result) {

        (isset($result['addmessage'])) ? $this->addMessage($result['addmessage']) : '';
        (isset($result['redirect'])) ? $this->_redirect($result['redirect']) : '';
    } 
    
    public function indexAction() {
        
        $this->_forward('list');
        
//        $this->view->readsite = $this->_request->getParam('siteId', "");
//        
//        //GET SITES
//        $data = array(
//            "type"  => "domains", 
//            "env" => "live", 
//            "query" => array(),
//            "sort" => array("name" => 1)
//        );
//        $this->view->readsites = $this->_hydraConnect("read", $data, true);
//        //END OF GET SITES
//        
//        //ADD EXCLUSION(S)
//        if(isset($_POST['exclusion_save'])){
//            $dateupdated = new MongoDate();
//            
//            if(isset($_POST['brands'])){
//                $te = count($_POST['brands']);
//                
//                if($te){
//                    for($ic=0; $ic<$te; $ic++){
//                        if($_POST['brands'][$ic]!=""){
//                            $brands[] = $_POST['brands'][$ic];
//                        }
//                    }
//                }
//            }else{
//                $brands = array();
//            }
//            
//            $submittedData = array(
//                "updatedAt" => $dateupdated, 
//                "brands"    => $brands
//            );
//            
//            $brands = $this->_baseService->arrayRecursiveEncode($submittedData);
//            
//            $data = array(
//                "type" => "exclusions", 
//                "env" => "live", 
//                "query" => array(
//                    array(
//                        "set" => $brands,
//                        "where" => array(
//                            $this->_baseService->baseEncoding("siteId") => $this->_baseService->baseEncoding($_POST['siteId'])
//                        )
//                    )
//                ),
//                "decode" => true
//            );
//            $this->_hydraConnect("update", $data);
//            
//            $this->_redirect('dcms/exclusion/index/siteId/'.$_POST['siteId']);
//        }
        //END OF ADD EXCLUSION(S)
    }
    
    public function ajaxAction(){
//        $this->_baseService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Base", "");
        $this->_helper->layout()->disableLayout();

        if($_GET['action']=='getexclusions' && isset($_POST['siteId'])){
            $siteId = $_POST['siteId'];
            
            //GET SELECTED SITE NAME
            $datacurrentsite = array(
                "type"  => "domains", 
                "env" => "live", 
                "query" => array("siteId" => $siteId)
            );
            $this->view->readcurrentsite = $this->_hydraConnect("read", $datacurrentsite, true);
            //END OF GET SELECTED SITE NAME
            
            //GET BRANDS OF SITES
            $databrandssites = array(
                "type"  => "exclusions", 
                "env" => "live", 
                "query" => array("siteId" => $siteId)
            );
            $this->view->readbrandssites = $this->_hydraConnect("read", $databrandssites, true);
            //END OF GET BRANDS OF SITES
            
            //GET ALL BRANDS
            $databrands = array(
                "type" => "mysql", 
                "env" => "live",
                "restriction" => "brands",
                "post" => (isset($_GET['brand_name'])) ? $this->_baseService->baseEncoding($_GET['brand_name']) : "nodata"
            );
            $this->view->readbrands = $this->_hydraConnect("read", $databrands, true);
            //END OF GET ALL BRANDS
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
        
//        if($data['type']=="mysql"){
//            return $getResults['_payload']['result'][$method];
//        }else{
//            return $getResults['_payload']['result'][$method]['result']['records'];
//        }      
        if($readRequest) {
            if($data['type']=="mysql"){

                if(!isset($getResults['_payload']['result'][$method])) {
                    return "error";
                } else {
                    return $getResults['_payload']['result'][$method];
                }
            } else {
                if(!isset($getResults['_payload']['result'][$method]['result']['records'])) {
                    return "error";
                } else {
                    return $getResults['_payload']['result'][$method]['result']['records'];
                }       
            }
        } else {
            if(!isset($getResults['_payload']['result'][$method])) {
                return "error";
            } else {
                return $getResults['_payload']['result'][$method];
            }    
        }        
        
        
        
        
    }

}
