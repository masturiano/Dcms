<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Couponowners_Update extends Dcms_Form_DcmsForm {
    protected $_data;
    
    public function init() {
        
    }
    
    public function populateForm($data) {
        $this->_data = $data;
        
        $this->addElement('hidden', 'ownerHidden', array('value' => (isset($this->_data['owner'])) ? $this->_data['owner'] : ""));
        
        $this->addElement('text', 'owner', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Coupon Owner:',
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'required' => true,
            'value' => (isset($this->_data['owner'])) ?  $this->_data['owner'] : null,
            'attribs' => array(
                'size' => 50,
                'class' => 'uniform',
                'onKeypress' => 'return letternumber(event);',
                'maxlength' => 60
            )
        ));
        
        $this->addElement('button', 'cancel', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Cancel', 
            'attribs' => array(
                'class' => 'uniform',
                'onclick' => "location.href='/dcms/couponowners/index';"
             )
        ));

        $this->addElement('submit', 'owner_update', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Update Coupon Owner', 
            'attribs' => array(
                'class' => 'uniform',
                'onclick' => "return validateForm();"
             )
        ));
    }
}

?>