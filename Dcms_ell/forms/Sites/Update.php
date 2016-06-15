<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Sites_Update extends Dcms_Form_DcmsForm {
    protected $_data;
    
    public function init() {
        
    }
    
    public function populateForm($data) {
        $this->_data = $data;
        
        //HIDDEN INPUTS
        $this->addElement('hidden', 'nameHidden', array('value' => (isset($this->_data['name'])) ? $this->_data['name'] : ""));
        $this->addElement('hidden', 'acronymHidden', array('value' => (isset($this->_data['acronym'])) ? $this->_data['acronym'] : ""));
        $this->addElement('hidden', 'siteId', array('value' => (isset($this->_data['siteId'])) ? $this->_data['siteId'] : ""));
        //HIDDEN INPUTS
        
//        $this->addElement('text', 'siteId', array(
//            'decorators' => array('ViewHelper'), 
//            'label' => 'Site ID:',
//            'filters' => array(
//                array('StripTags'),
//                array('StringTrim')
//            ),
//            'validators' => array(
//                array('Digits')
//            ),
//            'required' => true,
//            'value' => (isset($this->_data['siteId'])) ? $this->_data['siteId'] : null,
//            'readonly' => 'readonly', 
//            'attribs' => array(
//                'size' => 10,
//                'class' => 'uniform'
//            )
//        ));

        $this->addElement('text', 'name', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Site Domain:',
//            'filters' => array(
//                array('StripTags'),
//                array('StringTrim')
//            ),
//            'validators' => array(
//                array('NotEmpty')
//            ),
            'required' => true,
            'value' => (isset($this->_data['name'])) ?  $this->_data['name'] : null,
            'attribs' => array(
                'size' => 25,
                'maxlength' => 50,
                'class' => 'uniform'
            )
        ));
        
        $this->addElement('text', 'channelCode', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Channel Code:',
//            'filters' => array(
//                array('StripTags'),
//                array('StringTrim')
//            ),
//            'validators' => array(
//                array('Digits')
//            ),
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
//            'filters' => array(
//                array('StripTags'),
//                array('StringTrim')
//            ),
//            'validators' => array(
//                array('NotEmpty')
//            ),
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
//            'filters' => array(
//                array('StripTags'),
//                array('StringTrim')
//            ),
//            'validators' => array(
//                array('Digits')
//            ),
            'required' => true,
            'value' => (isset($this->_data['acronym'])) ?  $this->_data['acronym'] : null,
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
//            'filters' => array(
//                array('StripTags'),
//                array('StringTrim')
//            ),
            'attribs' => array(
                'checked' => (isset($this->_data['isUsapSite'])) ? $this->_data['isUsapSite'] : false
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

        $this->addElement('submit', 'domain_update', array(
            'decorators' => array('ViewHelper'), 
            'label' => 'Update Domain', 
            'attribs' => array(
                'class' => 'uniform',
                'onclick' => "return validateForm();"
             )
        ));
    }
}

?>