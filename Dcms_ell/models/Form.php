<?php

class Dcms_Model_Form extends USAP_Form {

    /**
     *
     * @param type $control
     * @param type $label
     * @param type $single
     * @param type $readonly 
     */
    public function addProjectSelect($control = 'project', $label = "Select Project", $single = true, $readonly = false) {
        /** @var MongoDB $mongodb  */
        $mongodb = Zend_Registry::get('mongodb');

        $coll = $mongodb->selectCollection('spinner_projects');

        $cProjects = $coll->find(array('projstatus' => 'active')
                )->sort(array('project' => 1));

        $options = array();

        foreach ($cProjects as $project) {
            $options[Utility::keyNormalize($project['project'])] = $project['project'];
        }

        $this->_addSelectControl($options, $control, $label, $single, $readonly);
    }

    /**
     *
     * @param type $control
     * @param type $label
     * @param type $single
     * @param type $readonly 
     */
    public function addProjectStatusSelect($control = 'status', $label = "Select Status", $single = true, $readonly = false) {
        $options = array('active' => 'Active', 'archived' => 'Archived');

        $this->_addSelectControl($options, $control, $label, $single, $readonly);
    }

    /**
     *
     * @param type $control
     * @param type $label
     * @param type $single
     * @param type $readonly 
     */
    public function addTypeSelect($control = 'type', $label = "Type", $single = true, $readonly = false) {
        /** @var MongoDB $mongodb  */
        $options = array(
            "percent" => "Percent",
            "dollar" => "dollar"
        );

        $this->_addSelectControl($options, $control, $label, $single, $readonly);
    }

    public function addSiteSelect($control = 'site', $label = "Site", $single = true, $readonly = false) {
        /** @var MongoDB $mongodb  */
//All Sites 	APW 	CS 	CPW 	IA 	jcwhitney.com 	overnightautoparts.com 	PT 	RP 	stylintrucks.com 	TPB 	UAP 
        $options = array(
            "any" => "Any Sites",
            "apw" => "APW",
            "cs" => "CS",
            "cpw" => "CPW",
            "ia" => "IA",
            "jcwhitney.com" => "jcwhitney.com",
            "overnightautoparts.com" => "overnightautoparts.com",
            "pt" => "PT",
            "rp" => "RP",
            "stylintrucks.com" => "stylintrucks.com",
            "tpb" => "TPB",
            "UAP" => "UAP",
        );

        $this->_addSelectControl($options, $control, $label, $single, $readonly);
    }

    public function addApplytoSelect($control = 'applyto', $label = "Apply Coupon to", $single = true, $readonly = false) {
        /** @var MongoDB $mongodb  */
//All Sites 	APW 	CS 	CPW 	IA 	jcwhitney.com 	overnightautoparts.com 	PT 	RP 	stylintrucks.com 	TPB 	UAP 
        $options = array(
            "online" => "Online",
            "offline" => "Offline",
            "both" => "Both",
        );

        $this->_addSelectControl($options, $control, $label, $single, $readonly);
    }

    public function addApplyDiscounttoSelect($control = 'applydiscountto', $label = "Apply Discount Coupon to", $single = true, $readonly = false) {
        /** @var MongoDB $mongodb  */
//A. Order Total
//B. Sub-total
//C. Shipping
//D. Tax
//E. Order Line Item        
        $options = array(
            "order_total" => "Order Total",
            "sub_total" => "Sub-total",
            "tax" => "Tax",
            "order_line_item" => "Order Line Item",
        );

        $this->_addSelectControl($options, $control, $label, $single, $readonly);
    }

    public function addExpirationRadio($control = 'expiration', $label = "Expiration", $single = true, $readonly = false) {
//            $options, $control, $label, $single, $readonly
//         if ($single) {
//            $default = "";
//        } else {
//            $default = array();
//        }
        $options = array(
            "non_expiring" => "Non Expiring",
            "expiring" => "Expiring",
            "recurring" => "Recurring",
            "one_time_use" => "One time use",
            "first_x_users" => "First X Users",
        );
        $controldata = array(
            'decorators' => $this->_usap_elementDecorators,
            'label' => $label . ':',
            'validators' => array(
                array('NotEmpty')
            ),
            'multiOptions' => $options,
            'required' => true,
            'value' => isset($this->_data[$control]) ? $this->_data[$control] : array(),
            'Attribs' => array(
                'class' => 'uniform',
                'width' => 1,
                'style' => 'font-weight:strong'
            )
        );
        $this->addElement('radio', $control, $controldata);
//        if ($readonly) {
//            $controldata['readonly'] = true;
//        }
//        if ($single) {
//            $this->addElement('select', $control, $controldata);
//        } else {
//            $controldata['Attribs']['class'] = "multiselect css-block-reset";
//            $this->addElement('multiselect', $control, $controldata);
//        }
    }
    
    
    
    

}