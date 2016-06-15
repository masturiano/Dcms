<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Switch
 *
 * @author gconstantino
 */
class Dcms_Model_Switch {
    //put your code here
    
    public function getSwitch() {
        $session = new Zend_Session_Namespace("User");
        $getSwitches = array();
        //if(!isset($session->killswitch)){
            $getCollection = $this->_getCollection("dcms_kill_switch");
            $getSwitches = $getCollection->findOne();
            unset($getSwitches["_id"]);
            $session->killswitch = $getSwitches;
          //}
        Zend_Registry::set('dcms_switch', $session);
        return $session->killswitch; 
    }
    

    
    private function _getMongomultidbResource() {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $mongoResource = $bootstraps['Dcms']->getPluginResource('mongomultidb');
       return $mongoResource;
    }
    
    private function _getCollection($collection) {
        $mongodb = $this->_getMongomultidbResource()->getAdapter('coupon')->getDatabase();
	return $mongodb->selectCollection($collection);
    }
}
?>
