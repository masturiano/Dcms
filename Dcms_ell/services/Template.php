<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author bteves
 * @version $Id$
 */
class Dcms_Service_Template extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $config;

    /**
     * 
     * @var Base Service Object
     */
    protected $baseService;

    /**
     * Selected Restrictions
     * @var array
     */
    protected $selected = array();

    /**
     * Flag if there are restrictions selected
     * @var boolean
     */
    protected $hasSelectedRestriction = false;

    /**
     * Username
     * @var string
     */
    protected $user;

    /**
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config, $baseService) {
        $this->config = $config;
        $this->baseService = $baseService;
        $user = $this->baseService->getRegistryAclUser();
        $this->user = $user['user']->getUserName();
    }

    public function displayTemplateList($httpRequest) {
        $request = $httpRequest->getParams();
        $request['countperpage'] = isset($request['countperpage']) ? $request['countperpage'] : 10;
        $request['page'] = isset($request['page']) ? $request['page'] : 1;
        $config = $this->config->service->toArray();
        $hydraService = $config['sourceobject']['coupon'];
        $hydraService['data'] = array(
            'type' => "template",
            'env' => "working"
        );
        $form = $this->getSearchForm();
        
        if(isset($request['template_name'])){
            $hydraService['data']['query']['mongoregex'] = array('templateName' => $request['template_name']);
            $form->getElement("template_name")->setValue($request['template_name']);
        }else{
            $hydraService['data']['query'] = $this->baseService->queryIfDeleted(array());
        }
        
        
        
         /* * *
         * set query parameters like page, advance query, site selected, count per page
         */
        if ($httpRequest->isPost()) {
            $post = $httpRequest->getPost();
            $form->getElement("template_name")->setValue($post['template_name']);
            $hydraService['data']['query']['mongoregex'] = array('templateName' => $post['template_name']);
            $request['template_name'] = $post['template_name'];
            $request['page'] = 1;
        } 
        
        
        $return['request'] = $request;
        $hydraService['method'] = "read";
        $return['paginator'] = $this->baseService->getPaginator($hydraService, $request);
        $return['form'] = $form;
        return $return;
    }
    
    public function getSearchForm(){
        $form = new Dcms_Form_Template_Search();
        return $form;
    }

    
    public function getTemplateForm() {

        return new Dcms_Form_Template_Addedit();
    }

    # Name: Del Date: Jun 2016
    public function getTemplateFormUpload() {

        return new Dcms_Form_Template_Addeditupload();
    }

    public function processParamId($id) {

        $param['query'] = array("_id" => $id);
        $param['type'] = 'template';
        $result = $this->_hydraConnect($param);
        return $result;
    }

    public function processParamTemplateId($templateId) {

        $param['query'] = array("templateId" => (int)$templateId);
        $param['type'] = 'template';
        $result = $this->_hydraConnect($param);
        return $result;
    }

    public function viewTemplate($request) {

        $id = $request->getParam('id');
        $templateId = $request->getParam('tid');
        if (isset($id) || isset($templateId)) {
            if (isset($id)) {
                $result = $this->processParamId($id);
            } elseif (isset($templateId)) {
                $result = $this->processParamTemplateId($templateId);
            }

            if (!is_string($result)) {
                if ($result['records']['result']['record_count'] == 1) {
                    $data = $result['records']['result']['records'][0];
                    return array(
                        'data' => $data
                    );
                } else {
                    return array(
                        'addmessage' => "Invalid Template Id."
                    );
                }
            } else {
                return array(
                    'addmessage' => $result
                );
            }
        } else {
            return array(
                'addmessage' => "Template Id not found."
            );
        }
    }

    public function editForm($request) {

        $templateForm = $this->getTemplateForm();
        $id = $request->getParam('id');

        if (isset($id)) {

            $result = $this->processParamId($id);

            if (!is_string($result)) {
                if ($result['records']['result']['record_count'] == 1) {
                    $data = $result['records']['result']['records'][0];
                    $templateId = (int) $data['templateId'];
                    if ($request->isPost()) {

                        $posts = $request->getPost();
                        $templateForm->buildForm();

                        if ($templateForm->isValid($posts)) {

                            $templateName = $templateForm->getValue('template_name');
                            $checkParam['type'] = 'template';
                            $checkParam['query'] = $this->baseService->queryIfDeleted(array("templateName" => $templateName, "neid" => $id));
                            $checkResult = $this->_hydraConnect($checkParam);

                            if (isset($checkResult['records']['result'])) {
                                if ($checkResult['records']['result']['record_count'] > 0) {
                                    $repopulatedForm = $this->_populateFormData($request, $templateForm);
                                    return array(
                                        'form' => $repopulatedForm,
                                        'addmessage' => 'Template name already exists.'
                                    );
                                }
                            } else {
                                return array(
                                    'addmessage' => "An error occured while proccessing your request.",
                                    'redirect' => "/dcms/template"
                                );
                            }

                            $param['type'] = 'template';
                            $param['decode'] = true;
//                            if ($data['templateName'] != $templateName) {
//                                $newRecord = $this->_buildRecord($templateForm, true);
//                                $templateId =  $this->baseService->getUniqueKey();
//                                if(is_string($templateId)) {
//                                    $repopulatedForm = $this->_populateFormData($request, $templateForm);
//                                    return array(
//                                        'form'       => $repopulatedForm,
//                                        'addmessage' => $templateId
//                                    ); 
//                                } else {
//                                    $newRecord[$this->baseService->baseEncoding("templateId")] = $templateId;
//                                }                                
//                                $param['query'][] = $newRecord;
//                                $logtype = "newtemplate";
//                                $result = $this->_hydraConnect($param, 'coupon', 'create');
//                            } else {
                            
                                $record = $this->_buildRecord($templateForm, false, true);
                                $record[$this->baseService->baseEncoding("templateId")] = $templateId;
                                $param['query'][] = array("set" => $record, "where" => array($this->baseService->baseEncoding("_id") => $this->baseService->baseEncoding($id)));
                                $result = $this->_hydraConnect($param, 'coupon', 'update');
                                $liveTemplateSearch = $this->baseService->hydraConnect(array('templateId' => $templateId), "template", 'read', 'live');
                                if(isset($liveTemplateSearch['result']['records']) && isset($liveTemplateSearch['result']['record_count']) && $liveTemplateSearch['result']['record_count'] > 0){
                                    $template_id = $liveTemplateSearch['result']['records'][0]['id'];
                                    unset($param['query'][0]);
                                    $param['query'][] = array("set" => $record, "where" => array($this->baseService->baseEncoding("_id") => $this->baseService->baseEncoding($template_id)));  
                                    $result = $this->_hydraConnect($param, 'coupon', 'update', 'live');
                                }
                                $logtype = "edittemplate";
//                            }
                            $this->log($posts, $logtype);
                            return $this->_buildReply($result, $templateForm, 'update');
                        } else {

                            $repopulatedForm = $this->_populateFormData($request, $templateForm);

                            return array(
                                'form' => $repopulatedForm,
                                'coupon' => $this->baseService->getHydraPaginator(10, 0, "coupon", array('coupon.name' => '1'), array('templateId' => $templateId), "readCouponAndBatch","live"),
                                'templateId' => $templateId
                            );
                        }
                    } else {
                        $templateForm->buildForm($data);
                        return array(
                            'form' => $templateForm,
                            'coupon' => $this->baseService->getHydraPaginator(10, 0, "coupon", array('coupon.name' => '1'), array('templateId' => $templateId), "readCouponAndBatch","live"),
                            'templateId' => $templateId
                        );
                    }
                } else {
                    return array(
                        'addmessage' => "Invalid Template Id",
                        'redirect' => "/dcms/template"
                    );
                }
            } else {
                return array(
                    'addmessage' => $result,
//                    'redirect' => "/dcms/template"
                );
            }
        } else {
            return array(
                'addmessage' => "Template Id not found.",
                'redirect' => "/dcms/template"
            );
        }
    }

    private function _buildReply($result, $templateForm, $hydraTypeCall = 'create') {
        if (isset($result['result']['result'])) {
            return array(
                'addmessage' => ($hydraTypeCall == 'create') ? "Template successfully saved." : "Template successfully updated.",
                'redirect' => "/dcms/template"
            );
        } else {
            
            if(is_string($result)) {
                return array(
                    'addmessage' => $result,
                    'form' => $templateForm
                );
            } elseif (!is_string($result) && isset($result['result']['error']['database_result'])) {
                return array(
                    'addmessage' => $result['result']['error']['database_result'],
                    'form' => $templateForm
                );
            } elseif(isset($result['result']['error']['message'])) { 
                return array(
                    'addmessage' => $result['result']['error']['message'],
                    'redirect' => "/dcms/template"
                );
            }
        }
    }

    public function addForm($request) {

        $templateForm = $this->getTemplateForm();

        if ($request->isPost()) {
            $posts = $request->getPost();
            $templateForm->buildForm();
            if ($templateForm->isValid($posts)) {

                $templateName = $templateForm->getValue('template_name');
                $checkParam['type'] = 'template';
                $checkParam['query'] = array("templateName" => $templateName);
                $checkResult = $this->_hydraConnect($checkParam);

                if (isset($checkResult['records']['result'])) {
                    if ($checkResult['records']['result']['record_count'] > 0) {
                        $repopulatedForm = $this->_populateFormData($request, $templateForm);
                        return array(
                            'form' => $repopulatedForm,
                            'addmessage' => "Template name already exists. If not found on the list, it is deleted."
                        );
                    }
                } else {
                    return array(
                        'addmessage' => "An error occured while proccessing your request.",
                        'redirect' => "/dcms/template"
                    );
                }

                $newRecord = $this->_buildRecord($templateForm, true, false);
                $templateId =  $this->baseService->getUniqueKey();
                if(is_string($templateId)) {
                    $repopulatedForm = $this->_populateFormData($request, $templateForm);
                    return array(
                        'form'       => $repopulatedForm,
                        'addmessage' => $templateId
                    );
                } else {
                    $newRecord[$this->baseService->baseEncoding("templateId")] = $templateId;
                }
                $param['query'][] = $newRecord;
                $param['type'] = 'template';
                $param['decode'] = true;
                $result = $this->_hydraConnect($param, 'coupon', 'create');
                $this->log($posts, "newtemplate");

                return $this->_buildReply($result, $templateForm, 'create');
            } else {

                $repopulatedForm = $this->_populateFormData($request, $templateForm);

                return array(
                    'form' => $repopulatedForm
                );
            }
        } else {

            $templateForm->buildForm();
            return array(
                'form' => $templateForm
            );
        }
    }

    # Name: Del Date: Jun 2016
    public function addFormUpload($request) {

        $templateForm = $this->getTemplateFormUpload();

        if ($request->isPost()) {
            $posts = $request->getPost();
            $templateForm->buildForm();

            if ($templateForm->isValid($posts)) {

                /* START - INITIALIZE UPLOADING */
                if(isset($_FILES['attachment'])){

                    # Name: Del Date: Jun 2016

                    // SET THE FOLDER PATH
                    $import_file="/var/www/html/Dcms_ell/template_import_file/"; 
                    $imported_file = "/var/www/html/Dcms_ell/template_imported_file/"; 
                    // GET THE FILE INFO'S
                    $file_name = $_FILES['attachment']['name'];
                    $file_size = $_FILES['attachment']['size'];
                    $file_tmp = $_FILES['attachment']['tmp_name'];
                    $file_type = $_FILES['attachment']['type'];
                    $file_ext = strtolower(end(explode('.',$_FILES['attachment']['name'])));

                    /* START - DELETE ALL THE FILES INSIDE DIRECTORY FOLDER */
                    if ($dirhandler = opendir($import_file)) 
                    {
                        while ($file = readdir($dirhandler)) 
                        {
                            $delete[] = $import_file.$file;
                            foreach ( $delete as $file ) {
                                unlink( $file );
                            }
                        }
                    }
                    /* END - DELETE ALL THE FILES INSIDE DIRECTORY FOLDER */

                    if(!empty($file_size)){

                        // REQUIRED FILE EXTENSIONS
                        $expensions= array("csv");
                        // ARRAY ERROR MESSAGE
                        $errors= array();

                        /* START - VALIDATION FOR FILE EXTENSION */
                        if(in_array($file_ext,$expensions)=== false){
                            $errors[]="extension not allowed, please choose a JPEG or PNG file.";
                        }
                        /* END - VALIDATION FOR FILE EXTENSION */

                        /* START - VALIDATION FOR FILE SIZE */
                        if($file_size > 2097152){
                            $errors[]='File size must be excately 2 MB';
                        }
                        /* END - VALIDATION FOR FILE SIZE */

                        /* START - VALID FILE UPLOADING */
                        if(empty($errors)==true){
                            move_uploaded_file($file_tmp,$import_file.$file_name);

                            // SET VARIABLE TO CHECK THE DIRECTORY
                            $checkEmpty  = (count(glob($import_file.'*')) === 0) ? 'Empty' : 'Not empty';

                            /* START - CHECKING THE DIRECTORY  */
                            if ($checkEmpty == "Empty")
                            {
                               echo "No file inside directory!";
                            }
                            else
                            {
                                /* START - OPEN THE DIRECTORY */  
                                if ($dirhandler = opendir($import_file)) 
                                {
                                    /* START - LOOPING THE FILES */
                                    while ($file = readdir($dirhandler)) 
                                    {
                                        $file_ext = explode('.',$file);
                                        $file_ext = $file_ext[1];
                                        /* START - READ THE CSV FILES ONLY */
                                        if($file_ext == "csv" || $file_ext == "CSV")
                                        {   
                                            if($file == $file_name){
                                                // SET VARIABLE FOR CSV FILES
                                                $csv_file = $import_file.$file;
                                                /* START - OPEN THE CSV FILE CONTENT */
                                                if (($handle = fopen($csv_file, "r")) !== FALSE) 
                                                {
                                                    $category_name = $templateForm->getValue('restriction_selector_select');
                                                    if($category_name == 'parttemplate'){

                                                        $selected_category_name = 'parts';

                                                        /* START - LOOPING THE CSV FILES */
                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                        {
                                                            $num = count($data);
                                                            $row++;
                                                            
                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                            for ($c=0; $c < $num; $c++) 
                                                            {
                                                                $col1 = trim($data[0]);
                                                                //$document = $col1;
                                                            }
                                                            $arr_document[] = $this->baseService->baseEncoding($col1);
                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                        }
                                                        /* END - LOOPING THE CSV FILES */

                                                        $upload_record = array(
                                                            $this->baseService->baseEncoding($selected_category_name) => $arr_document
                                                        );
                                                    }
                                                    else if($category_name == 'brandtemplate'){

                                                        $selected_category_name = 'brands';

                                                        /* START - LOOPING THE CSV FILES */
                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                        {
                                                            $num = count($data);
                                                            $row++;
                                                            
                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                            for ($c=0; $c < $num; $c++) 
                                                            {
                                                                $col1 = trim($data[0]);
                                                                //$document = $col1;
                                                            }
                                                            $arr_document[] = $this->baseService->baseEncoding($col1);
                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                        }
                                                        /* END - LOOPING THE CSV FILES */

                                                        $upload_record = array(
                                                            $this->baseService->baseEncoding($selected_category_name) => $arr_document
                                                        );
                                                    }
                                                    else if($category_name == 'brandparttemplate'){
                                                        // $finalBrandParts[$this->baseService->baseEncoding($brand)][] = $this->baseService->baseEncoding($part);
                                                        $selected_category_name = 'brandPart';

                                                        /* START - LOOPING THE CSV FILES */
                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                        {
                                                            //$column_one = trim($data[0]);
                                                            //$arr_unique[] = $column_one;

                                                            $num = count($data);
                                                            $row++;
                                                            
                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                            for ($c=0; $c < $num; $c++) 
                                                            {
                                                                $col1 = trim($data[0]);
                                                                $col2 = trim($data[1]);
                                                            }
                                                            $arr_document1[] = $this->baseService->baseEncoding($col1);
                                                            $arr_document2[] = $this->baseService->baseEncoding($col2);
                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                        }
                                                        /* END - LOOPING THE CSV FILES */
                                                        $result_unique = array_values(array_unique($arr_document1));
                                                        //$arr_document[] = $this->baseService->baseEncoding($result_unique);

                                                        $upload_record = array(
                                                            $this->baseService->baseEncoding($selected_category_name) => array(
                                                                $result_unique => $arr_document2
                                                            )
                                                        );
                                                    }
                                                    else if($category_name == 'partbrandtemplate'){
                                                        $selected_category_name = 'partBrand';

                                                        $upload_record = array(
                                                            $this->baseService->baseEncoding($selected_category_name) => $arr_document
                                                        );
                                                    }
                                                    else if($category_name == 'skutemplate'){
                                                        $selected_category_name = 'brandSku';

                                                        $upload_record = array(
                                                            $this->baseService->baseEncoding($selected_category_name) => $arr_document
                                                        );
                                                    }
                                                    else{
                                                        $selected_category_name = 'error';    
                                                    }

                                                    $templateName = $templateForm->getValue('template_name');
                                                    $checkParam['type'] = 'template';
                                                    $checkParam['query'] = array("templateName" => $templateName);
                                                    $checkResult = $this->_hydraConnect($checkParam);

                                                    if (isset($checkResult['records']['result'])) {
                                                        if ($checkResult['records']['result']['record_count'] > 0) {
                                                            $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                            return array(
                                                                'form' => $repopulatedForm,
                                                                'addmessage' => "Template name already exists. If not found on the list, it is deleted."
                                                            );
                                                        }
                                                        
                                                    } else {
                                                        return array(
                                                            'addmessage' => "An error occured while proccessing your request.",
                                                            'redirect' => "/dcms/template"
                                                        );
                                                    }

                                                    $newRecord = $this->_buildRecordUpload($templateForm, true, false);
                                                    $templateId =  $this->baseService->getUniqueKey();
                                                    if(is_string($templateId)) {
                                                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                        return array(
                                                            'form'       => $repopulatedForm,
                                                            'addmessage' => $templateId
                                                        );
                                                    } else {
                                                        $newRecord[$this->baseService->baseEncoding("templateId")] = $templateId;
                                                    }
                                                    $param['query'][] = array_merge($newRecord,$upload_record);
                                                    $param['type'] = 'template';
                                                    $param['decode'] = true;
                                                    $result = $this->_hydraConnect($param, 'coupon', 'create');
                                                    $this->log($posts, "newtemplate");
                                                    return $this->_buildReply($result, $templateForm, 'create');

                                                    /* START - INSERT THE CONTENT TO MONGO DATABASE */
                                                    //if($collection->insert($document))
                                                    //{
                                                        /* START - COPY THE FILES TO ARCHIVE FOLDER */
                                                    //    if(copy($import_file.$file, $archive_files.$file))
                                                    //    {
                                                            // SET VARIABLE FOR FILE TO BE DELETED
                                                    //        $delete[] = $import_file.$file;
                                                            /* START - DELETION FILE */
                                                    //        foreach ( $delete as $file ) {
                                                                //unlink( $file );
                                                    //        }
                                                            /* END - DELETION FILE */
                                                    //    }
                                                        /* END - COPY THE FILES TO ARCHIVE FOLDER */
                                                    //}
                                                    //else
                                                    //{
                                                    //    echo "not ok";
                                                    //}

                                                    /* END - INSERT THE CONTENT TO MONGO DATABASE */
                                                }
                                                /* END - OPEN THE CSV FILE CONTENT */
                                            }
                                            else{
                                                $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                return array(
                                                    'form' => $repopulatedForm,
                                                    'addmessage' => "Invalid file."
                                                );
                                            }
                                        }
                                        /* END - READ THE CSV FILES ONLY */
                                    }
                                    /* END - LOOPING THE FILES */
                                }
                                /* END - OPEN THE DIRECTORY */  
                            }
                            /* START - CHECKING THE DIRECTORY  */
                        }
                        else{
                            print_r($errors);
                        }
                        /* END - VALID FILE UPLOADING */
                    }
                    else{
                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                        return array(
                            'form' => $repopulatedForm,
                            'addmessage' => "No file selected."
                        );
                    }
                } else {
                    $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                    return array(
                        'form' => $repopulatedForm,
                        'addmessage' => "No file selected."
                    );
                }
                /* END - INITIALIZE UPLOADING */
            } else {
                $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);

                return array(
                    'form' => $repopulatedForm
                );
            }
        } else {

            $templateForm->buildForm();
            return array(
                'form' => $templateForm
            );
        }
    }

    public function deleteTemplate($request) {

        $id = $request->getParam('id');
        if (isset($id)) {
            $param = array();
            $param['query'] = array("_id" => trim($id));
            $param['type'] = 'template';
            $result = $this->_hydraConnect($param, 'coupon', 'read');

            if (isset($result['result']['result']) && count($result['result']['result']['record_count']) == 1) {

                $templateName = $result['result']['result']['records'][0]['templateName'];
                $templateRecord = $result['result']['result']['records'][0];
                $paramTemplateCouponSearch = array();
                $paramTemplateCouponSearch['query'] = array("restrictions" => trim($templateName));
                $paramTemplateCouponSearch['type'] = 'coupon';
                $paramResult = $this->_hydraConnect($paramTemplateCouponSearch, 'coupon', 'read', 'live');
                $paramResultWorking = $this->_hydraConnect($paramTemplateCouponSearch, 'coupon', 'read', 'working');

                if (isset($paramResult['result']['result']) && $paramResult['result']['result']['record_count'] >= 1) {
                    return array(
                        'addmessage' => 'You cannot delete this template. One or more coupons are using this template.',
                        'redirect' => '/dcms/template'
                    );
                } elseif (isset($paramResultWorking['result']['result']) && $paramResultWorking['result']['result']['record_count'] >= 1) {
                    return array(
                        'addmessage' => 'You cannot delete this template. One or more coupons are using this template.',
                        'redirect' => '/dcms/template'
                    );
                } else {
                    $this->_hydraConnect($param, 'coupon', 'delete', 'live');
                    unset($param);
                    $param['type'] = 'template';
//                    $param['decode'] = true;
                    $templateRecord['deleted'] = (bool) true;
                    unset($templateRecord['id']);
//                    $encodedRecord = $this->baseService->arrayRecursiveEncode($templateRecord);
//                    $param['query'][] = array("set" => $encodedRecord, "where" => array($this->baseService->baseEncoding("_id") => $this->baseService->baseEncoding($id)));
                    $param['query'][] = array("set" => $templateRecord, "where" => array("_id" => $id));
                    $templateUpdate = $this->_hydraConnect($param, 'coupon', 'update');

                    if (isset($templateUpdate['result']['result'])) {
                        $this->log(array('template_name' => $templateName), "deletetemplate");
                        return array(
                            'addmessage' => "Template successfully deleted.",
                            'redirect' => "/dcms/template"
                        );
                    } else {
                        if (!is_string($templateUpdate) && isset($templateUpdate['result']['error']['database_result'])) {
                            return array(
                                'addmessage' => "An error occured while deleting the template.",
                                'redirect' => "/dcms/template"
                            );
                        } else {
                            return array(
                                'addmessage' => "An error occured while deleting the template.",
                                'redirect' => "/dcms/template"
                            );
                        }
                    }
                }
            } else {
                return array(
                    'addmessage' => 'Invalid id.',
                    'redirect' => '/dcms/template'
                );
            }
        } else {
            return array(
                'addmessage' => 'Invalid id.',
                'redirect' => '/dcms/template'
            );
        }
    }

    private function _populateFormData($request, $templateForm) {

        $restriction_part_selected = $request->getParam('restriction_part_selected');
        if (count($restriction_part_selected) > 0) {
            foreach ($restriction_part_selected as $selected) {
                $templateForm->getElement('restriction_part_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_selected = $request->getParam('restriction_brand_selected');
        if (count($restriction_brand_selected) > 0) {
            foreach ($restriction_brand_selected as $selected) {
                $templateForm->getElement('restriction_brand_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_part_selected = $request->getParam('restriction_brand_part_selected');
        if (count($restriction_brand_part_selected) > 0) {
            foreach ($restriction_brand_part_selected as $selected) {
                $templateForm->getElement('restriction_brand_part_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }
        $restriction_part_brand_selected = $request->getParam('restriction_part_brand_selected');
        if (count($restriction_part_brand_selected) > 0) {
            foreach ($restriction_part_brand_selected as $selected) {
                $templateForm->getElement('restriction_part_brand_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }

        $restriction_sku_selected = $request->getParam('restriction_sku_selected');
        if (count($restriction_sku_selected) > 0) {
            foreach ($restriction_sku_selected as $selected) {
                $innerValue = explode('|', $selected);
                $templateForm->getElement('restriction_sku_selected')->addMultiOptions(array($selected => trim(strtoupper($innerValue[1])) . ' -- ' . trim($innerValue[0]) . ' -- ' . trim($innerValue[2])));
            }
        }
        return $templateForm;
    }

    # Name: Del Date: Jun 2016
    private function _populateFormDataUpload($request, $templateForm) {

        $restriction_part_selected = $request->getParam('restriction_part_selected');
        if (count($restriction_part_selected) > 0) {
            foreach ($restriction_part_selected as $selected) {
                $templateForm->getElement('restriction_part_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_selected = $request->getParam('restriction_brand_selected');
        if (count($restriction_brand_selected) > 0) {
            foreach ($restriction_brand_selected as $selected) {
                $templateForm->getElement('restriction_brand_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_part_selected = $request->getParam('restriction_brand_part_selected');
        if (count($restriction_brand_part_selected) > 0) {
            foreach ($restriction_brand_part_selected as $selected) {
                $templateForm->getElement('restriction_brand_part_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }
        $restriction_part_brand_selected = $request->getParam('restriction_part_brand_selected');
        if (count($restriction_part_brand_selected) > 0) {
            foreach ($restriction_part_brand_selected as $selected) {
                $templateForm->getElement('restriction_part_brand_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }

        $restriction_sku_selected = $request->getParam('restriction_sku_selected');
        if (count($restriction_sku_selected) > 0) {
            foreach ($restriction_sku_selected as $selected) {
                $innerValue = explode('|', $selected);
                $templateForm->getElement('restriction_sku_selected')->addMultiOptions(array($selected => trim(strtoupper($innerValue[1])) . ' -- ' . trim($innerValue[0]) . ' -- ' . trim($innerValue[2])));
            }
        }
        return $templateForm;
    }

    private function _buildRecord($templateForm, $hasCreatedDate = false, $hasModifiedDate = false) {

        $restriction_part_selected = $templateForm->getValue('restriction_part_selected');
        $restriction_brand_part_selected = $templateForm->getValue('restriction_brand_part_selected');
        $restriction_brand_selected = $templateForm->getValue('restriction_brand_selected');
        $restriction_part_brand_selected = $templateForm->getValue('restriction_part_brand_selected');

        $finalResultSkus = $templateForm->getValue('restriction_sku_selected');

        $finalParts = array();
        if (isset($restriction_part_selected) && count($restriction_part_selected) > 0) {
            foreach ($restriction_part_selected as $part) {
                $finalParts[] = $this->baseService->baseEncoding($part);
            }
        }

        $finalBrands = array();
        if (isset($restriction_brand_selected) && count($restriction_brand_selected) > 0) {
            foreach ($restriction_brand_selected as $brand) {
                $finalBrands[] = $this->baseService->baseEncoding($brand);
            }
        }

        $finalBrandParts = array();
        if (isset($restriction_brand_part_selected) && count($restriction_brand_part_selected) > 0) {
            foreach ($restriction_brand_part_selected as $brandpart) {
                $explodedBrandPart = explode('|', $brandpart);
                $brand = trim($explodedBrandPart[0]);
                $part = trim($explodedBrandPart[1]);
                $finalBrandParts[$this->baseService->baseEncoding($brand)][] = $this->baseService->baseEncoding($part);
            }
        }

        $finalPartBrands = array();
        if (isset($restriction_part_brand_selected) && count($restriction_part_brand_selected) > 0) {
            foreach ($restriction_part_brand_selected as $partbrand) {
                $explodedPartBrand = explode('|', $partbrand);
                $part = trim($explodedPartBrand[0]);
                $brand = trim($explodedPartBrand[1]);
                $finalPartBrands[$this->baseService->baseEncoding($part)][] = $this->baseService->baseEncoding($brand);
            }
        }

        $finalSkus = array();
        if (isset($finalResultSkus) && count($finalResultSkus) > 0) {
            foreach ($finalResultSkus as $sku) {
                $explodedSku = explode('|', $sku);
                $brandName = trim($explodedSku[0]);
                $encodedBrand = $this->baseService->baseEncoding($brandName);
                $site = trim(strtolower($explodedSku[1]));
                $sku = trim($explodedSku[2]);
                $encodedSku = $this->baseService->baseEncoding($sku);
                if (count($explodedSku) == 3) {
                    if ($site == 'usap') {
                        $finalSkus[$this->baseService->baseEncoding("usap")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'jcw') {
                        $finalSkus[$this->baseService->baseEncoding("jcw")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'stt') {
                        $finalSkus[$this->baseService->baseEncoding("stt")][$encodedBrand][] = $encodedSku;
                    }
                }
            }
        }

        $record = array();
        $record = array(
            $this->baseService->baseEncoding("templateName") => $this->baseService->baseEncoding($templateForm->getValue('template_name')),
            $this->baseService->baseEncoding("type") => $this->baseService->baseEncoding($templateForm->getValue('exinradio')),
            $this->baseService->baseEncoding("brands") => $finalBrands,
            $this->baseService->baseEncoding("parts") => $finalParts,
            $this->baseService->baseEncoding("brandPart") => $finalBrandParts,
            $this->baseService->baseEncoding("partBrand") => $finalPartBrands,
            $this->baseService->baseEncoding("brandSku") => $finalSkus,
            $this->baseService->baseEncoding("creator") => $this->baseService->baseEncoding($this->user),
            $this->baseService->baseEncoding("modified") => (bool) true
        );
        ($hasCreatedDate === true) ? $record[$this->baseService->baseEncoding("createdAt")] = time() : null;
        ($hasModifiedDate === true) ? $record[$this->baseService->baseEncoding("updatedAt")] = time() : null;
        return $record;
    }

    # Name: Del Date: Jun 2016
    private function _buildRecordUpload($templateForm, $hasCreatedDate = false, $hasModifiedDate = false) {

        $restriction_part_selected = $templateForm->getValue('restriction_part_selected');
        $restriction_brand_part_selected = $templateForm->getValue('restriction_brand_part_selected');
        $restriction_brand_selected = $templateForm->getValue('restriction_brand_selected');
        $restriction_part_brand_selected = $templateForm->getValue('restriction_part_brand_selected');

        $finalResultSkus = $templateForm->getValue('restriction_sku_selected');

        $finalSkus = array();
        if (isset($finalResultSkus) && count($finalResultSkus) > 0) {
            foreach ($finalResultSkus as $sku) {
                $explodedSku = explode('|', $sku);
                $brandName = trim($explodedSku[0]);
                $encodedBrand = $this->baseService->baseEncoding($brandName);
                $site = trim(strtolower($explodedSku[1]));
                $sku = trim($explodedSku[2]);
                $encodedSku = $this->baseService->baseEncoding($sku);
                if (count($explodedSku) == 3) {
                    if ($site == 'usap') {
                        $finalSkus[$this->baseService->baseEncoding("usap")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'jcw') {
                        $finalSkus[$this->baseService->baseEncoding("jcw")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'stt') {
                        $finalSkus[$this->baseService->baseEncoding("stt")][$encodedBrand][] = $encodedSku;
                    }
                }
            }
        }

        $record = array();
        $record = array(
            $this->baseService->baseEncoding("templateName") => $this->baseService->baseEncoding($templateForm->getValue('template_name')),
            $this->baseService->baseEncoding("type") => $this->baseService->baseEncoding($templateForm->getValue('exinradio')),
            $this->baseService->baseEncoding("creator") => $this->baseService->baseEncoding($this->user),
            $this->baseService->baseEncoding("modified") => (bool) true
        );
        ($hasCreatedDate === true) ? $record[$this->baseService->baseEncoding("createdAt")] = time() : null;
        ($hasModifiedDate === true) ? $record[$this->baseService->baseEncoding("updatedAt")] = time() : null;
        return $record;
    }

    public function listing($postArray) {

        $isSearch = $this->getRequestValue($postArray, 'search');
        $post = $this->getRequestValue($postArray, 'post');
        $params = array();
        $params = array(
            'post' => $this->baseService->baseEncoding($post),
            'offset' => $this->getRequestValue($postArray, 'offset'),
            'pagesize' => $this->getRequestValue($postArray, 'pagesize'),
            'issearch' => $isSearch,
//            'element'  => $this->getRequestValue($postArray, 'element') 
        );
        (isset($postArray['parentpost'])) ? $params['parentpost'] = $this->baseService->baseEncoding($this->getRequestValue($postArray, 'parentpost')) : '';
        (isset($postArray['selected'])) ? $this->selected = $postArray['selected'] : '';
        return $params;
    }

    public function getRequestValue($arrayPost, $key) {

        if (trim($key) == 'offset' && isset($arrayPost['offset']) && is_array($arrayPost['offset'])) {
            $params = array();
            (isset($arrayPost['offset']['usap']) && trim($arrayPost['offset']['usap']) != "") ? $params['usap'] = $arrayPost['offset']['usap'] : '';
            (isset($arrayPost['offset']['jcw']) && trim($arrayPost['offset']['jcw']) != "") ? $params['jcw'] = $arrayPost['offset']['jcw'] : '';
            (isset($arrayPost['offset']['stt']) && trim($arrayPost['offset']['stt']) != "") ? $params['stt'] = $arrayPost['offset']['stt'] : '';
            return $params;
        } else {
            return (isset($arrayPost[$key]) && trim($arrayPost[$key]) != "") ? $arrayPost[$key] : null;
        }
    }

    public function getResults($params, $fieldColumnIdentifier, $secondDegree = null, $isSkuResult = false) {

        $results = $this->_hydraConnect($params);        
        if (is_string($results)) {
            return $results;
        }
        
        if ((isset($this->selected)) && (count($this->selected) > 0)) {
            $this->hasSelectedRestriction = true;
        }
        $newHaystack = $this->_needleHaystack($results, $fieldColumnIdentifier, $secondDegree, $isSkuResult);
        return $newHaystack;
    }

    private function _needleHaystack($hydraResults, $fieldColumnIdentifier, $secondDegree = null, $isSkuResult = false) {

        if ($this->hasSelectedRestriction) {
            if ($isSkuResult) {
                $siteSkuIsolation = $this->_siteSkuIsolation();
                if (count($siteSkuIsolation) > 0) {
                    if (isset($hydraResults['records']) && count($hydraResults['records']) > 0) {
                        foreach ($hydraResults['records'] as $perSiteRecords => $perSiteValues) {
                            foreach ($perSiteValues as $recordKey => $recordValue) {
                                if (in_array(trim($recordValue['sku']), $siteSkuIsolation[$perSiteRecords])) {
                                    unset($hydraResults['records'][$perSiteRecords][$recordKey]);
                                }
                            }
                        }
                    }
                }
                return array('results' => $hydraResults, 'skussites' => $siteSkuIsolation);
            } else {

                if ($secondDegree != null) {
                    if ($secondDegree == 'brandpart') {
                        foreach ($hydraResults['records'] as $key => $value) {

                            if (in_array(trim($value['brand_name'] . '|' . $value['part_name']), $this->selected)) {
                                unset($hydraResults['records'][$key]);
                            }
                        }
                    }
                    if ($secondDegree == 'partbrand') {
                        foreach ($hydraResults['records'] as $key => $value) {

                            if (in_array(trim($value['part_name'] . '|' . $value['brand_name']), $this->selected)) {
                                unset($hydraResults['records'][$key]);
                            }
                        }
                    }
                } else {
                    foreach ($hydraResults['records'] as $key => $value) {
                        if (in_array(trim($value["{$fieldColumnIdentifier}"]), $this->selected)) {
                            unset($hydraResults['records'][$key]);
                        }
                    }
                    return $hydraResults;
                }
            }
        }
        return $hydraResults;
    }

    private function _siteSkuIsolation() {

        $finalResults = array();
        $usapRestriction = array();
        $jcwRestriction = array();
        $sttRestriction = array();

        foreach ($this->selected as $restriction) {

            $expRestriction = explode('|', $restriction);

            if (count($expRestriction) == 3) {
                $site = trim(strtolower($expRestriction[1]));
                if ($site == 'usap') {
                    $usapRestriction[] = $expRestriction[2];
                } elseif ($site == 'jcw') {
                    $jcwRestriction[] = $expRestriction[2];
                } elseif ($site == 'stt') {
                    $sttRestriction[] = $expRestriction[2];
                }
            }
        }

        ((isset($usapRestriction)) && (count($usapRestriction) > 0)) ? $finalResults['usap'] = $usapRestriction : '';
        ((isset($jcwRestriction)) && (count($jcwRestriction) > 0)) ? $finalResults['jcw'] = $jcwRestriction : '';
        ((isset($sttRestriction)) && (count($sttRestriction) > 0)) ? $finalResults['stt'] = $sttRestriction : '';

        return $finalResults;
    }

    public function _hydraConnect($data = null, $serviceIdentifier = 'coupon', $method = 'read', $env = 'working') {

        $config = $this->config->service->toArray();

        try {
            $data['env'] = $env;
            $serviceObject = $config['sourceobject']["{$serviceIdentifier}"];
            $getResults = Hydra_Helper::loadClass(
                            $serviceObject['url'], $serviceObject['version'], $serviceObject['service'], $method, $data, $serviceObject['httpmethod'], $serviceObject['id'], $serviceObject['format']
            );    
            if (isset($getResults['_payload']['result'])) {
                return array(
                    'record_count' => $getResults['_payload']['result']["{$method}"],
                    'records' => $getResults['_payload']['result']["{$method}"],
                    'result' => $getResults['_payload']['result']["{$method}"]
                );
            }
            return 'An error occured while requesting on the Coupon API.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
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
      string| authentication, upload, newtemplate, edittemplate, deletetemplate, etc.>
     */

    public function log($values, $type) {
        $details = array(
            'code' => $values['template_name'],
            'type' => $type,
            'transDetails' => "",
            'transData' =>
            array(
                "1" => $_SERVER['REMOTE_ADDR'],
                "2" => $_SERVER['SERVER_NAME'],
            ),
            'user' => $this->user,
            'date' => time(),
            'log_date' => time(),
            'remarks' => "",
            'status' => (isset($values['publish']) && $values['publish'] == true) ? "published" : "testing",
            'couponType' => "template"
        );
        return $this->baseService->hydraConnect(array($details), "logs", "create", "live");
    }
    
}

?>