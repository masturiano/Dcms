<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Dcms_Service_OtubatchList extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $config;

    /**
     *
     * @var USAP_Resource_Mongomultidb 
     */
    protected $mongoResource;

    /**
     *
     * @var array 
     */
    protected $collection;

    /**
     *
     * @var type 
     */
    protected $collectionObject;
    protected $hydraParamKeys;
    protected $requestParamKeys;
    protected $requiredRequestParamKeys;
    protected $hydraServiceInfo;
    protected $_otuConfig;

    /**
     *
     * @param Zend_Config $config
     * @param USAP_Resource_Mongomultidb $mongoResource
     */
    public function __construct(Zend_Config $config) {
        $this->config = $config;
        $this->_otuConfig = $config->otu;
        $this->hydraServiceInfo = $config->service->sourceobject->toArray();
        $this->requestParamKeys = array('countperpage', 'page', 'query', 'siteselected');
        $this->requiredRequestParamKeys = array('query');
    }

    /**
     * 
     */
    public function getPaginator($request) {
        $output = array();
        $hydraService = $this->hydraServiceInfo['coupon'];
        $hydraService['method'] = "read";
        $hydraService['data'] = array(
                    'query' => array(),
                    'type' => "batches",
                    'env' => "live"
                );
         isset($request['query']) ? $hydraService['data']['query'] = $request['query'] : "";
		$hydraService['data']['sort'] = (isset($request['sortby'])) ? array($request['sortby'] => $request['sort']) : array('coupon.name' => "1"); 
        $paginatorAdapter = new USAP_Paginator_Adapter_HydraApi($hydraService);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($request['page']);
        $paginator->setDefaultItemCountPerPage($request['countperpage']);
        $output['result'] = $paginator;
        
        return $output;
    }
    


    /**
     * 
     */
    public function validateParameters($arraParams, $requiredKeys = array(), $empty = array()) {
        $output = array();
        foreach ($requiredKeys as $key) {
            if ((count($empty) > 0 && in_array($key, $empty)) ? isset($arraParams[$key]) : empty($arraParams[$key])) {
                $output['message'] = "$key parameter is required";
                $output['error'] = Zend_Log::ERR;
                return $output;
                break;
            }
        }
    }
    

}
?>