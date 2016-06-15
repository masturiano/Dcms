<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Coupon_Updateotucoupon extends Dcms_Form_DcmsForm {
    protected $_data;
    
    public function init() {
        
    }
    
    public function populateForm($data) {
        $this->_data = $data;
        
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['name'])) ? $this->_data['name'] : null,
            'attribs' => array(
                'size' => 20,
                'class' => 'uniform text',
                'maxlength' => 16,
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
                'disabled' => 'disabled',
                'value' => (isset($this->_data['description'])) ? $this->_data['description'] : null,
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
            'disabled' => 'disabled',
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
            'disabled' => 'disabled',
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
            'disabled' => 'disabled',
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['ceilingAmount'])) ? $this->_data['ceilingAmount'] : null,
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
        
        $this->addElement('text', 'class', array(
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['class'])) ? $this->_data['class'] : null,
            'attribs' => array(
                'style' => 'width:100px',
                'class' => 'uniform text'
            )
        ));
        
        $this->addElement('text', 'siteId', array(
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['siteName'])) ? $this->_data['siteName'] : null,
            'attribs' => array(
                'style' => 'width:150px',
                'class' => 'uniform text'
            )
        ));

        $variable['variable'] = $this->_data['siteName']."/redeem/".$this->_data['batchId'];

        /*
        $this->addElement('text', 'site_name', array(
            'label'    => $this->_data['siteName'].'/redeem/'.$this->_data['batchId'],
            'required' => false,
            'attribs' => array(
                'style' => 'width:10px; height:10px; border:10px;',
                'class' => 'uniform text'
            ),
        ));
        */

        /*
        $this->addElement('hidden', 'site_name', array(
            'value' => (isset($this->_data['siteName'])) ? $this->_data['siteName'] : null,
        ));
        */
        
        /*
        $this->addElement('text', 'site_name', array(
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['siteName'])) ? $this->_data['siteName'] : null,
            'attribs' => array(
                'style' => 'width:150px',
                'class' => 'uniform text'
            )
        ));
        */

        $this->addElement('text', 'channelCode', array(
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['channelCode'])) ? $this->_data['channelCode'] : null,
            'attribs' => array(
                'style' => 'width:30px',
                'class' => 'uniform text'
            )
        ));
        
        $couponAppliesTo = array(
            "ORDERTOTAL" => "Order Total", 
            "SUBTOTAL" => "Sub-total", 
            "SHIPPING" => "Shipping", 
            "TAX" => "Tax", 
            "ITEM" => "Order Line Item"
        );
        
        $this->addElement('text', 'couponAppliesTo', array(
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['couponAppliesTo'])) ? $couponAppliesTo[$this->_data['couponAppliesTo']] : null,
            'attribs' => array(
                'style' => 'width:110px',
                'class' => 'uniform text'
            )
        ));
        
        $this->addElement('text', 'owner', array(
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['owner'])) ? $this->_data['owner'] : null,
            'attribs' => array(
                'style' => 'width:160px',
                'class' => 'uniform text'
            )
        ));

        $this->addElement('checkbox', 'appeasement', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Appeasement?', 
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ), 
            'disabled' => 'disabled',
            'attribs' => array(
                'checked' => (isset($this->_data['appeasement'])) ? $this->_data['appeasement'] : false
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['startDate'])) ? $this->_data['startDate'] : null,
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['endDate'])) ? $this->_data['endDate'] : null,
            'attribs' => array(
                'size' => 30,
                'class' => 'uniform text'
            )
        ));

        $this->addElement('text', 'initialQuantity', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Original number of Coupons:', 
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'validators' => array(
                array('NotEmpty')
            ),
            'required' => true,
            'disabled' => 'disabled',
            'value' => (isset($this->_data['initialQuantity'])) ? $this->_data['initialQuantity'] : null,
            'attribs' => array(
                'size' => 10,
                'class' => "uniform text",
                'maxlength' => '7',
                'name' => "initialQuantity"
            )
        ));
        $this->addElement('hidden', 'initialQuantityHidden', array('value' => (isset($this->_data['initialQuantity'])) ? $this->_data['initialQuantity'] : null,));

        $this->addElement('text', 'remainingQuantity', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Number of Unused:', 
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'validators' => array(
                array('NotEmpty')
            ),
            'required' => true,
            'disabled' => 'disabled',
            'value' => (isset($this->_data['remainingQuantity'])) ? $this->_data['remainingQuantity'] : null,
            'attribs' => array(
                'size' => 10,
                'class' => "uniform text",
                'maxlength' => '7',
                'name' => "remainingQuantity"
            )
        ));
        $this->addElement('hidden', 'remainingQuantityHidden', array('value' => (isset($this->_data['remainingQuantity'])) ? $this->_data['remainingQuantity'] : null,));
        
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
            'disabled' => 'disabled',
            'value' => (isset($this->_data['lowerLimit'])) ? $this->_data['lowerLimit'] : null,
            'attribs' => array(
                'size' => 10,
                'class' => "uniform text",
                'onKeyPress' => "return letternumber(event, true)",
                'maxlength' => '7',
                'name' => "lowerLimit"
            )
        ));
        $this->addElement('hidden', 'lowerLimitHidden', array('value' => (isset($this->_data['lowerLimit'])) ? $this->_data['lowerLimit'] : null,));
        # Name: Del # Date Modify: April 2015 #End

        $this->addElement('text', 'setUnused', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Set New Unused:', 
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'validators' => array(
                array('NotEmpty')
            ),
            'required' => true,
            'value' => (isset($this->_data['setUnused'])) ? $this->_data['setUnused'] : null,
            'attribs' => array(
                'size' => 10,
                'class' => "uniform text",
                'maxlength' => '7',
                'name' => "setUnused",
                'onkeyup' => "computeQuantity(this);"
            )
        ));

        $this->addElement('checkbox', 'enabledisable_restrictions', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Apply template restrictions?', 
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'disabled' => 'disabled',
            'attribs' => array(
                'onclick' => "return enableDisable();",
                'checked' => (isset($this->_data['enabledisable_restrictions'])) ? $this->_data['enabledisable_restrictions'] : false
            )
        ));

        $this->addElement('select', 'restrictions', array(
            'decorators' => array('ViewHelper'), 
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
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
            ),
            'attribs' => array(
                'checked' => (isset($this->_data['publish'])) ? $this->_data['publish'] : false
            )
        ));

        # Name: Del # Date Modify: April 2015 #Start
        $this->addElement('checkbox', 'dispense', 
            array(
                'decorators' => array('ViewHelper'), 
                'label' => 'For Auto Dispense', 
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'attribs' => array(
                    'checked' => (isset($this->_data['dispense'])) ? $this->_data['dispense'] : false
                ),
                'disable' => 'disable'
                // 'checkedValue' => 'Y',
                // 'uncheckedValue' => 'N'
            )
        );
        $this->addElement('hidden', 'dispenseHidden', array('value' => $this->_data['dispense'] == true ? 1 : 0));

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
            'disable' => 'disable',
            //'value' => 0
            'value' => $this->_data['gated'] == true ? 1 : 0
        ));
        $this->addElement('hidden', 'gatedHidden', array('value' => $this->_data['gated'] == true ? 1 : 0));
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
        /*
        $this->addElement('button', 'coupon_export', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Export', 
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'attribs' => array(
                'class' => 'uniform',
             )
        ));
        */
        $this->addElement('submit', 'coupon_update', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Update Coupon', 
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
?>