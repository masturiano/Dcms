<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Coupon_Add extends Dcms_Form_DcmsForm {

    public function init() {
        
    }

    public function buildCouponForm() {
        $this->setName('Dcms_Form_Coupon_Add');

        $this->couponDetailsBlock();
        $this->applyCouponToBlock();
        $this->appeasementBlock();
        $this->expirationBlock();
        $this->applyRestrictionBlock();
        $this->expiring();
        $this->recurringBlock();
        $this->firstXUsersField();
        $this->publishBlock();

        $saveButton = $this->createElement('submit', 'save');
        $saveButton->setLabel('Save Coupon');
        $saveButton->setAttrib('onclick', "return validateForm();");
        $this->addElement($saveButton);
    }

    public function couponDetailsBlock() {
        //create first block
        $couponName = $this->createElement('text', 'code');
        $couponName->setLabel('Coupon Name')
                ->setAttribs(
                        array(
                            'size' => 25,
                            'class' => 'labelblock',
                            'maxlength' => '16',
                            'onKeyPress' => "return letternumber(event)"
                ));

        $couponName->setDecorators(array('ViewHelper'));

        $type = $this->createElement('select', 'type');
        $type->setLabel('Type')
                ->setAttrib('class', 'select labelblock');
        $type->setDecorators(array('ViewHelper'));

        $discountVal = $this->createElement('text', 'discountVal');
        $discountVal->setLabel('Value')
                ->setAttribs(
                        array(
                            'size' => 25,
                            'class' => "labelblock",
                            'style' => "width:50px",
                            'onKeyPress' => "return letternumber(event, true)",
                            'maxlength' => '5',
                            'name' => "discountVal[]"))
                ->isArray(true);
        $discountVal->setDecorators(array('ViewHelper'));

        $amountQualifier = $this->createElement('text', 'amountQualifier');
        $amountQualifier->setLabel('Qualifier')
                ->setAttribs(
                        array(
                            'size' => 25,
                            'class' => "labelblock",
                            'style' => "width:50px; display:inline",
                            'onKeyPress' => "return letternumber(event, true)",
                            'maxlength' => '7',
                            'name' => "amountQualifier[]"))
                ->setValue('');

        $amountQualifier->setDecorators(array('ViewHelper'));

        $ceil = $this->createElement('text', 'ceilingAmount');
        $ceil->setLabel('Ceiling')
                ->setAttribs(
                        array(
                            'size' => 25,
                            'class' => 'labelblock',
                            'style' => "width:50px; display:inline",
                            'onKeyPress' => "return letternumber(event, true)",
                            'maxlength' => '5',
                            'name' => "ceilingAmount"))
                ->setValue('');
        $ceil->setDecorators(array('ViewHelper'));

        $plus = $this->createElement('image', 'plus');
        $plus->setAttribs(array('src' => "/assets/img/plus.gif", 'onclick' => "bc_SplitTableRow(this); return false;"));
        $plus->setDecorators(array('ViewHelper'));

        $minus = $this->createElement('image', 'minus');
        $minus->setAttribs(
                array(
                    'src' => "/assets/img/minus.gif",
                    'onclick' => "deleteRow(this.parentNode.parentNode.rowIndex); return false;",
        ));
        $minus->setDecorators(array('ViewHelper'));

        $hidden = $this->createElement('hidden', 'last_coupon_field_id');
        $hidden->setValue('1');
        $hidden->setDecorators(array('ViewHelper'));

		$code_checker = $this->createElement('hidden', 'code_checker');
        $code_checker->setDecorators(array('ViewHelper'));

		$code_live  = $this->createElement('hidden', 'code_live');
		$code_live->setDecorators(array('ViewHelper'));
		
		
        $this->addElements(
                array(
                    $couponName,
                    $type,
                    $discountVal,
                    $amountQualifier,
                    $ceil,
                    $plus,
                    $minus,
                    $hidden,
					$code_checker,
					$code_live
        ));
    }

    public function applyCouponToBlock() {
        //Create second block
        $class = $this->createSelectElement("class", "Apply Coupon to:");

        $this->columnarListSetDecorator($class);

        $site = $this->createSelectElement("site", "Site:");
        $this->columnarListSetDecorator($site);


        $couponAppliesTo = $this->createSelectElement("couponAppliesTo", "Apply Discount to:");
        $this->columnarListSetDecorator($couponAppliesTo);

        $owner = $this->createSelectElement("owner", "Owner");
        $this->columnarListSetDecorator($owner);

        $this->addElements(
                array(
                    $class,
                    $site,
                    $couponAppliesTo,
                    $owner
        ));

        $this->addDisplayGroup(array(
            'class',
            'site',
            'couponAppliesTo',
            'owner'
                ), 'coupons2', array('legend' => 'Coupons info'));

        $couponSecondBlock = $this->getDisplayGroup('coupons2');
        $couponSecondBlock->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'ul', 'id' => 'coupon', 'class' => 'clearfix ver2'))
        ));
    }

    public function appeasementBlock() {
        //Create third block
        $appeasement = $this->createElement('checkbox', 'appeasement');
        $appeasement->setLabel('Appeasement?:');
        $this->columnarListSetDecorator($appeasement);

        $this->addElements(
                array(
                    $appeasement
        ));

        $this->addDisplayGroup(array(
            'appeasement',
                ), 'coupons3', array('legend' => 'Coupons info'));


        $couponThirdBlock = $this->getDisplayGroup('coupons3');
        $couponThirdBlock->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'ul', 'id' => 'coupon', 'class' => 'clearfix'))
        ));
    }

    public function expirationBlock() {
        //Create fourth block
        $expiration = $this->createElement('radio', 'expiration');
        $expiration->setAttribs(
                array(
                    'onclick' => "javascript:toggleExpiration(this.value)"
        ));
        $expiration->setDecorators(array('ViewHelper'))->setSeparator(" ");

        $this->addElements(
                array(
                    $expiration
        ));
    }

    public function applyRestrictionBlock() {
        //Create fifth block
        $applyrestrictionCheckbox = $this->createElement('checkbox', 'applyrestriction_checkbox');
        $applyrestrictionCheckbox->setLabel('Apply template restrictions? ')
                ->setAttrib('onchange', "activate_restriction_opts(this, 'restrictions')");

        $applyrestrictionCheckbox->setDecorators(array('ViewHelper'));

        $restrictions = $this->createSelectElement("restrictions", "", "select labelblock");
        $restrictions->setDecorators(array('ViewHelper'));



        $this->addElements(
                array(
                    $applyrestrictionCheckbox,
                    $restrictions,
        ));
    }

    public function publishBlock() {
        $publish = $this->createElement('checkbox', 'publish');
        $publish->setLabel('Publish?:')->setDecorators(array('ViewHelper'));

        $this->addElement($publish);
    }

    public function expiring() {
        $from = $this->createElement('text', 'from')
                ->setLabel('Start Date')
                ->setAttribs(array(
            'style' => "width:150px;",
            'readonly' => true,
                ));

        $from->setDecorators(array('ViewHelper'));
        $from_min = $this->createElement('text', 'from_min')
                ->setLabel('Start Time')
                ->setAttribs(array(
            'style' => "width:100px;",
            'readonly' => true,
                ));

        $from_min->setDecorators(array('ViewHelper'));

        $to = $this->createElement('text', 'to')
                ->setLabel('End Date')
                ->setAttribs(array(
            'style' => "width:150px;",
            'readonly' => true
                ));
        $to->setDecorators(array('ViewHelper'));

        $to_min = $this->createElement('text', 'to_min')
                ->setLabel('End Time')
                ->setAttribs(array(
            'style' => "width:100px;",
            'readonly' => true
                ));
        $to_min->setDecorators(array('ViewHelper'));

        $free_shipping = $this->createElement('checkbox', 'free_shipping');
        $free_shipping->setLabel('Free Shipping?:')
                ->setAttribs(array(
                    'onclick' => "activateFreeShipping();"
                ))
                ->setDecorators(array('ViewHelper'));

        $this->addElements(
                array(
                    $from,
                    $from_min,
                    $to,
                    $to_min,
                    $free_shipping
        ));
    }

    public function firstXUsersField() {
        $firstXUsers = $this->createElement('text', 'valid_first_x_users')
                ->setAttribs(
                array(
                    'onKeyPress' => "return letternumber(event, true)",
                    'size' => "5",
					'maxlength' => "5",
                ));

        $firstXUsers->setDecorators(array('ViewHelper'));

        $this->addElements(
                array(
                    $firstXUsers
        ));
    }

    public function recurringBlock() {
        $startday = $this->createElement('text', 'startday')
                ->setAttribs(
                array(
                    'onKeyPress' => "return letternumber(event, true)",
                    'size' => "5",
					'maxlength' => "2"
                ));
        $startday->setDecorators(array('ViewHelper'));
        $endday = $this->createElement('text', 'endday')
                ->setAttribs(
                array(
                    'onKeyPress' => "return letternumber(event, true)",
                    'size' => "5",
					'maxlength' => "2"
                ));
        $endday->setDecorators(array('ViewHelper'));
        $recurring_period = $this->createElement('select', 'recurring_period')
                ->setAttrib('class', 'select')
                ->setDecorators(array('ViewHelper'));

        $recurring_period_month = $this->createElement('select', 'recurring_period_month')
                ->setAttrib('class', 'select')
                ->setDecorators(array('ViewHelper'));

        $recurring_period_year = $this->createElement('select', 'recurring_period_year')
                ->setAttrib('class', 'select')
                ->setDecorators(array('ViewHelper'));

        $this->addElements(
                array(
                    $startday,
                    $endday,
                    $recurring_period,
                    $recurring_period_month,
                    $recurring_period_year
        ));
    }

}
?>


