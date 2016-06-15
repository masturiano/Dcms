<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id: Client.php 1386 2012-08-03 01:46:48Z gconstantino $
 */
class Dcms_Service_Jobsqueue extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $_config;
    protected $_url;
    protected $_version;
    protected $_service;
    protected $_httpmethod;
    protected $_id;
    protected $_format;

    const ADD_WORKER_FAILURE = "AWF0"; //FAILURE

    /**
     *
     * @param Zend_Config $config
     */

    public function __construct(Zend_Config $config) {
        $this->_config = $config;
        $serviceObject = $config->service->sourceobject->jobsqueue->toArray();
        $this->_url = $serviceObject['url'];
        $this->_version = $serviceObject['version'];
        $this->_service = $serviceObject['service'];
        $this->_httpmethod = $serviceObject['httpmethod'];
        $this->_id = $serviceObject['id'];
        $this->_format = $serviceObject['format'];
    }

    public function connect($data, $method) {
        try {
            $result = Hydra_Helper::loadClass(
                            $this->_url, $this->_version, $this->_service, $method, $data, $this->_httpmethod, $this->_id, $this->_format
            );
            if (is_string($result) || !isset($result['_payload']['result'][$method])) {
                throw new Exception("Unable to connect to Jobs Queue API.");
            }
            return $result['_payload']['result'][$method];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function lock($jobid, $workername) {
        $params = array(
            'job_id' => $jobid,
            'worker_name' => $workername,
        );
        $lockResult = $this->connect($params, "lock");
        $this->_returnError($lockResult['error']);
        if (!isset($lockResult['result']['lock_id']) || empty($lockResult['result']['lock_id'])) {
            return $jobid . "|" . $workername;
        }
        return $lockResult['result']['lock_id'];
    }

    public function getDetails($jobid) {
        $params = array(
            'job_id' => $jobid
        );
        $jobDetails = $this->connect($params, "getDetails");
        if (count($jobDetails['error']) > 0 && isset($jobDetails['error']['message']) && is_string($jobDetails['error']['message'])) {
            return null;
        }
        if (isset($jobDetails['result']['job_details']['jobPayload'])) {
            return $jobDetails['result']['job_details']['jobPayload'];
        }

        return null;
    }

    public function statusUpdate($lockId, $payload) {
        $params = array(
            'lock_id' => $lockId,
            'new_current' => $payload
        );
        $statusUpdate = $this->connect($params, "statusUpdate");
        $this->_returnError($statusUpdate['error']);
        return true;
    }

    /**
     * jobId|workername = lockId
     * @param type $lockId
     * @return boolean
     * @throws Exception
     */
    public function statusComplete($lockId, $jobId) {
        $params = array(
            'lock_id' => $lockId,
            'job_id' => $jobId
        );
        $statusComplete = $this->connect($params, "statusComplete");
        if (isset($statusComplete['error']['error_code']) && $statusComplete['error']['error_code'] == "FALSE") {
            //check if job has been created
            $retryLimit = $this->_config->otu->retrylimit;
            $retryCtr = 0;
            $lockId = $statusComplete['error']['error_code'];
            while ($lockId == "FALSE") {
                echo "Retrying status complete..({$retryCtr})\n";
                $statusComplete = $this->connect($params, "statusComplete");
                if (!isset($statusComplete['error']['error_code'])) {
                    break;
                }
                $lockId = $statusComplete['error']['error_code'];
                if ($retryCtr == $retryLimit) {
                    break;
                }
                $retryCtr++;
                sleep(20);
            }
        }
        $this->_returnError($statusComplete['error']);
        return true;
    }

    public function statusCancel() {
        
    }

    public function registerWorker($workerName, $workerFunctions) {
        $params = array(
            'worker_name' => $workerName,
            'worker_functions' => $workerFunctions
        );
        $register = $this->connect($params, "registerWorker");
        if (count($register['error']) > 0 && isset($register['error']['message']) && is_string($register['error']['message'])) {
            if ($register['error']['error_code'] != self::ADD_WORKER_FAILURE) {
                throw new Exception($register['error']['message']);
            }
        }

        return true;
    }

    public function getAllJobs($params = array()) {

        $jobs = $this->connect($params, __FUNCTION__);

        $this->_returnError($jobs['error']);
        return $jobs['result']['job_ids'];
    }

    private function _returnError($errorIdx) {
        if (count($errorIdx) > 0 && isset($errorIdx['message']) && is_string($errorIdx['message'])) {
            throw new Exception($errorIdx['message']);
        }
    }

}
?>

