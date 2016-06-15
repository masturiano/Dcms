<?php

/**
 * @catagory USAPTool_Modules
 * @package Dcms_Controller
 * @author bteves
 * @copyright 2011
 * @version $Id$
 */
class Dcms_TemplateController extends USAP_Controller_Action {

    protected $_templateService;
	protected $_baseService;

    public function init() {
        parent::init();
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('searchtemplate', 'json')
                ->addActionContext('options', 'html')
                ->addActionContext('pages', 'html')
                ->initContext();
        $this->_templateService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Template", array());
		$this->_baseService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Base", array());
        $this->post = $this->getRequest()->getPost();
    }

    public function indexAction() {
        $this->_forward('list');
    }

    public function listAction() {
        $result = $this->_templateService->displayTemplateList($this->_request);
        $this->_setToView($result); 
        $this->_buildRedirectAndMessage($result);
    }

    public function addAction() {

        $result = $this->_templateService->addForm($this->_request);
        $this->_setToView($result);
        $this->_buildRedirectAndMessage($result);
    }

    public function adduploadAction() {
        $result = $this->_templateService->addFormUpload($this->_request);
        $this->_setToView($result);
        $this->_buildRedirectAndMessage($result);
    }

    public function editAction() {

        $result = $this->_templateService->editForm($this->_request);
        $this->_setToView($result);
        $this->_buildRedirectAndMessage($result);
    }

    public function deleteAction() {

        $result = $this->_templateService->deleteTemplate($this->_request);
        $this->_setToView($result);
        $this->_buildRedirectAndMessage($result);
    }

    public function viewAction() {

        $this->_disableLayout();
        $result = $this->_templateService->viewTemplate($this->_request);
        $this->_setToView($result);
        $this->_buildRedirectAndMessage($result);
    }

    /**
     * This method will list all parts
     */
    public function partlistAction() {

        $this->_disableLayout();
        $params = $this->_templateService->listing($this->post);
        $params['type'] = 'mysql';
        $params['restriction'] = 'parts';
        $this->view->issearch = $params['issearch'];
        $this->view->element = $this->post['element'];
        $this->view->partslist = $this->_templateService->getResults($params, 'part_name');
    }

    /**
     * This method will list all parts based on the post brandId
     */
    public function brandpartlistAction() {

        $this->_disableLayout();
        $params = $this->_templateService->listing($this->post);
        $params['type'] = 'mysql';
        $params['restriction'] = 'partbrand';
        $this->view->issearch = $params['issearch'];
        $this->view->element = $this->post['element'];
        $this->view->brandpartslist = $this->_templateService->getResults($params, 'part_name', 'brandpart');
    }

    /**
     * This method will list all brands based on the post partId
     */
    public function partbrandlistAction() {

        $this->_disableLayout();
        $params = $this->_templateService->listing($this->post);
        $params['type'] = 'mysql';
        $params['restriction'] = 'brandpart';
        $this->view->issearch = $params['issearch'];
        $this->view->element = $this->post['element'];
        $this->view->partbrandslist = $this->_templateService->getResults($params, 'brand_name', 'partbrand');
    }

    /**
     * This method will list all brands
     */
    public function brandlistAction() {

        $this->_disableLayout();
        $params = $this->_templateService->listing($this->post);
        $this->view->post = $params['post'];
        $params['type'] = 'mysql';
        $params['restriction'] = 'brands';
        $this->view->issearch = $params['issearch'];
        $this->view->element = $this->post['element'];
        $this->view->brandlist = $this->_templateService->getResults($params, 'brand_name');
    }

    /**
     * This method will list all skus from USAP, JCW, and STT based on brandId
     */
    public function brandskulistAction() {

        $this->_disableLayout();
        $params = $this->_templateService->listing($this->post);
        $this->view->post = $params['post'];
        $params['type'] = 'mysql';
        $params['restriction'] = 'skus';
        $this->view->issearch = $params['issearch'];
        $this->view->element = $this->post['element'];
        $finalResult = $this->_templateService->getResults($params, '', null, true);
        $this->view->siteSkus = (isset($finalResult['skussites'])) ? $finalResult['skussites'] : null;
        $this->view->brandskuslist = (isset($finalResult['results'])) ? $finalResult['results'] : $finalResult;
    }

    private function _disableLayout() {

        $this->_helper->layout->disableLayout();
    }

    private function _setToView($input) {

        foreach ($input as $keyInput => $valueInput) {
            $this->view->{$keyInput} = $valueInput;
        }
    }

    private function _buildRedirectAndMessage($result) {

        (isset($result['addmessage'])) ? $this->addMessage($result['addmessage']) : '';
        (isset($result['redirect'])) ? $this->_redirect($result['redirect']) : '';
    }
	
    public function searchtemplateAction(){
        unset($this->view->user);
        unset($this->view->alltoollist);
        
	$field = $this->_getParam('field');
        $value =  $this->_getParam('value');
        
	$query = array(
            "$field" => (int) trim($value)
        );
        $record = $this->_baseService->hydraConnect($this->_baseService->queryIfDeleted($query), 'template');
        $this->view->record = $record['result']['record_count'];
    }  
    
    public function optionsAction(){
        $limit = $this->_request->getParam('limit');
        $skip = $this->_request->getParam('skip');
        $templateSearch = $this->_request->getParam('templateSearch');
        $templateName = $this->_request->getParam('templateName');
        $templateId = $this->_request->getParam('templateId');
        $sort = array('templateName' => '1');
        if($templateSearch != ""){
            $paginator = $this->_baseService->getHydraPaginator($limit, $skip, "template", $sort, array("mongoregex" => array('templateName' => $templateSearch)));
        }else{
            $paginator = $this->_baseService->getHydraPaginator($limit, $skip, "template", $sort);
        }
        $paginator['templateName'] = $templateName;
        $paginator['templateId'] = $templateId;
        $this->_setToView($paginator);

    }
    
    
    public function pagesAction(){
        $this->_setToView($this->_request->getParams());
    }
    
}


?>