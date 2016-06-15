<?php

class Dcms_Form_Template_Addedit extends Zend_Form
{

    protected $data;
    
    public function init() {

        parent::init();       
    }
    
    public function buildForm($data = null) {
        
        $this->data = $data;
        $this->setName('Dcms_Template_Addedit');
        
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
         * Restriction Selector 
         */
        $select = $this->_getElementData('restriction_selector_select');
        $this->_createSelect('restriction_selector_select', $select['elementdetails'], $select['addmultioptions']);
        
        /**
         * Restirction Part Selector Search Text Element
         */
        $searchPartText = $this->_getElementData('restriction_part_selector_search');
        $this->_createTextbox('restriction_part_selector_search', $searchPartText);
                
        /**
         * Restriction Part List Selectbox
         */
        $restrictionPartList = $this->_getElementData('restriction_part_list');
        $this->_createSelect('restriction_part_list', $restrictionPartList['elementdetails'], null , $restrictionPartList['isArray']);        
        
        /**
         * Restriction Part Selected Selectbox 
         */
        $selectedParts = array();
        if(isset($this->data['parts'])) {
            foreach($this->data['parts'] as $selected) {
                $partSelected = trim($selected);
                $selectedParts[$partSelected] = $partSelected;
            }
        }     
        $restrictionPartSelected = $this->_getElementData('restriction_part_selected');
        $this->_createSelect('restriction_part_selected', $restrictionPartSelected['elementdetails'], $selectedParts, $restrictionPartSelected['isArray']);

    /**
     * Brand Template Restriction
     */
        /**
         * Restirction Brand Selector Search Text Element
         */
        $searchBrandText = $this->_getElementData('restriction_brand_selector_search');
        $this->_createTextbox('restriction_brand_selector_search', $searchBrandText);
        
        /**
         * Restriction Brand List Selectbox
         */
        $restrictionBrandList = $this->_getElementData('restriction_brand_list');
        $this->_createSelect('restriction_brand_list', $restrictionBrandList['elementdetails'], null , $restrictionBrandList['isArray']);
        
        /**
         * Restriction Brand Selected Selectbox 
         */
        $selectedBrands = array();
        if(isset($this->data['brands'])) {
            foreach($this->data['brands'] as $selected) {
                $brandSelected = trim($selected);
                $selectedBrands[$brandSelected] = $brandSelected;
            }
        }         
        $restrictionBrandSelected = $this->_getElementData('restriction_brand_selected');
        $this->_createSelect('restriction_brand_selected', $restrictionBrandSelected['elementdetails'], $selectedBrands , $restrictionBrandSelected['isArray']);

    /**
     * Brand-Part Template Restriction
     */
        /**
         * Restirction Brand Part Selector Brand Search Text Element
         */
        $searchBrandPartBrandText = $this->_getElementData('restriction_brand_part_selector_brand_search');
        $this->_createTextbox('restriction_brand_part_selector_brand_search', $searchBrandPartBrandText);
        
        /**
         * Restriction Brand Part Brand List Selectbox
         */
        $restrictionBrandPartBrandList = $this->_getElementData('restriction_brand_part_brand_list');
        $this->_createSelect('restriction_brand_part_brand_list', $restrictionBrandPartBrandList['elementdetails'], null , $restrictionBrandPartBrandList['isArray']);        

        /**
         * Restirction Brand Part Selector Part Search Text Element
         */
        $searchBrandPartPartText = $this->_getElementData('restriction_brand_part_selector_part_search');
        $this->_createTextbox('restriction_brand_part_selector_part_search', $searchBrandPartPartText);        

        /**
         * Restriction Brand Part Part List Selectbox
         */
        $restrictionBrandPartPartList = $this->_getElementData('restriction_brand_part_part_list');
        $this->_createSelect('restriction_brand_part_part_list', $restrictionBrandPartPartList['elementdetails'], null , $restrictionBrandPartPartList['isArray']);        

        /**
         * Restriction Brand Part Part Selected Selectbox 
         */
        $selectedBrandParts = array();
        if(isset($this->data['brandPart'])) {
            foreach($this->data['brandPart'] as $brand => $partsSelected) {
                foreach($partsSelected as $part) {
                    $selectedBrandParts[trim($brand . '|' . $part)] = $brand . ' -- ' . $part;
                }
            }
        }
        $restrictionBrandPartSelected = $this->_getElementData('restriction_brand_part_selected');
        $this->_createSelect('restriction_brand_part_selected', $restrictionBrandPartSelected['elementdetails'], $selectedBrandParts , $restrictionBrandPartSelected['isArray']);

    /**
     * Part-Brand Template Restriction
     */
        /**
         * Restirction Part Brand Selector Part Search Text Element
         */
        $searchPartBrandPartText = $this->_getElementData('restriction_part_brand_selector_part_search');
        $this->_createTextbox('restriction_part_brand_selector_part_search', $searchPartBrandPartText);        

        /**
         * Restriction Part Brand Part List Selectbox
         */
        $restrictionPartBrandPartList = $this->_getElementData('restriction_part_brand_part_list');
        $this->_createSelect('restriction_part_brand_part_list', $restrictionPartBrandPartList['elementdetails'], null , $restrictionPartBrandPartList['isArray']);        

        /**
         * Restirction Part Brand Selector Brand Search Text Element
         */
        $searchPartBrandBrandText = $this->_getElementData('restriction_part_brand_selector_brand_search');
        $this->_createTextbox('restriction_part_brand_selector_brand_search', $searchPartBrandBrandText);  

        /**
         * Restriction Part Brand Brand List Selectbox
         */
        $restrictionPartBrandBrandList = $this->_getElementData('restriction_part_brand_brand_list');
        $this->_createSelect('restriction_part_brand_brand_list', $restrictionPartBrandBrandList['elementdetails'], null , $restrictionPartBrandBrandList['isArray']);        

        /**
         * Restriction Part Brand Part Selected Selectbox 
         */
        $selectedPartBrands = array();
        if(isset($this->data['partBrand'])) {
            foreach($this->data['partBrand'] as $part => $brandsSelected) {
                foreach($brandsSelected as $brand) {
                    $selectedPartBrands[trim($part . '|' . $brand)] = $part . ' -- ' . $brand;
                }
            }
        }
        $restrictionPartBrandSelected = $this->_getElementData('restriction_part_brand_selected');
        $this->_createSelect('restriction_part_brand_selected', $restrictionPartBrandSelected['elementdetails'], $selectedPartBrands , $restrictionPartBrandSelected['isArray']);

    /**
     * Brand-Sku Template Restriction
     */
        /**
         * Restirction Sku Selector Brand Search Text Element
         */
        $searchSkuBrandText = $this->_getElementData('restriction_sku_selector_brand_search');
        $this->_createTextbox('restriction_sku_selector_brand_search', $searchSkuBrandText);        
        
        /**
         * Restriction Sku Brand List Selectbox
         */
        $restrictionSkuBrandList = $this->_getElementData('restriction_sku_brand_list');
        $this->_createSelect('restriction_sku_brand_list', $restrictionSkuBrandList['elementdetails'], null , $restrictionSkuBrandList['isArray']);        

        /**
         * Restirction Sku Selector Sku Usap Search Text Element
         */
        $searchSkuUsapText = $this->_getElementData('restriction_sku_selector_sku_usap_search');
        $this->_createTextbox('restriction_sku_selector_sku_usap_search', $searchSkuUsapText);

        /**
         * Restriction Sku Sku Usap List Selectbox
         */
        $restrictionSkuUsapList = $this->_getElementData('restriction_sku_sku_usap_list');
        $this->_createSelect('restriction_sku_sku_usap_list', $restrictionSkuUsapList['elementdetails'], null , $restrictionSkuUsapList['isArray']);        
        
        /**
         * Restirction Sku Selector Sku Jcw Search Text Element
         */
        $searchSkuJcwText = $this->_getElementData('restriction_sku_selector_sku_jcw_search');
        $this->_createTextbox('restriction_sku_selector_sku_jcw_search', $searchSkuJcwText);

        /**
         * Restriction Sku Sku Jcw List Selectbox
         */
        $restrictionSkuJcwList = $this->_getElementData('restriction_sku_sku_jcw_list');
        $this->_createSelect('restriction_sku_sku_jcw_list', $restrictionSkuJcwList['elementdetails'], null , $restrictionSkuJcwList['isArray']);         

        /**
         * Restirction Sku Selector Sku Stt Search Text Element
         */
        $searchSkuSttText = $this->_getElementData('restriction_sku_selector_sku_stt_search');
        $this->_createTextbox('restriction_sku_selector_sku_stt_search', $searchSkuSttText);        

        /**
         * Restriction Sku Sku Stt List Selectbox
         */
        $restrictionSkuSttList = $this->_getElementData('restriction_sku_sku_stt_list');
        $this->_createSelect('restriction_sku_sku_stt_list', $restrictionSkuSttList['elementdetails'], null , $restrictionSkuSttList['isArray']);  

        /**
         * Restriction Sku Selected List Selectbox
         */
        $selectedSkus = array();
        if(isset($this->data['brandSku'])) {
            foreach($this->data['brandSku'] as $key => $value) {
                foreach($value as $innerKey => $innerValue) {
                    foreach($innerValue as $finalKey => $finalValue) {
                        $selectedSkus[$innerKey . '|' . $key . '|' . $finalValue] = trim(strtoupper($key)) . ' -- ' . trim($innerKey) . ' -- ' . trim($finalValue);
                    }
                }
            }
        }
        $restrictionSkuSelectedList = $this->_getElementData('restriction_sku_selected');
        $this->_createSelect('restriction_sku_selected', $restrictionSkuSelectedList['elementdetails'], $selectedSkus , $restrictionSkuSelectedList['isArray']);         
    
        
        /**
         * Publish check box
         */
        
        $this->_createPublishCheckbox();
        
    /**
     * Submit Button
     */       
        $this->_createSubmit('Save', array('id' => 'Save', 'value' => 'Save Template', "onClick" => "populateSelected(); document.getElementById('Dcms_Template_Addedit').submit();"));
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
            
            case "restriction_part_selector_search" :
                
                return array(
                    'attribs'=> array(
                        'style'   => 'width: 150px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_part_selector_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_part_list', 'partlist', 
                            'RESTRICTION_PART_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_PART_LIST_SEARCH', 
                            'RESTRICTION_PART_LIST_OFFSET_SIZE', '#restriction_part_list', '#restriction_part_selected', event); return false;",
                        
                    ),
                    'value' => "search part"
                );
                break;
            
