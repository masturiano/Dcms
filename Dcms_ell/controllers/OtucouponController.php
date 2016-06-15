<?php

/**
 * Dcms_OtucouponController
 * 
 * @package Dcms_OtucouponController
 * @author Tools Team
 * @copyright 2012
 * @version $id$
 */
class Dcms_OtucouponController extends USAP_Controller_Action {

    /**
     *
     * @var type 
     */
    protected $bootstrap;

    /**
     *
     * @var type 
     */
    protected $service;

    /**
     *
     * @var type 
     */
    protected $baseService;

    /**
     *
     * @var type 
     */
    protected $otuCouponService;

    /**
     *
     * @var Array 
     */

    /**
     * Initializes resources and options. 
     */
    public function init() {
        parent::init();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $bootstraps = $bootstrap->getResource('modules');
        $this->bootstrap = $bootstraps['Dcms'];
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('retryworker', 'json')
                ->initContext();
    }

    /**
     * Use to run gearman worker.
     */
    public function runCouponWorkerAction() {
        $otuWorker = USAP_Service_ServiceAbstract::getService("Dcms_Service_Otucouponworker", "");
        $gearmanWorker = new Dcms_Model_Worker();
        $jobsQueueService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Jobsqueue", "");
        $otuWorker->setJobsQueueService($jobsQueueService);
        $workerName = $this->_getWorkerName();
        $otuWorker->initializeWorker($gearmanWorker, $workerName);
    }

    /**
     * Gets worker name by getting the value from -n on CLI
     * @return string worker name
     */
    private function _getWorkerName() {
        try {
            $bootstraps = $this->_getResource();
            $bootstraps->addOptionRules(
                    array(
                        'workername|n-s' => "Worker name."
                    )
            );
            $options = $bootstraps->getGetOpt();
            return $options->workername;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function _getResource($param = "cli") {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        return $bootstrap->getResource($param);
    }

    public function managerAction() {
        try {
            $jobsQueueService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Jobsqueue", "");
            $getjobs = $jobsQueueService->getAllJobs();

            foreach ($getjobs as $key => $value) {
                if ($value['status'] == "queued") {
                    $jobPayLoad = $value['jobPayload'];
                    $jobPayLoad = unserialize($jobPayLoad);
                    print_r($jobPayLoad);
                    $jobPayLoad['jobid'] = $key;
                    !isset($jobPayLoad['data']) ? $jobPayLoad['data'] = $jobPayLoad : "";

                    $jobPayLoad = serialize($jobPayLoad);

                    $client = new GearmanClient();
                    $client->addServer();
                    $jobhandle = $client->doBackground("otucoupon_" . APPLICATION_ENV, $jobPayLoad);
                    $stat = $client->jobStatus($jobhandle);
                    if ($client->returnCode() != GEARMAN_SUCCESS || !$stat[1]) {
                        echo $client->error();
                    }
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        exit;
    }

    public function retryworkerAction() {
        $otuUrl = $this->bootstrap->getOption('otu');
        $request = $this->getRequest()->getParams();
        unset($request['module']);
        unset($request['controller']);
        unset($request['action']);
        $request['env'] = APPLICATION_ENV;
        $request['action'] = $request['jobaction'];
        unset($request['jobaction']);
        $request = http_build_query($request);
        $url = "{$otuUrl['status']['url']}?{$request}";
        $this->view->request = $request;
//        echo $url;
//        exit;
        $result = $this->callCurl($url);
        $decodedResult = json_decode($result, true);

        $this->view->env = APPLICATION_ENV;
        if (isset($decodedResult['_payload']) || (isset($decodedResult['_error']['error_code']) && $decodedResult['_error']['error_code'] == "JE1")) {
            $this->_buildRedirect("Retry successfully initiated", "/dcms/otucoupon/manage");
        } else {
            $this->_buildRedirect("Failed to retry", "/dcms/otucoupon/manage", Zend_Log::ERR);
        }
        unset($this->view->user);
        unset($this->view->alltoollist);
    }

    public function manageAction() {
        try {
            $jobsQueueService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Jobsqueue", "");

            $this->view->jobs = $jobsQueueService->getAllJobs(array('status' => "queued"));

//        $this->view->jobs = $jobsQueueService->getAllJobs(array('status' => "processing"));
            if (in_array("dcms.otucontrol", $this->view->user->getRoles())) {
                $this->view->hasControl = true;
            } else {
                $this->view->hasControl = false;
            }
        } catch (Exception $e) {
            echo "error:{$e->getMessage()}";
        }
    }

    private function _buildRedirect($message, $path, $code = Zend_Log::INFO) {
        $this->addMessage($message, $code);
        $this->_redirect($path);
    }

    public function callCurl($url = null) {

        try {
            if (isset($url)) {
                $contents = null;
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_URL, $url);
                $contents = curl_exec($curl);
                curl_close($curl);
                return $contents;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
