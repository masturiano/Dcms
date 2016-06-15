<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Dcms_Service_Otucouponworker extends USAP_Service_ServiceAbstract {

    protected $_config;
    protected $_otuConfig;
    protected $_worker;
    protected $_jobsqueue;
    protected $_action;
    protected $_workerName;
    protected $_lockid;
    protected $_numerator = 0;
    protected $_denominator = 0;
    protected $_ssh2Methods;
    protected $_ssh2AuthPublic;
    protected $_ssh2AuthPrivate;
    protected $_destinationConfig;
    protected $_zipName;
    protected $_sourceZipFile;
    protected $_db4Filename;
    protected $_db4File;
    protected $_csvFilename;
    protected $_csvFile;
    protected $_sourceDirectory;
    protected $_csvDirectory;
    protected $_targetServers;
    protected $_bashFile;
    protected $_db4Directory;
    protected $_from;
    protected $_recipients;
    /**
     * 
     * @param Zend_Config $config
     * @param Dcms_Service_Base $base
     * @param GearmanClient $client
     * @param GearmanWorker $worker
     * @param Zend_Application_Resource_ResourceAbstract $mongoMultiDbResource
     */
    public function __construct(Zend_Config $config) {
        $this->_config = $config;
        $this->_otuConfig = $config->otu;
        //initialize email credentials
        $this->_from = $config->email->from;
        $this->_recipients = $config->email->to;
    }

    public function setJobsQueueService($jobsQueueService) {
        $this->_jobsqueue = $jobsQueueService;
    }

    /**
     * 
     * @throws Exception
     */
    public function initializeWorker($worker, $workerName) {
        try {
            $gearmanConfig = $this->_otuConfig->gearman;


            $this->_workerName = $workerName;

            //register worker using jobs queue
            $workerFunctions = array("dcms");

            $this->jobsQueue()->registerWorker($this->_workerName, $workerFunctions);
            echo "{$this->_workerName} is waiting for job ...\n";
            $workerCredentials = array(
                'gearmanServers' => $gearmanConfig->servers,
                'alias' => $gearmanConfig->alias . "_" . APPLICATION_ENV,
                'functionname' => $gearmanConfig->functionname,
                'class' => $this
            );

            $worker->startWorker($workerCredentials);
        } catch (Exception $e) {
            echo "error: " . $e->getMessage();
        }
    }

    /**
     * 
     * @return type
     */
    public function jobsQueue() {
        return $this->_jobsqueue;
    }

    /**
     * 
     * @param type $configIdentifier
     * @return type
     */
    public function config($configIdentifier) {
        return $this->_config->$configIdentifier;
    }

    private function _setAction($action) {
        $this->_action = $action;
    }

    private function _initializeConfig($batchName) {
        //get configuration
        $otuConfig = $this->_otuConfig;
        $sourceConfig = $otuConfig->source;
        //SSH2 config
        $ssh2Config = $otuConfig->ssh2;
        $this->_ssh2Methods = $ssh2Config->methods->toArray();
        $ssh2Auth = $ssh2Config->auth;
        $this->_ssh2AuthPublic = $ssh2Auth->public;
        $this->_ssh2AuthPrivate = $ssh2Auth->private;
        //Target server/s config
        $this->_destinationConfig = $otuConfig->destination->toArray();
        $this->_targetServers = explode(",", $this->_otuConfig->targetservers);
        $this->_sourceDirectory = $sourceConfig->directory;
        $this->_zipName = $batchName . '.zip';
        $this->_sourceZipFile = $this->_sourceDirectory . $this->_zipName;
        $this->_csvDirectory = $sourceConfig->csvdir . $this->_zipName;
        $this->_db4Directory = $sourceConfig->db4;

        //path and file name
        $this->_db4Filename = $batchName . '_new.db4';
        $this->_db4File = $this->_db4Directory . $this->_db4Filename;
        $this->_csvFilename = $batchName . '.csv';
        $this->_csvFile = $this->_sourceDirectory . $this->_csvFilename;

    }

    /**
     *      Array
      (
      [batch_name] => BATCHNAM
      [domain_name] => autopartswarehouse.com
      [channel_code] =>
      [start_date] => 1358294400
      [end_date] => 1359504000
      [discount_type] => percent
      [discount_amt] => Array
      (
      [0] => 1
      )

      [discount_amt_qual] => Array
      (
      [0] => 1
      )

      [discount_ceiling_amt] => 1
      [discount_apply] => ORDERTOTAL
      [class] => 0
      [creator] => gconstantino
      [owner] => 50f60ba8b872e711b842f6eb
      [quantity] => 1
      [publish] => 1
      [appeasement] =>
      [action] => create
      [restrictions] => 1
      )

     * @param GearmanJob $job
     * @throws Exception
     */
    public function otucouponworker($job) {
        try {
            $jobWorkload = array();
            $sdate = date("r");
            echo "start {$sdate}\n";
            
            if (is_object($job)) {
                $jobWorkload = unserialize($job->workload());
            } else {
                $jobWorkload = $job;
            }
           
            if (!isset($jobWorkload['jobid'])) {
                throw new Exception("Job Id parameter is not passed.");
            }
            $jobid = $jobWorkload['jobid'];
            if ($this->_workerName == "") {
                throw new Exception("Worker name is not defined.");
            }
            $this->_bashFile = $this->_otuConfig->checkdisc;
            //Validate source server
            //validate if bash file exists
            if (!file_exists($this->_bashFile)) {
                throw new Exception("Bash File [{$this->_bashFile}] not found");
            }
            
            
            $bashCmd = "bash {$this->_bashFile}";
            exec($bashCmd, $output);
            if ($output[0] == true) {
                throw new Exception("No more disc space.");
            }


            //check if workload contains data/payload
            if (isset($jobWorkload['data'])) {
                //get job details from workload
                $jobDetails = $jobWorkload['data'];
            }
            //validate workload with required params
            $jobDetails = $this->_validateParams($jobDetails);
            if (!$jobDetails) {
                throw new Exception("Invalid parameters passed to worker");
            }

            //check if job has been created
            $retryLimit = $this->_otuConfig->retrylimit;
            $retryCtr = 0;
            $getDetails = $this->jobsQueue()->getDetails($jobid);
            while($getDetails == null){
                echo "Retrying..({$retryCtr})\n";
                $getDetails = $this->jobsQueue()->getDetails($jobid);
                if($retryCtr == $retryLimit){
                    throw new Exception("Job not found with job id: " . $jobid);
                }
                $retryCtr++;
                sleep(20);
            }
            
            if(!empty($getDetails)){
                $jobsqueueJobDetails = unserialize($getDetails);
                $jobDetails = $jobsqueueJobDetails['data'];
            }

            $this->_lockid = $this->jobsQueue()->lock($jobid, $this->_workerName);; 
            echo "lock id: {$this->_lockid} \n";
            $this->_setAction($jobDetails['action']);
             
            $batchName = $jobDetails['batch_id'];
            $this->_initializeConfig($batchName);


            //create DB4 and CSV
            $csvFileZip = $this->_createDb4PlusCsv($jobDetails, $this->_db4File, $this->_sourceDirectory, $batchName);

            //create zip file for csv
            if (!$this->_createZip($csvFileZip, $this->_csvDirectory, false)) {
                throw new Exception("The zip file was not created: " . $this->_sourceDirectory . $this->_zipName);
            }


            $db4FileOrig = str_replace("_new", "", $this->_db4Filename);
            if ($this->_action == "update" && file_exists($this->_db4File)) {
                $prevFile = str_replace("_new", "_prev", $this->_db4Filename);
                exec("mv {$this->_db4Directory}{$db4FileOrig} {$this->_db4Directory}{$prevFile}", $output);
                echo "mv {$this->_db4Directory}{$db4FileOrig} {$this->_db4Directory}{$prevFile} \n";
            }

            exec("cp {$this->_db4Directory}{$this->_db4Filename} {$this->_db4Directory}{$db4FileOrig}", $output);
            exec("chmod 777 {$this->_db4Directory}{$db4FileOrig}", $output);

            $this->jobsQueue()->statusComplete($this->_lockid, $jobid);
            
            
            $edate = date("r");
            echo "end {$edate}\n";

            //send email
            $this->mail("Worker status: Successful [BatchName: {$jobDetails['batch_name']}]", "Started: {$sdate}<br>Ended: {$edate}<br>Job id: {$jobWorkload['jobid']}<br>Job Details: <br><br>{$getDetails}");
            
            
        } catch (Exception $e) {
            $date = date("r");
            
            echo "Error: {$e->getMessage()}\n";
            if (isset($jobWorkload['jobid']) && $this->_lockid != "") {
                $this->sendStatus($this->_numerator, $this->_denominator, $e->getMessage());
                $this->jobsQueue()->statusComplete($this->_lockid, $jobWorkload['jobid']);
                $this->mail("Error with Coupon worker", "error: {$e->getMessage()}<br>date: {$date}<br>job id: {$jobWorkload['jobid']}");
            }else{
                $this->mail("Error with Coupon worker", "error: {$e->getMessage()}<br>date: {$date}");
            }

            if (is_object($job)) {
                $job->sendFail();
                exit;
            } else {
                exit;
            }
        }
    }

    /**
     * 
     * @param type $jobDetails
     * @return boolean
     */
    private function _validateParams(Array $jobDetails) {
        $requireParams = array(
            'seed',
            'quantity',
            'batch_id',
            'action'
        );
        foreach ($requireParams as $param) {
            if (!array_key_exists($param, $jobDetails)) {
                echo $param;
                return false;
            } else if ($param == "quantity") {
                $jobDetails['quantity'] = (int) $jobDetails['quantity'];
            }
        }
        if ($jobDetails['mode'] == "w" && !isset($jobDetails['previous_quantity'])) {
            return false;
        }
        return $jobDetails;
    }

    /**
     * Generation of DB4 and CSV file
     * 
     * @param type $jobDetails
     * @param type $db4File
     * @param type $csvFile
     * @throws Exception
     */
    private function _createDb4PlusCsv($jobDetails, $db4File, $csvDirectory, $csvFilename) {
	    $seed = $jobDetails['seed'];
        $quantity = $jobDetails['quantity'];
        $collisionCount = 0;
        $csvMaxLine = $this->_otuConfig->csv->maxline;
        $csvFiles = array();
        $collisionMultiplier = 5;
        $collisionMax = $jobDetails['quantity'] * $collisionMultiplier;
        echo "start db4 generation " . date("r") . "\n";
        mt_srand($seed);
        $prevQuantity = 0;
        $countCsvFiles = 0;
        $lastCount = 0;
        if ($this->_action == "update") {
            $prevQuantity = (int) $jobDetails['previous_quantity'];
            $db4Count = 0;
            if (file_exists($db4File)) {
                $readDb4 = new Dcms_Model_OtuLookupDb4($db4File, "r");
                $readDb4->open();
                for ($key = $readDb4->firstKey(); $key !== false; $key = $readDb4->nextKey()) {
                    $this->_randomString(7);
                    $db4Count++;
                    if ($db4Count % $csvMaxLine == 0) {
                        $csvFile = $csvDirectory . $csvFilename . "_" . $countCsvFiles . ".csv";
                        $csvFiles[] = $csvFile;
                        $countCsvFiles++;
                    }
                }
                unset($readDb4);

                if ($db4Count % $csvMaxLine != 0) {
                    $n = $db4Count / $csvMaxLine;
                    echo "n: " . $n . "\n";
                    list($whole, $decimal) = explode('.', $n);
                    $whole = $whole * $csvMaxLine;
                    echo "whole: " . $whole . "\n";
                    $lastCount = $db4Count - $whole;
                    echo "prev last count : " . $lastCount . "\n";

                    $csvFile = $csvDirectory . $csvFilename . "_" . ($countCsvFiles) . ".csv";
                    $countCsvFiles++;
                } else {
                    $csvFile = $csvDirectory . $csvFilename . "_" . ($countCsvFiles) . ".csv";
                }
            } else {
                $csvFile = $csvDirectory . $csvFilename . "_0.csv";
                $countCsvFiles++;
            }

            $initial = (int) $jobDetails['initial'];
            $quantity = ($initial) - $db4Count;
            if ($quantity < 0) {
                $quantity = $db4Count - ($initial);
            }


            $collisionMax = ($jobDetails['quantity'] + $db4Count) * $collisionMultiplier;


            echo $csvFile . "\n";
        } else {
            $csvFile = $csvDirectory . $csvFilename . "_0.csv";
            $countCsvFiles++;
        }
        $openCsv = fopen($csvFile, 'a');
        $csvFiles[] = $csvFile;
        $db4 = new Dcms_Model_OtuLookupDb4($db4File, "c");
        $currentTime = time();
        $time = strtotime("+40 seconds");


        $this->sendStatus($i, $quantity, "Generating coupon codes and CSV");
        $i = 0;
        $computeForNewLine = ((floor($quantity / $csvMaxLine)) * $csvMaxLine);
        echo "computeForNewLine {$computeForNewLine}\n";
        while ($i < $quantity) {
            $currentTime = time();
            $cpnCode = $this->_randomString(7);
            if (false === $db4->lookup($cpnCode)) {
                $rawCpn = $jobDetails['batch_id'] . $cpnCode;
                $db4->save($cpnCode, array());
                $row = array();
                $row[] = $rawCpn; 
                # Name: Del # Date Modify: April 2016
                fputcsv($openCsv, $row);
                $i++;
                $lastCount++;
                if ($lastCount % $csvMaxLine == 0 && $i < $quantity) {
                    fclose($openCsv);
                    $csvFile = $csvDirectory . $csvFilename . "_" . ($countCsvFiles) . ".csv";
                    $csvFiles[] = $csvFile;
                    $countCsvFiles++;
                    echo "\n i -> {$i} lastCount -> {$lastCount} csv file: " . $csvFile . "\n";
                    $openCsv = fopen($csvFile, 'a');
                }
                if ($currentTime > $time) {
                    $this->sendStatus($i, $quantity, "Generating coupon codes and CSV");
                    $time = strtotime("+40 seconds");
                }
                if($jobDetails['auto_dispense'] == 1){
                    $autoDispenseCoupon = $this->createAutoDispenseCoupon($jobDetails['batch_id'],$cpnCode);    
                }
            } else {
                $collisionCount++;
                if ($collisionCount >= 1 && $collisionCount <= 5) {
                    echo "collision for {$cpnCode} \n";
                }
                if ($collisionCount > $collisionMax) {
                    unset($db4);
                    throw new Exception("Too many collisions. Try extending the charSet table or the coupon code length");
                }
            }
        }
        fclose($openCsv);
        unset($db4);
        $db4FileExist = file_exists($db4File);
        $csvFileExist = file_exists($csvFile);
        $this->sendStatus($i, $quantity, "Processing.");
        $this->_numerator = $i;
        $this->_denominator = $quantity;
        echo "end db4 generation " . date("r") . "\n";
        if (!$db4FileExist || !$csvFileExist) {
            throw new Exception("The following files were not created: " . (!$db4FileExist) ? "<{$db4File}>" : "" . (!$csvFileExist) ? "<{$csvFile}>" : "");
        }
//        print_r($csvFiles);
        return $csvFiles;
    }

    private function sendStatus($numerator, $denominator, $message) {
        $status = array(
            'numerator' => $numerator,
            'denominator' => $denominator,
            'message' => $message
        );
        $this->jobsQueue()->statusUpdate($this->_lockid, $status);
    }

    /**
     * 
     * @param type $files
     * @param type $destination
     * @param type $overwrite
     * @return boolean
     * @throws Exception
     */
    private function _createZip($files = array(), $destination = '', $overwrite = true) {
        $validFiles = array();
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
//                    $validFiles[] = basename($file);
                    $validFiles[] = $file;
                }
            }
        }

        if (count($validFiles)) {
            //create the archive
            $zip = new ZipArchive();

            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                throw new Exception("failed to make zip");
            }

            //add the files
            foreach ($validFiles as $file) {
                echo "adding file " . $file . "\n";
                if (!$zip->addFile($file, basename($file))) {
                    throw new Exception("{$zip->getStatusString()} {$file} \n");
                }
            }
            //debug
