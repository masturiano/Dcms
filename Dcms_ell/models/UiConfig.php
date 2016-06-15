<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Config
 *
 * @author bteves
 */
class Dcms_Model_UiConfig {
    //put your code here
    
    public function getUiConfig($query = array()) {
        
        if($query) {
            $getUiConfig = array();
            $getCollection = $this->_getCollection("dcms_ui_config");
            $getUiConfig = $getCollection->findOne($query);
            return $getUiConfig; 
        } else {
            return false;
        }
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
