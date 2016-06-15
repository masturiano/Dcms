<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Coupon_Addotucoupon extends Dcms_Form_DcmsForm {

    public function init() {        
        parent::init(); {
            $this->addElement('text', 'name', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Batch Name', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 20,
                    'class' => 'uniform text',
					'maxlength' => 8,
					'onKeyPress' => "return letternumber(event)"
                )
            ));
            
            $this->addElement('text', 'description', array(
                'decorators' => array('ViewHelper'),
                'label' => 'Description',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 50,
                    'class' => 'uniform text',
                    'maxlength' => 25,
                    'onKeyPress' => "return letternumber(event, null, true)"
                )
            ));
            
            $this->addElement('checkbox', 'generate', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Generate New Batch', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                )
            ));
            
            $this->addElement('select', 'type', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Type', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'class' => 'select labelblock',
                    'style' => 'width:70px'
                )
            ));
            
            $this->addElement('text', 'discountVal', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Value', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 5,
                    'class' => "uniform text",
                    'onKeyPress' => "return letternumber(event, true)",
                    'maxlength' => '5',
                    'name' => "discountVal[]"
                )
            ));
            
            $this->addElement('text', 'amountQualifier', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Qualifier', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 5,
                    'class' => "uniform text",
                    'onKeyPress' => "return letternumber(event, true)",
                    'maxlength' => '7',
                    'name' => "amountQualifier[]"
                )
            ));
            
            $this->addElement('text', 'ceilingAmount', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Ceiling', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 5,
                    'class' => "uniform text",
                    'onKeyPress' => "return letternumber(event, true)",
                    'maxlength' => '5',
                    'name' => "ceilingAmount"
                )
            ));
            
            $this->addElement('image', 'plus', array(
                'decorators' => array('ViewHelper'), 
                'attribs' => array(
                    'src' => "/assets/img/plus.gif", 
                    'onclick' => "bc_SplitTableRow(this); return false;"
                )
            ));
            
            $this->addElement('image', 'minus', array(
                'decorators' => array('ViewHelper'), 
                'attribs' => array(
                    'src' => "/assets/img/minus.gif", 
                    'onclick' => "deleteRow(this.parentNode.parentNode.rowIndex); return false;",
                )
            ));
            
            $this->addElement('hidden', 'last_coupon_field_id', array('value' => '1'));
            
            $this->addElement('select', 'class', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Apply coupon to:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'class' => 'select labelblock',
                    'style' => 'width:130px'
                 )
            ));

            $this->addElement('select', 'siteId', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Site:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'class' => 'select labelblock',
                    'style' => 'width:200px',
//                    'onchange' => 'getChannelCodes();'
                 )
            ));
            
            $this->addElement('select', 'channelCode', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Channel Code:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'class' => 'select labelblock',
                    'style' => 'width:50px'
                 )
            ));
            
            $this->addElement('select', 'couponAppliesTo', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Apply discount to:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'class' => 'select labelblock',
                    'style' => 'width:150px'
                 )
            ));
            
            $this->addElement('select', 'owner', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Owner:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'class' => 'select labelblock',
                    'style' => 'width:200px'
                 )
            ));
            
            $this->addElement('checkbox', 'appeasement', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Appeasement?', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                )
            ));
            
            $this->addElement('text', 'startDate', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Start Date:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'readonly' => 'readonly',
                'attribs' => array(
                    'size' => 30,
                    'class' => 'uniform text'
                )
            ));
            
            $this->addElement('text', 'endDate', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'End Date:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'readonly' => 'readonly',
                'attribs' => array(
                    'size' => 30,
                    'class' => 'uniform text'
                )
            ));
            
            $this->addElement('text', 'initialQuantity', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Number of Coupons:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 10,
                    'class' => "uniform text",
                    'onKeyPress' => "return letternumber(event, true)",
                    'maxlength' => '7',
                    'name' => "initialQuantity"
                )
            ));

            # Name: Del # Date Modify: April 2015 #Start
            $this->addElement('text', 'lowerLimit', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Alert me when coupons are below:', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 10,
                    'class' => "uniform text",
                    'onKeyPress' => "return letternumber(event, true)",
                    'maxlength' => '7',
                    'name' => "lowerLimit"
                )
            ));
            # Name: Del # Date Modify: April 2015 #End
            
            $this->addElement('checkbox', 'enabledisable_restrictions', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Apply template restrictions?', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'attribs' => array(
                    'onclick' => "return enableDisable();"
                )
            ));
            
            $this->addElement('select', 'restrictions', array(
                'decorators' => array('ViewHelper'), 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'disabled' => 'disabled',
                'attribs' => array(
                    'class' => 'select labelblock',
                    'style' => 'width:250px'
                 )
            ));
            
            $this->addElement('checkbox', 'publish', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Publish?', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                )
            ));

            # Name: Del # Date Modify: April 2015 #Start
            $this->addElement('checkbox', 'dispense', 
                array(
                    'decorators' => array('ViewHelper'), 
                    'label' => 'For Auto Dispense?', 
                    'filters' => array(
                        array('StripTags'),
                        array('StringTrim')
                    ),
                    'attribs' => array(
                        //'checked' => (isset($this->_data['dispense'])) ? true : false
                        'checked' => (isset($this->_data['dispense'])) ? $this->_data['dispense'] : false
                    )
                    // 'checkedValue' => 'Y',
                    // 'uncheckedValue' => 'N'
                )
            );
            
            $this->addElement('radio', 'gated', 
                array(
                'decorators' => array(
                    'ViewHelper',
                    'Errors',
                    'Description',
                    array(
                        'HtmlTag', 
                        array(
                            'tag' => 'dd',
                            'style' => 'float: right; width:90px',
                        )
                    ),
                ),
                //'label'      => 'Restrictive:',
                //'required'   => true,
                //'attribs' => array(
                'id'=>'gated',
                //),
                'multioptions' => array(
                    '1' => ' Gated',
                    '0' => ' Non-Gated',
                ),
                'value' => '1',
            ));
            # Name: Del # Date Modify: April 2015 #End

            /*
            $this->addElement('submit', 'cancel', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Cancel', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'attribs' => array(
                    'class' => 'uniform',
                    'onclick' => "location.href='otucoupon';"
                 )
            ));*/
            
            $this->addElement('submit', 'coupon_add', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Save Coupon', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'attribs' => array(
                    'class' => 'uniform',
                    'onclick' => "return validateForm();"
                 )
            ));
        }
    }

}

?>