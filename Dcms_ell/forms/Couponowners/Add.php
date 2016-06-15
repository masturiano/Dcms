<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Couponowners_Add extends Dcms_Form_DcmsForm {

    public function init() {
        parent::init(); {
            $this->addElement('text', 'owner', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Coupon Owner:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'required' => true,
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
            
            $this->addElement('submit', 'owner_add', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Save Coupon Owner', 
                'attribs' => array(
                    'class' => 'uniform',
                    'onclick' => "return validateForm();"
                 )
            ));
        }
    }

}

?>