//            echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
            //close the zip -- done!
            $zip->close();
            //check to make sure the file exists
            return file_exists($destination);
        } else {
            return false;
        }
    }

    private function _randomString($len, $repeatOk = false) {
        static $charSet = 'ABCDEFGHIJKLMNPQRSTUVWXYZ23456789';
        static $charSetLen;

        $charSetLen = strlen($charSet);
        $str = '';
        $rand = "";
        $x = 0;
        while ($x < $len) {
            $randValue = mt_rand(0, $charSetLen - 1);
            if ($repeatOk === true) {
                $str .= $charSet{$randValue};
                $rand .= $randValue;
                $x++;
            } else {
                $char = $charSet{$randValue};
                if (false === strpos($str, $char)) {
                    $str .= $char;
                    $rand .= $randValue;
                    $x++;
                }
            }
        }
        return $str;
    }

    public function test($details) {
        $otuConfig = $this->_otuConfig->toArray();
//        print_r($otuConfig);
    }
    
    
    public function mail($subject, $body) {
        try {
            $mail = new Zend_Mail();
            
            $mail->addHeader('MIME-Version', '1.0');
            $mail->addHeader('Content-Transfer-Encoding', '8bit');
            $mail->addHeader('X-Mailer:', 'PHP/' . phpversion());

            $mail->setType(Zend_Mime::MULTIPART_RELATED);

            $mail->setBodyHtml($body);

            $eadd = trim($this->_recipients);

            $mail->setFrom($this->_from);
            $mail->addTo("{$eadd}");
            
            $date = date("M-d-Y h:i:s");
            $mail->setSubject("{$subject} [{$date}]");

            $sent = true;

            try {
                $mail->send();
            } catch (Exception $e) {
                $sent = false;
            }

            //Do stuff (display error message, log it, redirect user, etc)
            if (!$sent) {
                return false;
            } 
            
            return true;
            
        } catch (Exception $e) {
            echo "An error occurred while sending email: {$e->getMessage()}";
        }
    }

   public function createAutoDispenseCoupon($batch_id, $coupon_code){
	    $details = array(
        	'batch_id' => $batch_id,
            'coupon_code' => $batch_id . $coupon_code,
            'type' => 'coupon_dispenser',
            'env' => 'live',
            'query' => array()
        );

        $config = $this->_config->service->sourceobject->toArray();
        
        try {
            $serviceObject = $config['coupon'];
            $serviceObject['method'] = "createAutoDispense";
            $getResults = Hydra_Helper::loadClass(
                $serviceObject['url'],
                $serviceObject['version'],
                $serviceObject['service'],
                $serviceObject['method'],
                $details,
                $serviceObject['httpmethod'],
                $serviceObject['id'],
                $serviceObject['format']
            );
            if (is_string($getResults) || !isset($getResults['_payload']['result'][$serviceObject['method']])) {
                return array();
            }
            return $getResults['_payload']['result'][$serviceObject['method']];
        } catch (Exception $e) {
            return array();
        }

   }

}

?>
