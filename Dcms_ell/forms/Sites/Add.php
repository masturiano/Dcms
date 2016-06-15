<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Sites_Add extends Dcms_Form_DcmsForm {

    public function init() {
        parent::init(); {
//            $this->addElement('text', 'siteId', array(
//                'decorators' => array('ViewHelper'), 
//                'label' => 'Site ID:',
//                'filters' => array(
//                    array('StripTags'),
//                    array('StringTrim')
//                ),
//                'validators' => array(
//                    array('Digits')
//                ),
//                'required' => true,
//                'attribs' => array(
//                    'size' => 10,
//                    'onkeyup' => "javascript:checkNumber(this)",
//                    'class' => 'uniform'
//                )
//            ));
//            
            $this->addElement('text', 'name', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Site Domain:',
//                'filters' => array(
//                    array('StripTags'),
//                    array('StringTrim')
//                ),
//                'validators' => array(
//                    array('NotEmpty')
//                ),
                'required' => true,
                'attribs' => array(
                    'size' => 25,
                    'maxlength' => 50,
                    'class' => 'uniform'
                )
            ));
            
            $this->addElement('text', 'channelCode', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Channel Code:',
//                'filters' => array(
//                    array('StripTags'),
//                    array('StringTrim')
//                ),
//                'validators' => array(
//                    array('Digits')
//                ),
                'required' => true,
                'attribs' => array(
                    'size' => 5,
                    'maxlength' => 3,
                    'onkeyup' => "javascript:checkNumber(this)",
                    'class' => 'uniform',
                    'name' => "channelCode[]"
                )
            ));
            
            $this->addElement('text', 'serverName', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Server Name:',
//                'filters' => array(
//                    array('StripTags'),
//                    array('StringTrim')
//                ),
//                'validators' => array(
//                    array('NotEmpty')
//                ),
                'required' => true,
                'attribs' => array(
                    'size' => 25,
                    'maxlength' => 50,
                    'class' => 'uniform',
                    'name' => "serverName[]"
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
            
            $this->addElement('hidden', 'last_domain_field_id', array('value' => '1'));
            
            $this->addElement('text', 'acronym', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Acronym:',
//                'filters' => array(
//                    array('StripTags'),
//                    array('StringTrim')
//                ),
//                'validators' => array(
//                    array('Digits')
//                ),
                'required' => true,
                'attribs' => array(
                    'size' => 5,
                    'maxlength' => 4,
                    'onKeypress' => 'return letternumber(event);',
                    'class' => 'uniform'
                )
            ));
            
            $this->addElement('checkbox', 'isUsapSite', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Is USAP Site?', 
//                'filters' => array(
//                    array('StripTags'),
//                    array('StringTrim')
//                ),
                'attribs' => array(
                    'checked' => true
                )
            ));
             
            $this->addElement('button', 'cancel', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Cancel',
                'attribs' => array(
                    'class' => 'uniform',
                    'onclick' => "location.href='/dcms/sites';"
                 )
            ));
            
            $this->addElement('submit', 'domain_add', array(
                'decorators' => array('ViewHelper'), 
                'label' => 'Save Domain', 
                'attribs' => array(
                    'class' => 'uniform',
                    'onclick' => "return validateForm();"
                 )
            ));
        }
    }

}

?>