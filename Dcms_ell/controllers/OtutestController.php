<?php
/**
 * @category USAPTool_Modules
 * @package Dcms_OtutestController
 * @author bteves
 * @copyright 2013
 * @version $Id$
 */
class Dcms_OtutestController extends USAP_Controller_Action {
    
    protected $_options;
    protected $_mongoConnDcms;
    protected $_mongoConnDummy;
    
    public function init() {
        parent::init();
    }

    public function indexAction() {
        try {
            $front = Zend_Controller_Front::getInstance();
            $bootstrap = $front->getParam('bootstrap');
            $bootstraps = $bootstrap->getResource('cli');
            $bootstraps->addOptionRules(
                    array(
                        'domain|d-s'   => "Domain name.",
                        'batch_id|b-s' => "Batch Id."
                    )
            );
            $this->_options = $bootstraps->getGetOpt();
            if($this->_options->domain == "" || $this->_options->batch_id == "") {
                echo "\n Invalid Parameter \n\n";
                exit();
            }
            
            $this->_mongoDcms();
            $this->_mongoDummy();
            
            $mongoDcmsBatchCol = $this->_mongoConnDcms->getCollection();
            $mongoDcmsBatchCol->setSlaveOkay(false);
            
            $mongoDummyBatchCol = $this->_mongoConnDummy->getCollection();
            $mongoDummyBatchCol->setSlaveOkay(false);
            $batchId = "";
            $batchId = strtoupper(trim($this->_options->batch_id));
            
            $dcmsBatchResult = $mongoDcmsBatchCol->findOne(array("batchId" => $batchId), array("_id" => 0));
            if(!$dcmsBatchResult) {
                echo "\n Batch id '{$batchId}' not found. \n\n";
                exit();
            } else {
                $mongoDummyBatchCheck = $mongoDummyBatchCol->findOne(array("batchId" => $batchId), array("_id" => 0));
                if($mongoDummyBatchCheck) {
                    echo "\n Batch id '{$batchId}' already found on dummy collection. \n\n";
                    exit();
                }
                $mongoDummyBatchIns = $mongoDummyBatchCol->update(array("batchId" => $batchId), $dcmsBatchResult, array('upsert' => true, 'safe' => true));
                
                if(!$mongoDummyBatchIns) {
                    echo "\n An error occured while inserting Batch id '{$batchId}' on dcms_dummy:dcms_one_time_use_coupons_batches. \n\n";
                    exit();
                }
                if(isset($dcmsBatchResult['coupon']['domains'])) {
                    $domain = $dcmsBatchResult['coupon']['domains'][0];
                    
                    $mongoDcmsDomainCol = $this->_mongoConnDcms->getCollection('dcms_domains');
                    $mongoDcmsDomainCol->setSlaveOkay(false);
                    $dcmsDomainResult = $mongoDcmsDomainCol->findOne(array("name" => $domain), array("_id" => 0));
                    
                    $mongoDummyDomainCol = $this->_mongoConnDummy->getCollection('dcms_domains');
                    $mongoDummyDomainCol->setSlaveOkay(false);

                    $mongoDummyDomainIns = $mongoDummyDomainCol->update(array("name" => $domain), $dcmsDomainResult, array('upsert' => true, 'safe' => true));
                    if(!$mongoDummyDomainIns) {
                        $mongoDummyBatchCol->remove(array("batchId" => $batchId), array('safe' => true));
                        echo "\n An error occured while inserting domain name '{$domain}' for '{$batchId}' on dcms_dummy:dcms_domains. \n\n";
                        exit();
                    }
                    
                    $mongoDcmsMsBrandCol = $this->_mongoConnDcms->getCollection('master_excluded_brands');
                    $mongoDcmsMsBrandCol->setSlaveOkay(false);
                    $dcmsMsBrandsResult = $mongoDcmsMsBrandCol->findOne(array("siteId" => $dcmsDomainResult['siteId']), array("_id" => 0));
                    if($dcmsMsBrandsResult) {
                        $mongoDummyMsBrandsCol = $this->_mongoConnDummy->getCollection('master_excluded_brands');
                        $mongoDummyMsBrandsCol->setSlaveOkay(false);
                        
                        $mongoDummyMsBrandsIns = $mongoDummyMsBrandsCol->update(array("siteId" => $dcmsDomainResult['siteId']), $dcmsMsBrandsResult, array('upsert' => true, 'safe' => true));
                        if(!$mongoDummyMsBrandsIns) {
                            $mongoDummyBatchCol->remove(array("batchId" => $batchId), array('safe' => true));
                            $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                            echo "\n An error occured while inserting master brands '{$domain}' for '{$batchId}' on dcms_dummy:master_excluded_brands. \n\n";
                            exit();
                        }
                    }
                }
                
                if(isset($dcmsBatchResult['coupon']['owner']) && trim($dcmsBatchResult['coupon']['owner']) != "") {
                    $owner = $dcmsBatchResult['coupon']['owner'];
                    
                    $mongoDcmsOwnerCol = $this->_mongoConnDcms->getCollection('dcms_coupon_owners');
                    $mongoDcmsOwnerCol->setSlaveOkay(false);
                    $dcmsOwnerResult = $mongoDcmsOwnerCol->findOne(array("_id" => new MongoId($owner)));
                    (string)$dcmsOwnerResult['_id'];
                    $mongoDummyOwnerCol = $this->_mongoConnDummy->getCollection('dcms_coupon_owners');
                    $mongoDummyOwnerCol->setSlaveOkay(false);
                    
                    $mongoDummyOwnerIns = $mongoDummyOwnerCol->update(array("_id" => new MongoId($owner)), $dcmsOwnerResult, array('upsert' => true, 'safe' => true));

                    if(!$mongoDummyOwnerIns) {
                        $mongoDummyBatchCol->remove(array("batchId" => $batchId), array('safe' => true));
                        $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                        echo "\n An error occured while inserting owner '{$owner}' for '{$batchId}' on dcms_dummy:dcms_coupon_owners. \n\n";
                        exit();
                    }   
                }
                
                if(isset($dcmsBatchResult['templateId']) && (int)$dcmsBatchResult['templateId'] != 0 ) {
                    $templateId = (int)$dcmsBatchResult['templateId'];
                    
                    $mongoDcmsResctricCol = $this->_mongoConnDcms->getCollection('dcms_restrictions');
                    $mongoDcmsResctricCol->setSlaveOkay(false);
                    $dcmsRestricResult = $mongoDcmsResctricCol->findOne(array("templateId" => $templateId));

                    $mongoDummyRestricCol = $this->_mongoConnDummy->getCollection('dcms_restrictions');
                    $mongoDummyRestricCol->setSlaveOkay(false);
                    
                    $mongoDummyRestricWorkCol = $this->_mongoConnDummy->getCollection('dcms_restrictions_working');
                    $mongoDummyRestricWorkCol->setSlaveOkay(false);
                    
                    $mongoDummyRestricIns = $mongoDummyRestricCol->update(array("templateId" => $templateId), $dcmsRestricResult, array('upsert' => true, 'safe' => true));
                    $mongoDummyRestricWorkIns = $mongoDummyRestricWorkCol->update(array("templateId" => $templateId), $dcmsRestricResult, array('upsert' => true, 'safe' => true));
                    
                    if($mongoDummyRestricIns && $mongoDummyRestricWorkIns) {  } else {
                        $mongoDummyBatchCol->remove(array("batchId" => $batchId), array('safe' => true));
                        $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                        $mongoDummyOwnerCol->remove(array("_id" => new MongoId($owner)), array('safe' => true));
                        $mongoDummyRestricCol->remove(array("templateId" => $templateId), array('safe' => true));
                        $mongoDummyRestricWorkCol->remove(array("templateId" => $templateId), array('safe' => true));
                        echo "\n An error occured while inserting templateId '{$templateId}' for '{$batchId}' on dcms_dummy:dcms_restrictions & dcms_dummy:dcms_restrictions_working. \n\n";
                        exit();
                    }
                }
                                
                $db = $this->_getDb4($batchId);
                if(!$db) {
                    $mongoDummyBatchCol->remove(array("batchId" => $batchId), array('safe' => true));
                    $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                    $mongoDummyOwnerCol->remove(array("_id" => new MongoId($owner)), array('safe' => true));
                    $mongoDummyRestricCol->remove(array("templateId" => $templateId), array('safe' => true));
                    $mongoDummyRestricWorkCol->remove(array("templateId" => $templateId), array('safe' => true));
                    exit();
                }
                
                $handle = @dba_open($db, "r", "db4");
                if(!$handle) {
                    $mongoDummyBatchCol->remove(array("batchId" => $batchId), array('safe' => true));
                    $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                    $mongoDummyOwnerCol->remove(array("_id" => new MongoId($owner)), array('safe' => true));
                    $mongoDummyRestricCol->remove(array("templateId" => $templateId), array('safe' => true));
                    $mongoDummyRestricWorkCol->remove(array("templateId" => $templateId), array('safe' => true));
                    echo "\n Unable to open Db4 file: {$db}. \n\n";
                    exit();
                }
                $mongoDummyCpnCol = $this->_mongoConnDummy->getCollection('dcms_coupons');
                $mongoDummyCpnCol->setSlaveOkay(false);
                
                $mongoDummyUsageCol = $this->_mongoConnDummy->getCollection('coupon_usage');
                $mongoDummyUsageCol->setSlaveOkay(false);
                
                $key = dba_firstkey($handle);
                $ctr = 0;
                while($key != null) {
                    $ctr++;
                    $couponCode = dba_fetch($key, $handle);
                    $couponCode = unserialize($couponCode);
                    $minifiedData = array();
                    $minifiedData['batchId'] = $dcmsBatchResult['batchId'];
                    $minifiedData['coupon']['code'] = $batchId . $couponCode['__coupon'];
                    $minifiedData['coupon']['type'] = $dcmsBatchResult['coupon']['type'];
                    $minifiedData['coupon']['appeasement'] = $dcmsBatchResult['coupon']['appeasement'];
                    $minifiedData['coupon']['couponAppliesTo'] = $dcmsBatchResult['coupon']['couponAppliesTo'];
                    $minifiedData['coupon']['domains'] = $dcmsBatchResult['coupon']['domains'];
                    $minifiedData['coupon']['siteId'] = $dcmsBatchResult['coupon']['siteId'];
                    $minifiedData['expiration']['type'] = $dcmsBatchResult['expiration']['type'];
                    $minifiedData['expiration']['startDate'] = $dcmsBatchResult['expiration']['startDate'];
                    $minifiedData['expiration']['endDate'] = $dcmsBatchResult['expiration']['endDate'];
                    $minifiedData['expiration']['status'] = "used";
                    $minifiedData['dateUsed'] = new MongoDate();
                 
                    $mongoDummyCpnCol->insert($minifiedData, array('safe' => true));
                    
                    $usageDetails = array(
                        'code' => $batchId . $couponCode['__coupon'],
                        'usage' => array(
                            'date' => new MongoDate(),
                            'site_id' => $dcmsDomainResult['siteId'],
                            'order_id' => "order_id_{$ctr}",
                            'status' => time(),
                            'method' => "updateCouponStatus",
                            'transaction_id' => "trans_id_{$ctr}"
                        ),
                        'usage_count' => 1
                    );
                    $mongoDummyUsageCol->insert($usageDetails, array('safe' => true));
                    $key = dba_nextkey($handle);
                    echo "INSERTED order_id_{$ctr} \n";
                }
                dba_close($handle);
                
                $dummyBatch = $mongoDummyBatchCol->findOne(array("batchId" => $batchId), array("_id" => 0));
                $dummyBatch['remaining'] = 0;
                $mongoDummyBatchCol->update(array("batchId" => $batchId), $dummyBatch, array('upsert' => true, 'safe' => true));
                
                echo "\n\n SUCCESS {$ctr} \n\n";
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    public function getcpnAction(){
        try {
            $front = Zend_Controller_Front::getInstance();
            $bootstrap = $front->getParam('bootstrap');
            $bootstraps = $bootstrap->getResource('cli');
            $bootstraps->addOptionRules(
                    array(
                        'domain|d-s'   => "Domain name.",
                        'coupon|c-s'   => "Coupon Code."
                    )
            );
            $this->_options = $bootstraps->getGetOpt();
            if($this->_options->coupon == "" && $this->_options->domain == ""){
                echo "\n Invalid Parameter \n\n";
                exit();
            }
            
            $this->_mongoDcms();
            $this->_mongoDummy();
            
            $mongoDcmsCpnCol = $this->_mongoConnDcms->getCollection("dcms_coupons");
            $mongoDcmsCpnCol->setSlaveOkay(false);
            
            $mongoDummyCpnCol = $this->_mongoConnDummy->getCollection("dcms_coupons");
            $mongoDummyCpnCol->setSlaveOkay(false);
            
            $CpnCode = "";
            $CpnCode = strtoupper(trim($this->_options->coupon));
            
            $domainName = "";
            $domainName = trim($this->_options->domain);
            
            $dcmsCouponResult = $mongoDcmsCpnCol->findOne(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array("_id" => 0));
            
            if(!$dcmsCouponResult) {
                echo "\n Coupon code '{$CpnCode}' not found. \n\n";
                exit();
            } else {
                
                $mongoDummyCpnIns = $mongoDummyCpnCol->update(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), $dcmsCouponResult, array('upsert' => true, 'safe' => true));
                
                if(!$mongoDummyCpnIns) {
                    echo "\n An error occured while inserting Coupon code '{$CpnCode}' on dcms_dummy:dcms_coupons. \n\n";
                    exit();
                }
                
                $mongoDummyCpnWorkCol = $this->_mongoConnDummy->getCollection("dcms_coupons_working");
                $mongoDummyCpnWorkCol->setSlaveOkay(false);
                $mongoDummyCpnWorkingIns = $mongoDummyCpnWorkCol->update(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), $dcmsCouponResult, array('upsert' => true, 'safe' => true));
                
                if(!$mongoDummyCpnWorkingIns) {
                    $mongoDummyCpnCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                    echo "\n An error occured while inserting Coupon code '{$CpnCode}' on dcms_dummy:dcms_coupons_working. \n\n";
                    exit();
                }
                
                if(isset($dcmsCouponResult['coupon']['domains'])) {
                    $domain = $dcmsCouponResult['coupon']['domains'][0];
                    
                    $mongoDcmsDomainCol = $this->_mongoConnDcms->getCollection('dcms_domains');
                    $mongoDcmsDomainCol->setSlaveOkay(false);
                    $dcmsDomainResult = $mongoDcmsDomainCol->findOne(array("name" => $domain), array("_id" => 0));
                    
                    $mongoDummyDomainCol = $this->_mongoConnDummy->getCollection('dcms_domains');
                    $mongoDummyDomainCol->setSlaveOkay(false);

                    $mongoDummyDomainIns = $mongoDummyDomainCol->update(array("name" => $domain), $dcmsDomainResult, array('upsert' => true, 'safe' => true));
                    if(!$mongoDummyDomainIns) {
                        $mongoDummyCpnCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                        $mongoDummyCpnWorkCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                        echo "\n An error occured while inserting domain name '{$domain}' for '{$CpnCode}' on dcms_dummy:dcms_domains. \n\n";
                        exit();
                    }
                    
                    $mongoDcmsMsBrandCol = $this->_mongoConnDcms->getCollection('master_excluded_brands');
                    $mongoDcmsMsBrandCol->setSlaveOkay(false);
                    $dcmsMsBrandsResult = $mongoDcmsMsBrandCol->findOne(array("siteId" => $dcmsDomainResult['siteId']), array("_id" => 0));
                    if($dcmsMsBrandsResult) {
                        $mongoDummyMsBrandsCol = $this->_mongoConnDummy->getCollection('master_excluded_brands');
                        $mongoDummyMsBrandsCol->setSlaveOkay(false);
                        
                        $mongoDummyMsBrandsIns = $mongoDummyMsBrandsCol->update(array("siteId" => $dcmsDomainResult['siteId']), $dcmsMsBrandsResult, array('upsert' => true, 'safe' => true));
                        if(!$mongoDummyMsBrandsIns) {
                            $mongoDummyCpnCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                            $mongoDummyCpnWorkCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                            $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                            echo "\n An error occured while inserting master brands '{$domain}' for '{$CpnCode}' on dcms_dummy:master_excluded_brands. \n\n";
                            exit();
                        }
                    }
                }
                
                if(isset($dcmsCouponResult['coupon']['owner']) && trim($dcmsCouponResult['coupon']['owner']) != "") {
                    $owner = $dcmsCouponResult['coupon']['owner'];
                    
                    $mongoDcmsOwnerCol = $this->_mongoConnDcms->getCollection('dcms_coupon_owners');
                    $mongoDcmsOwnerCol->setSlaveOkay(false);
                    $dcmsOwnerResult = $mongoDcmsOwnerCol->findOne(array("_id" => new MongoId($owner)));
                    (string)$dcmsOwnerResult['_id'];
                    $mongoDummyOwnerCol = $this->_mongoConnDummy->getCollection('dcms_coupon_owners');
                    $mongoDummyOwnerCol->setSlaveOkay(false);
                    
                    $mongoDummyOwnerIns = $mongoDummyOwnerCol->update(array("_id" => new MongoId($owner)), $dcmsOwnerResult, array('upsert' => true, 'safe' => true));

                    if(!$mongoDummyOwnerIns) {
                        $mongoDummyCpnCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                        $mongoDummyCpnWorkCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                        $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                        echo "\n An error occured while inserting owner '{$owner}' for '{$CpnCode}' on dcms_dummy:dcms_coupon_owners. \n\n";
                        exit();
                    }   
                }
                
                if(isset($dcmsCouponResult['templateId']) && (int)$dcmsCouponResult['templateId'] != 0 ) {
                    $templateId = (int)$dcmsCouponResult['templateId'];
                    
                    $mongoDcmsResctricCol = $this->_mongoConnDcms->getCollection('dcms_restrictions');
                    $mongoDcmsResctricCol->setSlaveOkay(false);
                    $dcmsRestricResult = $mongoDcmsResctricCol->findOne(array("templateId" => $templateId));

                    $mongoDummyRestricCol = $this->_mongoConnDummy->getCollection('dcms_restrictions');
                    $mongoDummyRestricCol->setSlaveOkay(false);
                    
                    $mongoDummyRestricWorkCol = $this->_mongoConnDummy->getCollection('dcms_restrictions_working');
                    $mongoDummyRestricWorkCol->setSlaveOkay(false);
                    
                    $mongoDummyRestricIns = $mongoDummyRestricCol->update(array("templateId" => $templateId), $dcmsRestricResult, array('upsert' => true, 'safe' => true));
                    $mongoDummyRestricWorkIns = $mongoDummyRestricWorkCol->update(array("templateId" => $templateId), $dcmsRestricResult, array('upsert' => true, 'safe' => true));
                    
                    if($mongoDummyRestricIns && $mongoDummyRestricWorkIns) {  } else {
                        $mongoDummyCpnCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                        $mongoDummyCpnWorkCol->remove(array("coupon.code" => $CpnCode, "coupon.domains" => $domainName), array('safe' => true));
                        $mongoDummyDomainCol->remove(array("name" => $domain), array('safe' => true));
                        $mongoDummyOwnerCol->remove(array("_id" => new MongoId($owner)), array('safe' => true));
                        $mongoDummyRestricCol->remove(array("templateId" => $templateId), array('safe' => true));
                        $mongoDummyRestricWorkCol->remove(array("templateId" => $templateId), array('safe' => true));
                        echo "\n An error occured while inserting templateId '{$templateId}' for '{$CpnCode}' on dcms_dummy:dcms_restrictions & dcms_dummy:dcms_restrictions_working. \n\n";
                        exit();
                    }
                }
                echo "\n\n SUCCESS {$ctr} \n\n";
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    private function _getDb4($batchId) {
        
        if($this->_options->environment == "production" || $this->_options->environment == "staging") {
            $dbName = "/data/www/html/usaptool/scripts/{$batchId}.db4";
        } else {
            $dbName = "/var/lib/db4/{$batchId}.db4";
        }
        if(file_exists($dbName) && is_readable($dbName)) {
            return $dbName;
        } else {
            echo "\n DB4 not found: {$dbName}. \n\n";
            return false;
        }
    }

    private function _mongoDcms() {
        try {
            $mongo = array();

            if($this->_options->environment == "production") {
                $mongo = array(
                    "host"       => "192.168.100.108:27017, 192.168.100.107:27017, 192.168.100.106:27017, 192.168.100.105:27017, 192.168.100.104:27017, 192.168.100.186:27017",
                    "database"   => "dcms",
                    "replica"    => "replica11",
                    "collection" => "dcms_one_time_use_coupons_batches"
                );
            } elseif($this->_options->environment == "staging") {
                $mongo = array(
                    "host"       => "localhost:27120, localhost:27121, localhost:27122",
                    "database"   => "dcms",
                    "replica"    => "hydra",
                    "collection" => "dcms_one_time_use_coupons_batches"
                );
            } else {
                $mongo = array(
                    "host"       => "localhost:27017, 127.0.0.1:27017",
                    "database"   => "dcms",
                    "replica"    => false,
                    "collection" => "dcms_one_time_use_coupons_batches"
                );
            }

            $this->_mongoConnDcms = new USAP_Db_Mongo_Adapter($mongo);
        } catch(MongoConnectionException $e) {
            echo $e->getMessage();
        }
    }
    
    private function _mongoDummy() {
        
        try {
            $mongo = array();

            if($this->_options->environment == "production") {
                $mongo = array(
                    "host"       => "192.168.100.108:27017, 192.168.100.107:27017, 192.168.100.106:27017, 192.168.100.105:27017, 192.168.100.104:27017, 192.168.100.186:27017",
                    "database"   => "dcms_dummy",
                    "replica"    => "replica11",
                    "collection" => "dcms_one_time_use_coupons_batches"
                );
            } elseif($this->_options->environment == "staging") {
                $mongo = array(
                    "host"       => "localhost:27120, localhost:27121, localhost:27122",
                    "database"   => "dcms_dummy",
                    "replica"    => "hydra",
                    "collection" => "dcms_one_time_use_coupons_batches"
                );
            } else {
                $mongo = array(
                    "host"       => "localhost:27017, 127.0.0.1:27017",
                    "database"   => "dcms_dummy",
                    "replica"    => false,
                    "collection" => "dcms_one_time_use_coupons_batches"
                );
            }

            $this->_mongoConnDummy = new USAP_Db_Mongo_Adapter($mongo);
        } catch(MongoConnectionException $e) {
            echo $e->getMessage();
        }        
    }
    
    public function preDispatch() {
        if (php_sapi_name() != 'cli') {
            $this->_redirect("/dcms");
        }
    }
}
