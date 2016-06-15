<?php

/* * * changed again ** */

class Dcms_Form_Index_Spin extends USAP_Form {

    public function init() {
        parent::init();


        /** @var Zend_View $view  */
        $view = Zend_Registry::get('view');

        // Make the dynamic header field exist if it does not
        if (!isset($view->dynamicheader)) {
            // This is a change
            $view->dynamicheader = "";
        }

        $view->dynamicheader .= <<<EOD
 <script type="text/javascript" src="/assets/js/jquery/jquery.markitup.js"></script>
<!-- markItUp! toolbar settings -->
<script type="text/javascript" src="/assets/markitup/sets/default/set.js"></script>
<!-- markItUp! skin -->
<link rel="stylesheet" type="text/css" href="/assets/markitup/skins/markitup/style.css" />
<!--  markItUp! toolbar skin -->
<link rel="stylesheet" type="text/css" href="/assets/markitup/sets/default/style.css" />
<script type="text/javascript">
<!--
$(document).ready(function()	{
     	$('#markItUp').markItUp(mySettings);
     	});
-->
</script>
EOD;


        $this->setName('Spinner_Spin_Spin');

        $this->addElement('text', 'required', array(
            'decorators' => $this->getElementDecorators(),
            'label' => '# Required:',
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'validators' => array(
                array('NotEmpty')
            ),
            'required' => true,
            'value' => isset($this->_data['required']) ? $this->_data['required'] : "10",
            'attribs' => array(
                'size' => 35,
                'class' => 'uniform'
            )
                )
        );

        $this->addElement('text', 'threshold', array(
            'decorators' => $this->getElementDecorators(),
            'label' => 'Simularity Threshold:',
            'filters' => array(
                array('StripTags'),
                array('StringTrim')
            ),
            'validators' => array(
                array('NotEmpty')
            ),
            'required' => true,
            'value' => isset($this->_data['threshold']) ? $this->_data['threshold'] : "50",
            'attribs' => array(
                'size' => 35,
                'class' => 'uniform'
            )
                )
        );

        $this->addElement('checkbox', 'checksim', array(
            'decorators' => $this->getElementDecorators(),
            'label' => 'Simularity Check?:',
            'required' => true,
            'checkedValue' => true,
            'uncheckedValue' => false,
            'value' => true
                )
        );
        $this->addElement('hidden', 'check_target_status', array(
                'required' => false,
                'value' => "",
                    )
            );

        $this->addElement('checkbox', 'autopublish', array(
            'decorators' => $this->getElementDecorators(),
            'label' => 'Autopublish:'
                )
        );
       

        $this->addDisplayGroup(
                array('required', 'threshold', 'checksim','autopublish'), 'specgroup', array(
            'disableLoadDefaultDecorators' => true,
            'decorators' => $this->getGroupDecorators(),
            'legend' => "Generation requirements properties",
            'description' =>
            'Enter the number of varients you require, and the maximum simularity tolerated'
                )
        );




        $this->addElement('textarea', 'spintax', array(
            'decorators' => $this->getElementDecorators(),
            'filters' => array(
                array('StringTrim')
            ),
            'validators' => array(
                array('NotEmpty')
            ),
            'required' => true,
            'value' => isset($this->_data['spintax']) ? $this->_data['spintax'] : "",
            'attribs' => array(
                'rows' => 20,
                'cols' => 100,
                'size' => 80,
                'id' => 'markItUp'
            )
                )
        );

        $this->addDisplayGroup(
                array('spintax'), 'generalgroup', array(
            'disableLoadDefaultDecorators' => true,
            'decorators' => $this->getGroupDecorators(),
            'legend' => "Spintax",
            'description' => 'Place your spintax here, it will be stored with the
                        data item and can be recalled and used to update the generated content'
                )
        );


        $this->addSubmitButtons(array('Save', 'Generate and Save'));
    }

}