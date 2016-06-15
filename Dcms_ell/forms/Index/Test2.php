<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Index_Test2 extends Dcms_Form_DcmsForm {

    public function init() {
//        $this->setDisableLoadDefaultDecorators(true);

        $this->setName('Dcms_Index_Test2');

        $couponName = $this->createElement('text', 'coupon_name');
        $couponName->setLabel('Coupon Name')
                ->setAttribs(array('size' => 25, 'class' => 'labelblock'))
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);

        $this->columnarListSetDecorator($couponName);

        $typeList = array('percent' => "Percent", 'dollar' => "Dollar");

        $type = $this->createElement('select', 'type');
        $type->setLabel('Type')
                ->setAttrib('class', 'select labelblock')
                ->addMultiOptions($typeList)
                ->setRequired(true);

        $this->columnarListSetDecorator($type);
        $value = $this->createElement('text', 'value');
        $value->setLabel('Value')
                ->setAttribs(array('size' => 25, 'class' => 'labelblock'))
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarListSetDecorator($value);

        $qualifier = $this->createElement('text', 'qualifier');
        $qualifier->setLabel('Qualifier')
                ->setAttribs(array('size' => 25, 'class' => 'labelblock'))
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarListSetDecorator($qualifier);

        $ceil = $this->createElement('text', 'ceiling');
        $ceil->setLabel('Ceiling')
                ->setAttribs(array('size' => 25, 'class' => 'labelblock ceil'))
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarListSetDecorator($ceil);
        $this->addElements(
                array(
                    $couponName,
                    $type,
                    $value,
                    $qualifier,
                    $ceil
        ));
        $this->addDisplayGroup(array(
            'coupon_name',
            'type',
            'value',
            'qualifier',
            'ceiling'
                ), 'coupons1', array('legend' => 'Coupons info'));

        $coupons1 = $this->getDisplayGroup('coupons1');
        $coupons1->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'ul', 'class' => 'clearfix bluebackground', 'id' => 'coupon'))
        ));

        $applycoupontoList = array(
            'online' => 'Online',
            'offline' => 'Offline',
            'both' => 'Both'
        );

        $applycouponto = $this->createElement('select', 'applycouponto');
        $applycouponto->setLabel('Appy Coupon to:')
                ->setAttrib('class', 'select')
                ->addMultiOptions($applycoupontoList)
                ->setRequired(true);
        $this->columnarListSetDecorator($applycouponto);
        $siteList = array(
            'apw' => "autopartswarehouse.com",
            'cs' => "car-stuff.com",
            'ia' => "innerauto.com",
            'jcwhitney.com' => "jcwhitney.com",
            'pt' => "partstrain.com",
            'rp' => "racepages.com",
            'stylintrucks.com' => "stylintrucks.com",
            'tpb' => "thepartsbin.com",
            'uap' => "usautoparts.net"
        );
        $site = $this->createElement('select', 'site');
        $site->setLabel('Site:')
                ->setAttrib('class', 'select')
                ->addMultiOptions($siteList)
                ->setRequired(true);
        $this->columnarListSetDecorator($site);

        $applydiscounttoList = array(
            'order_total' => "Order Total",
            'sub_total' => "Sub-total",
            'shipping' => "Shipping",
            'tax' => "Tax",
            'order_line_item' => "Order Line Item"
        );
        $applydiscountto = $this->createElement('select', 'applydiscountto');
        $applydiscountto->setLabel('Appy Discount to:')
                ->setAttrib('class', 'select')
                ->addMultiOptions($applydiscounttoList)
                ->setRequired(true);

        $this->columnarListSetDecorator($applydiscountto);

        $this->addElements(
                array(
                    $applycouponto,
                    $site,
                    $applydiscountto
        ));

        $this->addDisplayGroup(array(
            'applycouponto',
            'site',
            'applydiscountto'
                ), 'coupons2', array('legend' => 'Coupons info'));

        $coupons2 = $this->getDisplayGroup('coupons2');
        $coupons2->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'ul', 'id' => 'coupon', 'class' => 'clearfix'))
        ));

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


        $coupons3 = $this->getDisplayGroup('coupons3');
        $coupons3->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'ul', 'id' => 'coupon', 'class' => 'clearfix'))
        ));

        $expirationRadio = array(
            'non_expiring' => "Non-Expiring",
            'expiring' => "Expiring",
            'recurring' => "Recurring",
            'one_time_use' => "One Time Use",
            'first_x_users' => "First X Users"
        );
        $expiration = $this->createElement('radio', 'expiration');
        $expiration->addMultiOptions($expirationRadio)
                ->setRequired(true)
                ->setSeparator('</li><li>');
        
        $this->columnarListSetDecorator($expiration);

        $this->addElements(
                array(
                    $expiration
        ));

        $this->addDisplayGroup(array(
            'expiration'
                ), 'coupons4', array('legend' => 'Coupons info'));

        $coupons4 = $this->getDisplayGroup('coupons4');
        $coupons4->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'ul', 'id' => 'coupon', 'class' => 'clearfix'))
        ));
        
        
        
        $applyrestrictionCheckbox = $this->createElement('checkbox', 'applyrestriction_checkbox');
        $applyrestrictionCheckbox->setLabel('Apply template restrictions? ');
        $this->columnarListSetDecorator($applyrestrictionCheckbox);

        $applyrestrictionList = array('percent' => "Percent", 'dollar' => "Dollar");

        $applyrestriction = $this->createElement('select', 'applyrestriction');
        $applyrestriction->setAttrib('class', 'select labelblock')
                ->addMultiOptions($applyrestrictionList)
                ->setRequired(true);

        $this->columnarListSetDecorator($applyrestriction);
        
        $this->addElements(
                array(
                    $applyrestrictionCheckbox,
                    $applyrestriction,
        ));
        $this->addDisplayGroup(array(
            'applyrestriction_checkbox',
            'applyrestriction',
                ), 'coupons5', array('legend' => 'Coupons info'));

        $coupons5 = $this->getDisplayGroup('coupons5');
        $coupons5->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'ul', 'class' => 'clearfix bluebackground', 'id' => 'coupon'))
        ));
    }

}
?>


