<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Coupon_Delete extends Dcms_Form_DcmsForm {

    public function init() {

        $this->setName('Dcms_Form_Coupon_Delete');
        $this->setAction('/dcms/index/delete/');

        $checkAll = $this->createCheckbox("checkall", "");
        $checkAll->setAttrib('onClick', "toggleCheckbox(this, document.homedelete.checkone)");
        $checkone = $this->createCheckbox("checkone", "")->setAttribs(array(
            'name' => "checkone[]",
                ));
        $domain = $this->createElement('hidden', 'domain');
        $couponCode = $this->createElement('hidden', 'coupon_code');
//        $expirationType = $this->createElement('hidden', 'expiration_type');
//        $appeasement = $this->createElement('hidden', 'appeasement');
//        $couponContainer = $this->createElement("select", "couponContainer")->setAttrib('multiple', "multiple");
        

        $this->addElements(
                array(
                    $checkAll,
                    $checkone,
                    $domain,
                    $couponCode,
//                    $expirationType,
//                    $appeasement
//                    $couponContainer,
        ));
    }

}
?>


