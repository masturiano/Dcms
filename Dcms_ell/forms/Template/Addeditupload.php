<?php

class Dcms_Form_Template_Addeditupload extends Zend_Form
{

    protected $data;
    
    public function init() {

        parent::init();       
    }
    
    public function buildForm($data = null) {
        
        $this->data = $data;
        $this->setName('Dcms_Template_Addeditupload');

        $this->setAttrib('enctype', 'multipart/form-data');
        
    /**
     * Part Template Restriction
     */
        /**
         * Template Name Text Element
         */
        $text = $this->_getElementData('template_name');
        $this->_createTextbox('template_name', $text);

        # Name: Del Date: Jun 2016
        //$this->_getElementData('template_name_upload');
        //$this->_createTextbox('template_name_upload', $text);

        /**
         * Exclude and Include Radio Button
         */
        $this->_createExcludeIncludeRadio();

        /**
         * File upload
         */
        $this->_createUpload();
        
        /**
         * Restriction Selector 
         */
        $select = $this->_getElementData('restriction_selector_select');
        $this->_createSelect('restriction_selector_select', $select['elementdetails'], $select['addmultioptions']);
        
    /**
     * Submit Button
     */       
        $this->_createSubmit('Save', array('id' => 'Save', 'value' => 'Save Template', "onClick" => "document.getElementById('Dcms_Template_Addeditupload').submit();"));
    }
    
    /**
     * Submit Element Template
     * This will create submit element
     * @param   string  $name
     * @param   array   $elementInfo
     */
    private function _createSubmit($name, $elementInfo) {
        
        $submit = $this->createElement('button', $name, $elementInfo)
            ->removeDecorator('HtmlTag')
            ->removeDecorator('Label');
        $this->addElement($submit);
    }
    
    /**
     * Text Element Template
     * This will create text element
     * @param   string  $name
     * @param   array   $elementInfo
     */
    private function _createTextbox($name, $elementInfo) {
        
        $text = $this->createElement('text', $name, $elementInfo)
            ->removeDecorator('HtmlTag')
            ->removeDecorator('Label');
        $this->addElement($text);
    }
    
    /**
     * This will create radio element named exinradio
     */    
    private function _createExcludeIncludeRadio() {
        
        $radioExcludeInclude = $this->createElement('radio', 'exinradio', array(

            ))
            ->addMultiOptions(array(
                'exclude' => 'Exclude',
                'include' => 'Include',
            ))
            ->setSeparator('&nbsp;&nbsp;&nbsp;')
            ->removeDecorator('HtmlTag')->removeDecorator('Label');
        (isset($this->data['templateName'])) ? $radioExcludeInclude->setValue($this->data['type']) : $radioExcludeInclude->setValue('exclude')  ;

        $this->addElement($radioExcludeInclude); 
    }
    
    /**
     * This will create checkbox named 'publish' to publish template to live copy if checked.
     */
    /*
    private function _createPublishCheckbox(){
        
         $checkboxPublish = $this->createElement('checkbox', 'publish', array(
                    'label'      => 'Publish?:',
                    'filters'    => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'value'  => (isset($this->data['publish'])) ? $this->data['publish'] : false ,
                'required'   => true,
                'attribs'=> array(
                    'maxlength' => 30,
                    'size'   => 30,
                    'class'  => 'uniform',
                    'style'  => 'background-color: #FFF !important;',
                    
                )))
            ->removeDecorator('HtmlTag')
            ->removeDecorator('Label');
        $this->addElement($checkboxPublish);
    
    }
    */

    /**
     * Select Element Template
     * This will create select element
     * @param   string  $name
     * @param   array   $elementInfo
     * @param   array   $addMultiOptions 
     * @param   boolean $isSetToArray
     */

    private function _createSelect($name, $elementInfo, $addMultiOptions = null, $isSetToArray = false) {
        
        $select = $this->createElement('select', $name, $elementInfo);
        if($addMultiOptions != null) {
            $select->addMultiOptions($addMultiOptions); 
        }
        if($isSetToArray) {
            $select->setIsArray(true);
        }
        $select->setRegisterInArrayValidator(false);
        $select->removeDecorator('HtmlTag')
            ->removeDecorator('Label');
        $this->addElement($select);
    }
    
    
    /**
     * This will create the element information depending on your parameter
     * @param   string  $elementId
     * @return  array
     */
    private function _getElementData($elementId) {
                
        switch ($elementId) {
            
            default :
                
            case "template_name" : 
                
                return array(
                    'label'      => 'Template Name:',
                    'filters'    => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'value'  => (isset($this->data['templateName'])) ? $this->data['templateName'] : null ,
                'required'   => true,
                'attribs'=> array(
                    'maxlength' => 30,
                    'size'   => 30,
                    'class'  => 'uniform',
                    'style'  => 'background-color: #FFF !important;',
                    
                ));
                break;
            
            case "restriction_selector_select" :
                
                return array(
                    'elementdetails' => array(
                        'style'     => "width:200px",
                        'onKeyUp'   =>"selectorDisplayType(this.value, '#restriction_selector_select'); return false;",
                        'onChange'  =>"selectorDisplayType(this.value, '#restriction_selector_select'); return false;"
                    ),
                    'addmultioptions' => array(
                        'parttemplate'      => 'Parts',
                        'brandtemplate'     => 'Brands',
                        'brandparttemplate' => 'Brand-Part',
                        'partbrandtemplate' => 'Part-Brand',
                        'skutemplate'       => 'Skus'
                    )
                );
                break;
        }
        
    }

    private function _createUpload() {

        $this->addElement(
            'file', 'attachment', 
                array(
                    'label' => 'Attachment:',
                    'decorators' => array(
                        array('File'),
                        array('label'),
                        array(
                            array('row' => 'HtmlTag'),
                            array('tag' => 'div', 'class' => 'form-row')
                        )
                    ),
                    'attribs' => array(
                        'class' => 'uniform',
                        'size' => '200',
                        'style' => 'width: 400px; '
                    ),
                )
        );
    }
}
