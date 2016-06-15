<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Index_Logindecorator extends Zend_Form {

    public function init() {
        $this->login2();
    }

    private function login1() {
        $this->setMethod('post');



        $username = $this->CreateElement('text', 'username')
                ->setLabel('User Name:');



        $username->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'openOnly' => true))
        ));



        $password = $this->CreateElement('text', 'password')
                ->setLabel('Password');



        $password->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td')),
            array('Label', array('tag' => 'td')),
        ));



        $submit = $this->CreateElement('submit', 'submit')
                ->setLabel('Login');



        $submit->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td',
                    'colspan' => '2', 'align' => 'center')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'closeOnly' => 'true'))
        ));



        $this->addElements(array(
            $username,
            $password,
            $submit
        ));



        $this->setDecorators(array(
            'FormElements',
            array(array('data' => 'HtmlTag'), array('tag' => 'table')),
            'Form'
        ));
    }

    private function login2() {
        $this->setMethod('post');
        $username = $this->CreateElement('text', 'username')
                ->setLabel('User Name:');



        $username->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'openOnly' => true))
        ));



        $password = $this->CreateElement('text', 'password')
                ->setLabel('Password');



        $password->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td')),
            array('Label', array('tag' => 'td')),
        ));


        $email = $this->CreateElement('text', 'username')
                ->setLabel('User Name:')
                ->setDescription('path/to/image');



        $email->setDecorators(array(
            'ViewHelper',
            array('Description', array('tag' => '', 'escape' => false)),
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr'))
        ));



        $password = $this->CreateElement('text', 'password')
                ->setLabel('Password')
                ->setDescription('path/to/image');



        $password->setDecorators(array(
            'ViewHelper',
            array('Description', array('tag' => '', 'escape' => false)),
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr'))
        ));



        $submit = $this->CreateElement('submit', 'submit')
                ->setLabel('Login');



        $submit->setDecorators(array(
            'ViewHelper',
            'Description',
            'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td',
                    'colspan' => '2', 'align' => 'center')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr'))
        ));



        $this->addElements(array(
            $username,
            $password,
            $submit
        ));



        $this->setDecorators(array(
            'FormElements',
            array(array('data' => 'HtmlTag'), array('tag' => 'table')),
            'Form'
        ));
    }

}

?>
