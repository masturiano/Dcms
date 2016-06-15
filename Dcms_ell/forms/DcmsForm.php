<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_DcmsForm extends Zend_Form {

    public function init() {
        
    }

    public function columnarTdSetDecorator(&$element) {
        $element->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array('Label'),
            array('HtmlTag', array('tag' => 'td'))
        ));
    }

    public function columnarDivSetDecorator(&$element) {
        $element->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array('Label'),
            array('HtmlTag', array('tag' => 'div'))
        ));
    }

    public function columnarListSetDecorator(&$element) {
        $element->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array('Label'),
            array('HtmlTag', array('tag' => 'li'))
        ));
    }

    public function createSelectElement($name = "select", $label = "Select", $class = "select") {
        $element = $this->createElement('select', $name);
        !empty($label) ? $element->setLabel($label) : "";
        $element->setAttrib('class', $class);

        return $element;
    }

    public function createCheckbox($name = "name", $label = "label") {
        $checkbox = $this->createElement('checkbox', $name);
        $checkbox->setLabel($label);

        $checkbox->setDecorators(array('ViewHelper'));
        return $checkbox;
    }

}

?>
