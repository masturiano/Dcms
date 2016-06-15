<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Dcms_Service_Client extends USAP_Service_ServiceAbstract {

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

    /**
     *
     * @param Zend_Config $config
     * @param USAP_Resource_Mongomultidb $mongoResource
     */
    public function __construct(Zend_Config $config) {
        $this->config = $config;
        $this->hydraParamKeys = array('url', 'service', 'version', 'method', 'httpmethod', 'format', 'data');
        $this->requestParamKeys = array('countperpage', 'page', 'query', 'siteselected');
        $this->requiredRequestParamKeys = array('query', 'siteselected');
    }



    /**
     * 
     */
    public function getPaginator($request, $hydraServiceInfo) {
        $output = array();

        //validate $request params 

        $output = $this->validateParameters($request, $this->requestParamKeys, $this->requiredRequestParamKeys);

        /**
         * set query to be sent to hydra service
         */
        if (!empty($request['query'])) {
            $data = array(
                "pfp.channel_id" => "1",
                "part_name" => $request['query']
            );
        } else {
            $data = array("pfp.channel_id" => "1");
        }


        $hydraServiceInfo['data'] = $data;


        $output = $this->validateParameters($hydraServiceInfo, $this->hydraParamKeys);


        /**
         * Initialize DCMS paginator
         */
        $paginatorAdapter = new USAP_Paginator_Adapter_DcmsApi($hydraServiceInfo);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($request['page']);
        $paginator->setDefaultItemCountPerPage($request['countperpage']);
        $output['result'] = $paginator;
        return $output;
    }

    /**
     * $hydraServiceInfo = array(
     * 'url' => "http://catalog.hydra.staging.usautoparts.com/",
      'service' => "Spintax",
      'version' => "v1d0",
      'method' => array(
      "get" => "getTemplates",
      "count" => "countTemplates"
      ),
      "httpmethod" => "get",
      "format" => "json",
      "id" => null
      );
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

