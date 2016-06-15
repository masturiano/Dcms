<?php

/**
 * @package User
 * @author gconstantino
 * @version $Id$
 * 
 */
class Dcms_Bootstrap extends Zend_Application_Module_Bootstrap {

    /**
     * Gets the dcms.ini settings found in /Dcms/configs/
     * Please be careful as not to overwrite variable names from other config.ini
     * @uses Zend_Config_Ini
     * @return Zend_Config_Ini 
     */
    public function _initConfig() {
        $options = new Zend_Config_Ini(dirname(__FILE__) . '/configs/dcms.ini', APPLICATION_ENV);
        $this->setOptions($options->toArray());
        return $options;
    }

    protected function _initServices() {
        $config = $this->getResource('config');
        USAP_Service_ServiceAbstract::registerServiceFactory(new Dcms_Service_ServiceFactory($config), 'Dcms_Service_');
    }

    function _initAutoLoadClass() {
        Zend_Loader::autoload('Utility');
    } 
    /*
    protected function _initAcl() {
        $acl = new Dcms_Model_Acl();
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->registerPlugin(
                new Dcms_Plugin_AccessControl($acl));
    }

protected function _initAcl() {
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->registerPlugin(
                new Dcms_Plugin_Auth());
    }
*/  
  /**
     * @uses Zend_Controller_Front()
     */
    function _initPlugins() {
        $bootstrap = $this->getApplication();
        $bootstrap->bootstrap('frontcontroller');
        $front = $bootstrap->getResource('frontcontroller');
        $front->registerPlugin(new Dcms_Plugin_Auth(), 7);
    }

    /**
     * Add Helper Path for helpers with prefix User_View_Helper
     */
    protected function _initViewHelpers() {

        $bootstrap = $this->getApplication();
        $bootstrap->bootstrap('View');
        $view = $bootstrap->getResource('View');
        $view->addHelperPath(dirname(__FILE__) . '/views/helpers', 'dcms_View_Helper');
    }

    /**
     * Add resource type acl with prefix User_Model_Acl
     */
    protected function _initResourceTypes() {
        $loader = $this->getResourceLoader();
        $loader->addResourceType('acl', 'models/acl', 'Model_Acl');
    }

}
