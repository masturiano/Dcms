<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Coupon_Search extends Dcms_Form_DcmsForm {

    public function init() {
        
    }

    public function buildSearchForm() {
        $this->setName('Dcms_Form_Coupon_Search');

        $coupon_name = $this->createElement('text', 'coupon_code');
        $coupon_name->setLabel("Coupon Name:");
        $coupon_name->setAttribs(array(
            'onBlur' => "document.home.submit();",
            'style' => "padding:4px",
			'maxlength' => '16',
            ));
        $coupon_name->setDecorators(array('ViewHelper'));

        $sites = $this->createSelectElement("coupon_domains", "Website:");
        $sites->setAttrib('onChange', "document.home.submit();");
        $sites->setDecorators(array('ViewHelper'));

        $expirationType = $this->createSelectElement("expiration_type", "Coupon Type:");
        $expirationType->setAttrib('onChange', "document.home.submit();");
        $expirationType->setDecorators(array('ViewHelper'));

        $publish_checkbox = $this->createCheckbox("status", "Published")->setAttrib('onClick', "document.home.submit();");
        
        $this->addElements(
                array(
                    $coupon_name,
                    $sites,
                    $expirationType,
                    $publish_checkbox
        ));
    }

    
    

}
?>


