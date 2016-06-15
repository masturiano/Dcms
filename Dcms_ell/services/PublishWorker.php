<?php

/**
 * @category USAPTool_Modules
 * @package Cpam_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Cpam_Service_PublishWorker extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Array 
     */
    protected $serviceRead;

    /**
     *
     * @var Array
     */
    protected $serviceWrite;

    /**
     *
     * @var Spinner_Model_Import
     */
    protected $importModel;

    /**
     *
     * @var GearmanWorker
     */
    protected $worker;

    /**
     *
     * @var GearmanClient
     */
    protected $client;

    /**
     *
     * @var string 
     */
    protected $collection;

    /**
     *
     * @var type 
     */
    protected $adapter;

    /**
     *
     * @param GearmanWorker $worker
     * @param Zend_Config_Ini $config
     * @param USAP_Db_Mongo_Adapter $mongodbresource
     * @param GearmanClient $client 
     */
    public function __construct(Zend_Config_Ini $config, USAP_Resource_Mongomultidb $mongoResource, GearmanClient $client, GearmanWorker $worker) {
        $this->mongoResource = $mongoResource;

        $this->attribute = "type";

        $this->client = $client;
        $this->worker = $worker;

        $this->useService = $config->use->service;
        $this->collection = $config->publish->collection->toArray();
        $this->destination = $config->publish->destination->toArray();
        $this->serviceRead = $config->service->read->toArray();
        $this->serviceWrite = $config->service->write->toArray();
        if (!$this->useService) {
            $this->mysql = $config->mysql->toArray();
            $this->SQL = new Zend_Db_Adapter_Pdo_Mysql($this->mysql);
        }
        $this->postlimit = $config->postlimit;
    }

    /**
     *
     * @param type $attr 
     */
    public function setAttribute($attr) {
        $this->attribute = $attr;
    }

    /**
     *
     * @return type 
     */
    public function destinationPath() {
        return $this->destination[$this->attribute];
    }

    /**
     *
     * @return GearmanWorker 
     */
    public function worker() {
        return $this->worker;
    }

    /**
     * Registers a function with the name and function callback
     * 
     * @param type $function_name
     * @param type $callback 
     */
    public function addWorkerFunction($function_name, $callback) {
        $this->worker->addFunction($function_name, array($this, $callback));
    }

    public function removeAttributes() {
        $preCombiMongoCollection = $this->_attributeMongoCollection($this->collection['pre_combi']);
        $finalCombiMongoCollection = $this->_attributeMongoCollection($this->collection['final_combi']);
        $typeMongoCollection = $this->_attributeMongoCollection($this->collection['type']);
        $valueMongoCollection = $this->_attributeMongoCollection($this->collection['value']);
        $this->removeCollectionContents($typeMongoCollection);
        $this->removeCollectionContents($valueMongoCollection);
//        $this->removeCollectionContents($preCombiMongoCollection);
//        $this->removeCollectionContents($finalCombiMongoCollection);
    }

    /**
     *
     * @param type $MongoCollection
     * @param type $key
     * @param type $status 
     */
    public function setStatus($MongoCollection, $key, $status) {
        $MongoCollection->update(array('key' => $key), array('$set' => $status), array('upsert' => true));
    }

    public function cpampublishworker($job) {
        try {
            try {
//            $job->sendFail();exit;
                $gearmanJob = unserialize($job->workload());

                /**
                 * Initialize mongo collections
                 *  cpam_attr_file
                 *  cpam_publish_status
                 *  cpam_report
                 */
                $fileMongoCollection = $this->_attributeMongoCollection($this->collection['file']);

                $publishStatusMongoCollection = $this->_attributeMongoCollection($this->collection['status']);
                $publishStatusMongoCollection->ensureIndex(array('key' => 1), array('unique' => true));

                $reportMongoCollection = $this->_attributeMongoCollection($this->collection['report']);


                $MongoCollections = array(
                    'status' => $publishStatusMongoCollection,
                    'report' => $reportMongoCollection
                );


                echo "starting..\n";

                /**
                 * remove previous upload/publis status
                 */
                $publishStatusMongoCollection->remove(array("key" => "cpam_new"));
                /**
                 * remove previosly created failed/exempted inserts
                 */
                $reportMongoCollection->remove();


                $fileCounter = 1;
                $totalCount = 0;
                $totalInsert = 0;

                /**
                 * $gearmanJob contains two array:
                 *      [type] => array(
                 *          'filepath' => <upload directory> + <filename>,
                 *          'username' => <username>
                 *          'filename' => <filename>    
                 *      ),
                 *      [value] => array(
                 *          'filepath' => <upload directory> + <filename>,
                 *          'username' => <username>
                 *          'filename' => <filename>    
                 *      )
                 */
                foreach ($gearmanJob as $key => $value) {
                    $job->sendStatus($fileCounter, count($gearmanJob));

                    echo "uploading {$key} attribute \n";
                    /**
                     * Sets attribute for global access for $this->attribute
                     */
                    $this->setAttribute($key);

                    /**
                     * set status to uploading
                     */
                    $status = array(
                        'status' => "uploading",
                        'attribute' => $key,
                        'date' => new MongoDate(),
                        'uploaded_by' => $gearmanJob[$key]['username'],
                        $key . '_filename' => $gearmanJob[$key]['filename']
                    );
                    $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));

                    /**
                     * Gets file contents and store to Hydra mongo db.
                     */
                    list($totalCount, $totalInsert) = $this->uploadData($gearmanJob[$key]['filepath'], $MongoCollections);
                    $status = array(
                        $key . '_insert' => $totalInsert,
                        $key . '_count' => $totalCount,
                    );
                    $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
                    echo "uploading end\n";
                    $fileCounter++;
                }


                /**
                 * remove cpam_attr_file
                 */
                $this->removeCollectionContents($fileMongoCollection);
                $status = array(
                    'status' => "done",
                    'date' => new MongoDate()
                );
                $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
                echo "end..\n";
            } catch (Exception $e) {
                $status = array(
                    'date' => new MongoDate(),
                    'status' => "done",
                    'signal' => "error",
                    'message' => $e->getMessage()
                );
                $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
                echo "Error: " . $e->getMessage() . "\n";
                echo "end..\n";
                $job->sendFail();
            }
        } catch (MongoConnectionException $e) {
            $status = array(
                'date' => new MongoDate(),
                'status' => "done",
                'signal' => "error",
                'message' => $e->getMessage()
            );
            $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
            echo "Error: " . $e->getMessage() . "\n";
            echo "end..\n";
            $job->sendFail();
        }
    }

    public function checkHeaders($header) {
        $countError = 0;
        if ($this->attribute == "type") {
            if (count($header) == 17
                    && $header[0] == "pa_type_name"
                    && $header[1] == "pa_group_name"
                    && $header[2] == "pa_type_desc"
                    && $header[3] == "isnavigational"
                    && $header[4] == "issearchable"
                    && $header[5] == "sort_order"
                    && $header[6] == "nav_rank"
            ) {
                return true;
            } else {
                return false;
            }
        } else if ($this->attribute == "value") {
            if (count($header) == 11
                    && $header[0] == "pa_value_name"
            ) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Reads a file line by line. Each line/record separated by tab is then sent to a Write Interface. This write interface saves 
     * the record in Hydra mongodb.
     * 
     * @param String $filename
     * @param MongoCollection $MongoCollections
     * @return type 
     */
    public function uploadData($filename, $MongoCollections) {

//        try {
//            $hReader = new Csv_Reader($filename, new Csv_Dialect_Excel());
//        } catch (Csv_Exception_FileNotFound $e) {
//            Throw new Exception($e->getMessage());
//        }

        try {
            /**
             * get mongo collections from $MongoCollections array parameter
             *  cpam_publish_status
             *  cpam_report
             */
            $publishStatusMongoCollection = $MongoCollections['status'];
            $reportMongoCollection = $MongoCollections['report'];
            $hydraService = $this->serviceWrite;

            $goodcount = 0;
            $badcount = 0;
//            $totalCount = $hReader->count();
            $filecontentArray = file($filename);
            $totalCount = count($filecontentArray);
            $status = array(
                'denominator' => $totalCount
            );

            /**
             * Checks if file has contents
             */
            if ($totalCount == 0) {
                Throw new Exception("File/s should not be empty.");
            }

            $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
//            if ($hReader) {

            /**
             * Validates if file "does" contain the correct attributes depending on the file
             * sample value file header pa_value_name	synonym_att_value_1	synonym_att_value_2	synonym_att_value_3	synonym_att_value_4	synonym_att_value_5	synonym_att_value_6	synonym_att_value_7	synonym_att_value_8	synonym_att_value_9	synonym_att_value_10
             * sampe type file header pa_type_name	pa_group_name	pa_type_desc	isnavigational	issearchable	sort_order	nav_rank	synonym_att_type_1	synonym_att_type_2	synonym_att_type_3	synonym_att_type_4	synonym_att_type_5	synonym_att_type_6	synonym_att_type_7	synonym_att_type_8	synonym_att_type_9	synonym_att_type_10
             */
//                $header = $hReader->getRow(); // grabs first row
            $header = $filecontentArray[0];
            $header_map = explode("\t", $header);

            if ($this->checkHeaders($header_map) == false) {
                throw new Exception("Invalid header on a [{$this->attribute}] file. Please check again");
            }
            /**
             * Maps data from file separated by tabs and inserted to Hydra 
             * Dbname: Multiplicity
             * collection: cpam_typevalue
             */
//                $rowcounter = 1;
            $data = array();
            $arrayData = array();
//                while ($row = $hReader->getRow()) { // starts from second row
            for ($rowcounter = 1; $rowcounter <= $totalCount; $rowcounter++) {
//                    $fieldValues = explode("\t", $row[0]);
                $fieldValues = explode("\t", $filecontentArray[$rowcounter]);
                /**
                 * Maps row contents
                 */
                if(trim($fieldValues[0]) == ""){
                    echo "blank space in line number " . $rowcounter . "\n";
                }
                $data = $this->mapRowContents($header_map, $fieldValues);
                if(!$data){
                    continue;
                }
                $arrayData['cpam'][] = $data;
                $arrayData['attribute'] = $this->attribute;

                if ($rowcounter % ((int) $this->postlimit) == 0) {
                    /**
                     * write cpam attribute to Hydra server mongo database.
                     */
                    $hydraReturnResult = $this->hydraService($hydraService, $arrayData);
                    if (is_string($hydraReturnResult)) {
                        Throw new Exception("Unable to connect to hydra server.");
                    } else {
                        $exemptionReport = $hydraReturnResult['_payload']['result']['pushCpam'];
                        if (!empty($exemptionReport)) {
                            $status[$this->attribute . "_report"] = "true";
                            $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
                            $reportMongoCollection->batchInsert($exemptionReport);
                        }
                    }
                    unset($arrayData);
                }


//                    $status = array(
//                        'numerator' => $rowcounter++
//                    );
                $status = array(
                    'numerator' => $rowcounter
                );

                $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
            }
//                }
            $hydraReturnResult = $this->hydraService($hydraService, $arrayData);

            if (is_string($hydraReturnResult)) {
                Throw new Exception("Unable to connect to hydra server.");
            } else {
                $exemptionReport = $hydraReturnResult['_payload']['result']['pushCpam'];
                if (!empty($exemptionReport)) {
                    $status[$this->attribute . "_report"] = "true";
                    $publishStatusMongoCollection->update(array('key' => "cpam_new"), array('$set' => $status), array('upsert' => true));
                    $reportMongoCollection->batchInsert($exemptionReport);
                }
            }
//            }// if hReader


            unset($hReader);
            return array($totalCount, $rowcounter);
        } catch (Exception $e) {
            Throw new Exception($e->getMessage());
        }
    }

    /**
     * insert array of documents in Mongo db 
     * @param array $report
     * @param MongoCollection $MongoCollection 
     */
    public function bulkInsert($report, $MongoCollection) {
        foreach ($report as $rep) {
            $rep['attribute'] = $this->attribute;
            $MongoCollection->insert($rep);
        }
    }

    /**
     * Maps data from given array and insert to new array using the fieldnames
     * 
     * @param array $fieldNames
     * @param array $fieldValues
     * @return array 
     */
    public function mapRowContents($fieldNames, $fieldValues) {
        if (trim($fieldValues[0]) == "") {
            return false;
        } else {
            $data = array();
            $data[base64_encode(htmlentities($this->replaceQuote($fieldNames[0])))] = base64_encode(htmlentities($this->replaceQuote($fieldValues[0])));
            if ($this->attribute == "value") {
                $data["synonyms"] = $this->synonymsArray($fieldValues, 1);
            } else {
                for ($fieldCounter = 1; $fieldCounter <= 6; $fieldCounter++) {
                    $data[base64_encode(htmlentities($this->replaceQuote($fieldNames[$fieldCounter])))] = base64_encode(htmlentities($this->replaceQuote($fieldValues[$fieldCounter])));
                }
                $data["synonyms"] = $this->synonymsArray($fieldValues);
            }
            return $data;
        }
    }

    /**
     * Maps synonyms from given array
     * 
     * @param array $data
     * @param int $offset
     * @param int $limit 
     */
    public function synonymsArray($data = array(), $offset=7, $limit=10) {
        $synonyms = array();
        for ($i = 0; $i <= $limit; $i++, $offset++) {
            if (isset($data[$offset]) && trim($data[$offset]) != "") {
//                $pattern = '/[\x00-\x1F\x80-\xFF]/';
                $synonyms[][$this->attribute] = base64_encode(htmlentities($this->replaceQuote($data[$offset])));
            }
        }
        return $synonyms;
    }

    /**
     * Sends data to hydra service 
     * +
     * @param Array $serviceInfo
     * @param Array $data
     * @return service result or false if data did not pass validation 
     */
    public function hydraService($serviceInfo, $data = array()) {
        try {
            if (isset($serviceInfo['url'])
                    && isset($serviceInfo['version'])
                    && isset($serviceInfo['service'])
                    && isset($serviceInfo['method'])
                    && isset($serviceInfo['httpmethod'])
                    && isset($serviceInfo['id'])
                    && isset($serviceInfo['format'])
                    && count($data) > 0
            ) {
                return Hydra_Helper::loadClass(
                                $serviceInfo['url'], $serviceInfo['version'], $serviceInfo['service'], $serviceInfo['method'], $data, $serviceInfo['httpmethod'], $serviceInfo['id'], $serviceInfo['format']
                );
            } else {
                return false;
            }
        } catch (Exception $e) {
            Throw new Exception($e->getMessage);
        }
    }

    /**
     *
     * @param type $collection
     * @return type 
     */
    private function _attributeMongoCollection($collection) {
        $dataAdapter = $this->mongoResource->getAdapter('cpam');
        $dataCollection = $dataAdapter->getCollection($collection);
        return $dataCollection;
    }

    /**
     * Remove collection content 
     * @param MongoCollection $mongoCollection
     * @return 
     */
    private function removeCollectionContents($mongoCollection) {
        try {
            return $mongoCollection->remove();
        } catch (Exception $e) {
            Throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param type $s
     * @return string 
     */
    public function clearAscii($s) {
        setlocale(LC_ALL, 'en_US.UTF8');

        $r = '';
        $s1 = iconv('Windows-1252', 'ASCII//TRANSLIT', $s);
        for ($i = 0; $i < strlen($s1); $i++) {
            $ch1 = $s1[$i];
            $ch2 = mb_substr($s, $i, 1);

            $r .= $ch1 == '?' ? $ch2 : $ch1;
        }
        return $r;
    }

    /**
     *
     * @param <type> $text
     * @return <type>
     */
    public function replaceQuote($text) {
//        $text = $this->normalize($text);
        $text = $this->removeSmartQuotes($text);
        $text = $this->clearAscii($text);
        $text = $this->clearUTF($text);
        return $text;
    }

    /**
     *
     * @param <type> $text
     * @return <type>
     */
    public function removeSmartQuotes($text) {
        // First, replace UTF-8 characters.
        $text = str_replace(
                array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"), array("'", "'", '"', '"', '-', '--', '...'), $text);
        // Next, replace their Windows-1252 equivalents.
        $text = str_replace(
                array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)), array("'", "'", '"', '"', '-', '--', '...'), $text);
        return $text;
    }

    /**
     *
     * @param <type> $s
     * @return <type>
     */
    function clearUTF($s) {
        setlocale(LC_ALL, 'en_US.UTF8');
        $r = '';
        $s1 = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $j = 0;
        for ($i = 0; $i < strlen($s1); $i++) {
            $ch1 = $s1[$i];
            $ch2 = @mb_substr($s, $j++, 1, 'UTF-8');
            if (strstr('`^~\'"', $ch1) !== false) {
                if ($ch1 <> $ch2) {
                    --$j;
                    continue;
                }
            }
            $r .= ( $ch1 == '?') ? $ch2 : $ch1;
        }
        return $r;
    }

}

?>
