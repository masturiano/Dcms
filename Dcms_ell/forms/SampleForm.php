<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Hello_Form_SampleForm extends USAP_Form {

    public function init() {
        parent::init(); {
            $this->addElement('text', 'firstname', array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Firstname:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );
            
             $this->addElement('text', 'lastname', array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Lastname:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );
             
             $this->addSubmitButtons("Submit");
        }
    }

}

?>