            case "restriction_part_list" :
      
                return array(
                    'elementdetails' => array(
                        'multiple'      => true,
                        'attribs'       => array(
                            'style'     => 'width: 297px; height: 250px;',
                            'size'      => 15,
                            'onChange'  => "callList(this.value, this.id, 'partlist', 'RESTRICTION_PART_LIST_OFFSET_SIZE', '#restriction_part_selected'); return false;",
                            'onDblClick'=> "listbox_moveacross('restriction_part_list', 'restriction_part_selected', 'restriction_part_list', 'add');"
                        )
                    ),
                    'isArray'        => true
                );
                break;
            
            case "restriction_part_selected" :

                return array(
                    'elementdetails' => array(
//                        'filters'       => array('StringTrim', new Zend_Filter_HtmlEntities(array(array('doublequote' => false)))),
                        'multiple'      => true,
                        'attribs'       => array(
                            'style'     => 'width: 297px; height: 250px;',
                            'size'      => 15,
                            'onDblClick'=> "listbox_moveacross('restriction_part_selected', 'restriction_part_list', 
                                'restriction_part_list', 'remove');
                                listbox_selectall('restriction_part_list',false); 
                                sortSelect(this.form['restriction_part_list'], true);"
                        ) 
                    ),
                    'isArray'        => true
                );
                break;
            
            case "restriction_brand_selector_search" :
                
                return array(
                    'attribs' => array(
                        'style'   => 'width: 150px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_brand_selector_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_brand_list', 'brandlist', 
                            'RESTRICTION_BRAND_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_BRAND_LIST_SEARCH', 
                            'RESTRICTION_BRAND_LIST_OFFSET_SIZE', '#restriction_brand_list', '#restriction_brand_selected', event); return false;",
                    ),
                    'value' => "search brand"
                );
                break;
            
            case "restriction_brand_list" :
                
                return array(
                    'elementdetails' => array(
                        'multiple'      => true,
                        'attribs'       => array(
                            'style'     => 'width: 297px; height: 250px;',
                            'size'      => 15,
                            'onChange'  => "callList(this.value, this.id, 'brandlist', 'RESTRICTION_BRAND_LIST_OFFSET_SIZE', '#restriction_brand_selected'); return false;",
                            'onDblClick'=> "listbox_moveacross('restriction_brand_list', 'restriction_brand_selected', 'restriction_brand_list', 'add');"
                        )
                    ),
                    'isArray'        => true
                );
                break;            
            
            case "restriction_brand_selected" :
                
                return array(
                    'elementdetails' => array(
                        'multiple'      => true,
                        'attribs'       => array(
                            'style'     => 'width: 297px; height: 250px;',
                            'size'      => 15,
                            'onDblClick'=> "listbox_moveacross('restriction_brand_selected', 'restriction_brand_list', 
                                'restriction_brand_list', 'remove');
                                listbox_selectall('restriction_brand_list',false); 
                                sortSelect(this.form['restriction_brand_list'], true);"
                        )
                    ),
                    'isArray'        => true
                );
                break;
            
            case "restriction_brand_part_selector_brand_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 120px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_brand_part_selector_brand_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_brand_part_brand_list', 'brandlist', 
                            'RESTRICTION_BRAND_PART_BRAND_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_BRAND_PART_BRAND_LIST_SEARCH', 
                            'RESTRICTION_BRAND_PART_BRAND_LIST_OFFSET_SIZE', '#restriction_brand_part_brand_list', 
                            '#restriction_brand_part_selected', event); return false;"
                    ),
                    'value' => "search brand"
                );
                break;
            
            case "restriction_brand_part_brand_list" :
                
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 250px;',
                            'size'      => 15,
                            'onChange'  => "callList(this.value, this.id, 'brandlist', 'RESTRICTION_BRAND_PART_BRAND_LIST_OFFSET_SIZE', 
                                '#restriction_brand_part_selected', true, '#restriction_brand_part_part_list', 'RESTRICTION_BRAND_PART_PART_LIST_OFFSET_SIZE', 
                                'brandpartlist', 'RESTRICTION_BRAND_PART_PART_LIST_SEARCH', 'RESTRICTION_BRAND_PART_PART_LIST_SEARCH_OFFSET_SIZE', 
                                '#restriction_brand_part_selector_part_search'); return false;"
                        )
                    ),
                    'isArray'        => false
                );
                break;            

            case "restriction_brand_part_selector_part_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 120px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_brand_part_selector_part_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_brand_part_part_list', 'brandpartlist', 
                            'RESTRICTION_BRAND_PART_PART_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_BRAND_PART_PART_LIST_SEARCH', 
                            'RESTRICTION_BRAND_PART_PART_LIST_OFFSET_SIZE', '#restriction_brand_part_part_list', 
                            '#restriction_brand_part_selected', event, 'restriction_brand_part_brand_list'); return false;"
                    ),
                    'value' => "search part"
                );
                break;                  
            
            case "restriction_brand_part_part_list" :
                
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 250px;',
                            'size'      => 15,
                            'multiple'  => 'multiple',
                            "onChange"  => "brandPartCallList(this.value, this.id, 'brandpartlist', 'RESTRICTION_BRAND_PART_PART_LIST_OFFSET_SIZE', 
                                'restriction_brand_part_brand_list', '#restriction_brand_part_selected');",
                            "onDblClick"=> "secondLevelCheck('partlist', this.value);"
                        )
                    ),
                    'isArray'        => true
                );
                break;            
            
            case "restriction_brand_part_selected" :

                return array(
                    'elementdetails' => array(
                        'multiple'      => true,
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 250px;',
                            'size'      => 15,
                            'onDblClick'=> "listbox_moveacross('restriction_brand_part_selected', 'restriction_brand_part_part_list', 
                                'restriction_brand_part_brand_list', 'remove', 'restriction_part_list', 'restriction_part_selected'); 
                                listbox_selectall('restriction_brand_part_selected', false); 
                                sortSelect(this.form['restriction_brand_part_part_list'], true);"
                        )
                    ),
                    'isArray'        => true
                );
                break;        

            case "restriction_part_brand_selector_part_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 120px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_part_brand_selector_part_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_part_brand_part_list', 'partlist', 
                            'RESTRICTION_PART_BRAND_PART_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_PART_BRAND_PART_LIST_SEARCH', 
                            'RESTRICTION_PART_BRAND_PART_LIST_OFFSET_SIZE', '#restriction_part_brand_part_list', 
                            '#restriction_part_brand_selected', event); return false;"
                    ),
                    'value' => "search part"
                );
                break;    
            
            case "restriction_part_brand_part_list" :
                
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 250px;',
                            'size'      => 15,
                            'onChange'  => "callList(this.value, this.id, 'partlist', 'RESTRICTION_PART_BRAND_PART_LIST_OFFSET_SIZE', 
                                '#restriction_part_brand_selected', true, '#restriction_part_brand_brand_list', 
                                'RESTRICTION_PART_BRAND_BRAND_LIST_OFFSET_SIZE', 'partbrandlist', 'RESTRICTION_PART_BRAND_BRAND_LIST_SEARCH', 
                                'RESTRICTION_PART_BRAND_BRAND_LIST_SEARCH_OFFSET_SIZE', '#restriction_part_brand_selector_brand_search'); 
                                return false;"
                        )
                    ),
                    'isArray'        => false
                );
                break;
            
            case "restriction_part_brand_selector_brand_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 120px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_part_brand_selector_brand_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_part_brand_brand_list', 'partbrandlist', 
                            'RESTRICTION_PART_BRAND_BRAND_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_PART_BRAND_BRAND_LIST_SEARCH', 
                            'RESTRICTION_PART_BRAND_BRAND_LIST_OFFSET_SIZE', '#restriction_part_brand_brand_list', 
                            '#restriction_part_brand_selected', event, 'restriction_part_brand_part_list'); return false;"
                    ),
                    'value' => "search brand"
                );
                break;             
        
            case "restriction_part_brand_brand_list" :
              
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 250px;',
                            'multiple'  => 'multiple',
                            'size'      => 15,
                            'onChange'  => "brandPartCallList(this.value, this.id, 'partbrandlist', 'RESTRICTION_PART_BRAND_BRAND_LIST_OFFSET_SIZE', 
                                'restriction_part_brand_part_list', '#restriction_part_brand_selected'); return false;",
                            'onDblClick'=> "secondLevelCheck('brandlist', this.value);"
                        )
                    ),
                    'isArray'        => true
                );
                break;
        
            case "restriction_part_brand_selected" :

                return array(
                    'elementdetails' => array(
                        'multiple'      => true,
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 250px;',
                            'size'      => 15,
                            'onDblClick'=> "listbox_moveacross('restriction_part_brand_selected', 'restriction_part_brand_brand_list', 
                                'restriction_part_brand_part_list', 'remove');
                                listbox_selectall('restriction_part_brand_selected', false); 
                                sortSelect(this.form['restriction_part_brand_brand_list'], true);"
                        )
                    ),
                    'isArray'        => true
                );
                break;          

            case "restriction_sku_selector_brand_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 120px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_sku_selector_brand_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_sku_brand_list', 'brandlist', 
                            'RESTRICTION_SKU_BRAND_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_SKU_BRAND_LIST_SEARCH', 
                            'RESTRICTION_SKU_BRAND_LIST_OFFSET_SIZE', '#restriction_sku_brand_list', 
                            '#restriction_sku_selected', event); return false;"
                    ),
                    'value' => "search brand"
                );
                break;
          
            case "restriction_sku_brand_list" :
              
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 400px;',
                            'size'      => 15,
                            'onChange'  => "skuList(this.id, this.value); ",
                            'onDblClick'=> "secondLevelCheck('brandlist', this.value);return false;"
                        )
                    ),
                    'isArray'        => false
                );
                break;
            
            case "restriction_sku_selector_sku_usap_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 112px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_sku_selector_sku_usap_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_sku_sku_usap_list', 'brandskulist', 
                            'RESTRICTION_SKU_SKU_USAP_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_SKU_SKU_USAP_LIST_SEARCH', 
                            'RESTRICTION_SKU_SKU_USAP_LIST_OFFSET_SIZE', '#restriction_sku_sku_usap_list', 
                            '#restriction_sku_selected', event, 'restriction_sku_brand_list', 'usap'); return false;"
                    ),
                    'value' => "search sku"
                );
                break;            
           
            case "restriction_sku_sku_usap_list" :
              
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 104px;',
                            'size'      => 15,
                            'multiple'  => 'multiple',
                            'onChange'  => "skuCallList(this.value, this.id, 'brandskulist', 
                                'RESTRICTION_SKU_SKU_USAP_LIST_OFFSET_SIZE', 'usap'); return false;",
                            'onDblClick'=> "secondLevelCheck('usap', this.value); return false;"
                        )
                    ),
                    'isArray'        => true
                );
                break;

            case "restriction_sku_selector_sku_jcw_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 120px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_sku_selector_sku_jcw_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_sku_sku_jcw_list', 'brandskulist', 
                            'RESTRICTION_SKU_SKU_JCW_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_SKU_SKU_JCW_LIST_SEARCH', 
                            'RESTRICTION_SKU_SKU_JCW_LIST_OFFSET_SIZE', '#restriction_sku_sku_jcw_list', 
                            '#restriction_sku_selected', event, 'restriction_sku_brand_list', 'jcw'); return false;"
                    ),
                    'value' => "search sku"
                );
                break;             

            case "restriction_sku_sku_jcw_list" :
              
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 104px;',
                            'size'      => 15,
                            'multiple'  => 'multiple',
                            'onChange'  => "skuCallList(this.value, this.id, 'brandskulist', 
                                'RESTRICTION_SKU_SKU_JCW_LIST_OFFSET_SIZE', 'jcw'); return false;",
                            'onDblClick'=> "secondLevelCheck('jcw', this.value); return false;"
                        )
                    ),
                    'isArray'        => true
                );
                break;

            case "restriction_sku_selector_sku_stt_search" :

                return array(
                    'attribs' => array(
                        'style'   => 'width: 120px; color:#999; font:inherit !important;',
                        'onFocus' => 'clearTextBox("#restriction_sku_selector_sku_stt_search"); return false;',
                        'onKeyUp' => "searchBox('direct', this.value, '#restriction_sku_sku_stt_list', 'brandskulist', 
                            'RESTRICTION_SKU_SKU_STT_LIST_SEARCH_OFFSET_SIZE', 'RESTRICTION_SKU_SKU_STT_LIST_SEARCH', 
                            'RESTRICTION_SKU_SKU_STT_LIST_OFFSET_SIZE', '#restriction_sku_sku_stt_list', 
                            '#restriction_sku_selected', event, 'restriction_sku_brand_list', 'stt'); return false;"
                    ),
                    'value' => "search sku"
                );
                break;              
           
            case "restriction_sku_sku_stt_list" :
              
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 104px;',
                            'size'      => 15,
                            'multiple'  => 'multiple',
                            'onChange'  => "skuCallList(this.value, this.id, 'brandskulist', 
                                'RESTRICTION_SKU_SKU_STT_LIST_OFFSET_SIZE', 'stt'); return false;",
                            'onDblClick'=> "secondLevelCheck('stt', this.value); return false;"
                        )
                    ),
                    'isArray'        => true
                );
                break;
          
            case "restriction_sku_selected" :
              
                return array(
                    'elementdetails' => array(
                        'attribs'       => array(
                            'style'     => 'width: 197px; height: 400px;',
                            'size'      => 15,
                            'multiple'  => 'multiple',
                            'onDblClick'=> "sku_listbox_moveacross('restriction_sku_selected', 'restriction_sku_sku_usap_list', 
                                'restriction_sku_brand_list', 'remove');"
                        )
                    ),
                    'isArray'        => true
                );
                break;
        }
        
    }
    
    
}
