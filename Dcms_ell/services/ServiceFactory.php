<?php

/**
 * Dcms_Service_Factory
 * 
 * @package Dcms_Service
 * @subpackage Factory
 * @author folpindo
 * @copyright 2012
 * @version $id$
 */
class Dcms_Service_ServiceFactory extends USAP_Service_ServiceFactoryAbstract {

    /**
     *
     * @var Zend_Config
     */
    protected $_config;

    /**
     *
     * @var <type> 
     */
    protected $resource;

    /**
     *
     * @param Zend_Config $config 
     */
    public function __construct(Zend_Config $config) {
        $this->_config = $config;
    }

    /**
     *
     * @param <type> $service
     * @return service
     */
    public function create($service) {
        if (!class_exists($service)) {
            throw new Exception("Service not available.");
        }
        return new $service($this->_config);
    }

    /**
     *
     * @param <type> $param
     * @return <type>
     */
    public function getConfigParams($param) {
        $param = preg_replace(".", "->", $param);
        return $this->config->$param;
    }
	public function getMongomultidbResource() {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $mongoResource = $bootstraps['Dcms']->getPluginResource('mongomultidb');
        $this->resource['mongo'] = $mongoResource;
        Zend_Registry::set('mongomultidb', $mongoResource);
        return $this->resource['mongo'];
    }

    public function getMysqlMultidbResource() {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        return $bootstraps['Dcms']->getPluginResource('multidb');
    }

    public function getSttMysqlmulidbResource() {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $mysqlmultidbResource = $bootstraps['Dcms']->getPluginResource('multidb');
        $this->resource['mysql'] = $mysqlmultidbResource;
        Zend_Registry::set('multidb', $mysqlmultidbResource);
        return $this->resource['mysql'];
    }

    /**
     *
     * @return Dcms_Service_Coupon
     */
    public function getDcmsServiceCoupon() {
        return new Dcms_Service_Coupon($this->_config, $this->getDcmsServiceBase());
    }

    /**
     *
     * @return Dcms_Service_Dcms
     */
    public function getDcmsServiceMigratorMongo() {
        return new Dcms_Service_Migrator_Mongo($this->_config, $this->getMongomultidbResource());
    }

    /**
     *
     * @return Dcms_Service_Dcms
     */
    public function getDcmsServiceMigratorMysql() {
        return new Dcms_Service_Migrator_Mysql($this->_config, $this->getSttMysqlmulidbResource());
    }

    public function getDcmsServiceMigratorJcwWorker() {
        return new Dcms_Service_Migrator_Jcw_Worker($this->_config, $this->getMysqlMultidbResource(), $this->getMongomultidbResource());
    }

    public function getDcmsServiceMigratorStandardWorker() {
        return new Dcms_Service_Migrator_Standard_Worker($this->_config, $this->getMysqlMultidbResource(), $this->getMongomultidbResource());
    }

    public function getDcmsServiceMigratorSttWorker() {
        return new Dcms_Service_Migrator_Stt_Worker($this->_config, $this->getMysqlMultidbResource(), $this->getMongomultidbResource());
    }

    /**
     *
     * @return Dcms_Service_Client
     */
    public function getDcmsServiceClient() {
        return new Dcms_Service_Client($this->_config);
    }

    /**
     *
     * @return Dcms_Service_Template
     */
    public function getDcmsServiceTemplate() {
        $baseService = $this->getDcmsServiceBase();
        return new Dcms_Service_Template($this->_config, $baseService);
    }
    
   
    /**
     *
     * @return Dcms_Service_Base
     */
    public function getDcmsServiceBase() {
        return new Dcms_Service_Base($this->_config);
    }    
    
    public function getDcmsServiceDomainList() {
        return new Dcms_Service_DomainList($this->_config);
    }
    
    public function getDcmsServiceOtucouponList() {
        return new Dcms_Service_OtucouponList($this->_config);
    }
    
    public function getDcmsServiceOtubatchList() {
        return new Dcms_Service_OtubatchList($this->_config);
    }

    public function getDcmsServiceDispenseOtubatchList() {
        return new Dcms_Service_DispenseOtubatchList($this->_config);
    }

    public function getDcmsServiceBrandList() {
        return new Dcms_Service_BrandList($this->_config);
    }
    
    public function getDcmsServiceCouponsecondaryList() {
        return new Dcms_Service_CouponsecondaryList($this->_config);
    }
    
    public function getDcmsServiceOtucouponsecondaryList() {
        return new Dcms_Service_OtucouponsecondaryList($this->_config);
    }
    
    public function getDcmsServiceProductionList() {
        return new Dcms_Service_ProductionList($this->_config);
    }
    
    public function getDcmsServiceCouponownerList() {
        return new Dcms_Service_CouponownerList($this->_config);
    }
    
    public function getDcmsServiceCouponList() {
        $baseService = $this->getDcmsServiceBase();
        return new Dcms_Service_CouponList($this->_config, $baseService);
    }  
	
    public function getDcmsServiceOtucouponworker() {
        return new Dcms_Service_Otucouponworker($this->_config);
    }
	
	public function getDcmsServiceOtucoupon() {
        $baseService = $this->getDcmsServiceBase();
        return new Dcms_Service_Otucoupon($this->_config, $baseService, $this->getMongomultidbResource());
    }

	public function getDcmsServiceOtuexportworker() {
		$worker = new GearmanWorker();
        $worker->addServer();
		$client = new GearmanClient();
        $client->addServer();
        $baseService = $this->getDcmsServiceBase();
        return new Dcms_Service_Otuexportworker($this->_config, $baseService, $client, $worker, $this->getMongomultidbResource());
    }
    public function getDcmsServiceJobsqueue() {
        return new Dcms_Service_Jobsqueue($this->_config);
    }
    
    }
