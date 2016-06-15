<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Template_Search extends Zend_Form {

    public function init() {

         $this->setName('Dcms_Form_Template_Search');
        /* Template Name Text Element
         */
        $text = array(
                    'label'      => 'Search:',
                    'filters'    => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'value'  => "Template Name" ,
                'required'   => true,
                'attribs'=> array(
                    'maxlength' => 30,
                    'size'   => 30,
                    'class'  => 'uniform',
                    'style'  => 'background-color: #FFF !important;',
                    
                ));
        $textElement = $this->createElement('text', 'template_name', $text);
        $this->addElement($textElement);
        
        /* Template Search Button Element
         */
        $button = array(
                    'label'      => '',
                    'filters'    => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required'   => true,
                'attribs'=> array(
                    'maxlength' => 30,
                    'size'   => 30,
                    'class'  => 'uniform',
                    'style'  => 'background-color: #FFF !important;',
                    
                ));
        $buttonElement = $this->createElement('submit', 'search', $button)
            ->removeDecorator('Label');
        $this->addElement($buttonElement);
        
                
        
    }
    
   
    
    

}
?>


