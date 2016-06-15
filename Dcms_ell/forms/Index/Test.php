<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Index_Test extends Dcms_Form_DcmsForm {


    public function init() {
        $this->setDisableLoadDefaultDecorators(true);
        
        $this->setName('Dcms_Index_Test');

        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'id' => 'couponTable')),
            'Form'
        ));

        $couponName = $this->createElement('text', 'coupon_name');
        $couponName->setLabel('Coupon Name')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);

        $this->columnarDivSetDecorator($couponName);

        $typeList = array('percent' => "Percent", 'dollar' => "Dollar");

        $type = $this->createElement('select', 'type');
        $type->setLabel('Type')
                ->setAttrib('class', 'select')
                ->addMultiOptions($typeList)
                ->setRequired(true);

        $this->columnarDivSetDecorator($type);
        $value = $this->createElement('text', 'value');
        $value->setLabel('Value')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarDivSetDecorator($value);

        $qualifier = $this->createElement('text', 'qualifier');
        $qualifier->setLabel('Qualifier')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarDivSetDecorator($qualifier);

        $ceil = $this->createElement('text', 'ceiling');
        $ceil->setLabel('Ceiling')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarDivSetDecorator($ceil);


        $this->addElements(
                array(
                    $couponName,
                    $type,
                    $value,
                    $qualifier,
                    $ceil
        ));

//        $this->addDisplayGroup(array(
//            'coupon_name',
//            'type',
//            'value',
//            'qualifier',
//            'ceiling'
//                ), 'coupons1', array('legend' => 'Coupons info'));
//
//        $coupons1 = $this->getDisplayGroup('coupons1');
//        $coupons1->setDecorators(array(
//            'FormElements',
//            array('HtmlTag', array('tag' => 'table', 'openOnly' => true, 'id' => 'coupon-name'))
//        ));
        
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'id' => 'couponTable')),
            'Form'
        ));

        $couponName = $this->createElement('text', 'coupon_name2');
        $couponName->setLabel('Coupon Name')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);

        $this->columnarDivSetDecorator($couponName);

        $typeList = array('percent' => "Percent", 'dollar' => "Dollar");

        $type = $this->createElement('select', 'type2');
        $type->setLabel('Type')
                ->setAttrib('class', 'select')
                ->addMultiOptions($typeList)
                ->setRequired(true);

        $this->columnarDivSetDecorator($type);
        $value = $this->createElement('text', 'value2');
        $value->setLabel('Value')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarDivSetDecorator($value);

        $qualifier = $this->createElement('text', 'qualifier2');
        $qualifier->setLabel('Qualifier')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarDivSetDecorator($qualifier);

        $ceil = $this->createElement('text', 'ceiling2');
        $ceil->setLabel('Ceiling')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 5))
                ->setValue('')
                ->setRequired(true);
        $this->columnarDivSetDecorator($ceil);


        $this->addElements(
                array(
                    $couponName,
                    $type,
                    $value,
                    $qualifier,
                    $ceil
        ));
    }

}
?>


