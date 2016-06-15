<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Coupon_Checkbox extends Dcms_Form_DcmsForm {

    public function init() {
        
    }


    public function createCheckbox($name = "name", $label = "label"){
        $checkbox = $this->createElement('checkbox', $name);
        $checkbox->setLabel($label);

        $checkbox->setDecorators(array('ViewHelper'));
        return $checkbox;
    }
    
    

}
?>


