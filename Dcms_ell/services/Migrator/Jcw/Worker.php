<?php

/**
 * Dcms_Service_Migrator_Mysql
 * 
 * @package Dcms_Service_Migrator_Usap
 * @subpackage Worker
 * @author Faustino M. Olpindo, Jr.
 * @copyright 2012
 * @version $id$
 */
class Dcms_Service_Migrator_Jcw_Worker extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config
     */
    protected $config;

    /**
     *
     * @var Zend_Application_Resource_ResourceAbstract
     */
    protected $mysql;

    /**
     *
     * @var Zend_Application_Resource_ResourceAbstract
     */
    protected $mongo;

    /**
     *
     * @var type 
     */
    protected $site;
    protected $specSite;

    /**
     *
     * @var type 
     */
    protected $db;

    /**
     *
     * @param Zend_Config $config
     * @param Zend_Application_Resource_Db $mysqlMultiDbResource
     * @param Zend_Application_Resource_ResourceAbstract $mongoMultiDbResource 
     */
    public function __construct(Zend_Config $config, Zend_Application_Resource_Db $mysqlMultiDbResource, Zend_Application_Resource_ResourceAbstract $mongoMultiDbResource) {
        $this->config = $config;
        $this->mysql = $mysqlMultiDbResource;
        $this->mongo = $mongoMultiDbResource;
    }

    /**
     *
     * @param type $key
     * @param type $type
     * @return type
     * @throws Exception 
     */
    public function getDb($key, $type) {
        if ($type == 'mysql') {
            return $this->mysql->getDb($key);
        } elseif ($type == 'mongo') {
            return $this->mongo->getAdapter($key)->getDatabase();
        } else {
            throw new Exception("The type of database is now specified.");
        }
    }

    /**
     * @return type 
     */
    public function migrate($site) {
        try {
            $this->specSite = $site;
            echo "\nMigration of JCW Coupons started: " . date('r') . "\n \n \n";

            $coupons = $this->getCoupons();
            $counter = 0;
            $mongodb = $this->getDb('standard', 'mongo');
            $config = $this->config;

            $couponWorkingMongoCollection = $mongodb->selectCollection($config->standard->coupons->working);
            
            $couponWorkingMongoCollection->setSlaveOkay($config->setSlaveOk);
            $couponLiveMongoCollection = $mongodb->selectCollection($config->standard->coupons->live);
            $couponLiveMongoCollection->setSlaveOkay($config->setSlaveOk);
            $ceiling = 0;
            $countMultiRestriction = 0;
            $multiTieredCtr = 0;
            foreach ($coupons as $coupon) {

                if (empty($coupon['code']) || empty($coupon['name'])) {
                    echo "Missing coupon code/name \n \n \n";
                    continue;
                }
                $couponCode = strtoupper($coupon['code']);
                $couponName = strtoupper($coupon['name']);


                if ($coupon['apply'] != "SHIPPING" && $coupon['is_shipping_restrction_coupon']) {
                    echo "Invalid SHIPPING coupon: {$couponCode} \n \n \n";
                    continue;
                }

                $basicDocument = array();
                $couponDetails = array();
                $couponDetails['code'] = $couponCode;
                $couponDetails['name'] = $couponName;


                $amountQualifier = (count($coupon['qual_amt']) > 0) ? $coupon['qual_amt'] : array('0');
                $couponDetails['amountQualifier'] = $amountQualifier;

                $couponAmount = $coupon['amount'];
                $couponDetails['discountVal'] = $couponAmount;
                $couponDetails['appeasement'] = false;
                $couponDetails['ceilingAmount'] = (int) $coupon['ceil_amt'][0]; //which index? for now index 0 as DCMS2.0 caters for only one ceiling.
                //if no ceiling what value???
                //if more than one ceiling
                if (count($coupon['ceil_amt']) > 1) {
                    $ceiling++;
                    echo "{$ceiling}). celing: {$couponCode} \n \n \n";
                }
                $couponDetails['class'] = '1';
                $couponDetails['couponAppliesTo'] = $coupon['apply'];
                $couponDetails['type'] = strtolower($coupon['discount_type']);
                if (isset($coupon['promo_type']) && $coupon['promo_type'] == "promobanner") {
                    if (isset($coupon['site_promo_disp_start_date']) && isset($coupon['site_promo_disp_end_date'])) {
                        $basicDocument['site_promo_disp_start_date'] = new MongoDate($coupon['site_promo_disp_start_date']);
                        $basicDocument['site_promo_disp_end_date'] = new MongoDate($coupon['site_promo_disp_end_date']);
                    } else {
                        echo "Invalid PROMO BANNER data model. Coupon Code: {$couponCode} \n \n \n";
                        continue;
                    }
                }
                $owner = (isset($coupon['promo_type'])) ? $coupon['promo_type'] : "none";
                $ownerDetail = $this->getOwnerInfo($owner);
                $couponDetails['owner'] = $ownerDetail['id'];


                $jcwSiteIds = $coupon['site_id'];
                //validate site id if channel code or site id
                $domainList = array();
                $channelCodeList = array();
                $siteIdList = array();
                foreach ($jcwSiteIds as $jcwSiteId) {
                    $isSiteId = $this->getSiteInformation(array('siteId' => $jcwSiteId));
                    $isChannelCode = $this->getSiteInformation(array('channelCode' => $jcwSiteId));
                    if ($isSiteId && !in_array($isSiteId['siteId'], $siteIdList)) {
                        $domainList[] = $isSiteId['name'];
                        $siteIdList[] = $isSiteId['siteId'];
                        $channelCodeList = array_merge($channelCodeList, $isSiteId['channelCode']);
                    } else if ($isChannelCode && !in_array($isChannelCode['siteId'], $siteIdList)) {
                        $domainList[] = $isChannelCode['name'];
                        $siteIdList[] = $isChannelCode['siteId'];
                        $channelCodeList = array_merge($channelCodeList, $isChannelCode['channelCode']);
                    }
                }

                $couponDetails['domains'] = $domainList;
                $couponDetails['channelCode'] = $channelCodeList;
                $couponDetails['siteId'] = $siteIdList;

                //EXPIRATION Details
                $expirationDetails = array();
                if (!$coupon['start_date'] || !$coupon['end_date']) {
                    $expirationDetails['type'] = "nonexpiring";
                    $expirationDetails['status'] = "unused";
                } else {
                    $expirationDetails['type'] = "expiring";
                    $expirationDetails['status'] = "unused";
                    $expirationDetails['timezone'] = "EDT";
                    $expirationDetails['startDate'] = new MongoDate($coupon['start_date']);
                    $expirationDetails['endDate'] = new MongoDate($coupon['end_date']);
                }
                //OTHER Important Details
                $basicDocument['coupon'] = $couponDetails;
                $basicDocument['expiration'] = $expirationDetails;
//            $basicDocument['promo_type'] = $owner;
                $basicDocument['creator'] = "Migrator";
                $basicDocument['createdvia'] = "jcw_migration";
                $basicDocument['createdAt'] = new MongoDate($coupon['date_added']);
                $basicDocument['updatedAt'] = new MongoDate($coupon['last_modified']);
                $basicDocument['modified'] = false;
                $basicDocument['modifiedby'] = "";
                $basicDocument['status'] = "published";


                //Restriction
                $templateName = "JCW_{$coupon['apply']}";
                $countMultiTieredQualifiers = 0;
                $newDiscount = array();
                $newQualifier = array();
                $qualifiers = array();
                $discounts = array();
                if ($coupon['apply'] != "SHIPPING") {
                    $percent = "";
                    if (strtoupper($coupon['discount_type']) == 'PERCENT') {
                        if (count($coupon['qual_amt']) > 1 && count($couponAmount) > 1) {
                            $uniqueDiscount = array_unique($couponAmount);
                            if (count($uniqueDiscount) > 1) {
                                list($discounts, $qualifiers) = $this->regroupDiscountQualifier($couponAmount, $coupon['qual_amt'], $coupon['discount_type']);
                                $countMultiRestriction++;
                                $countMultiTieredQualifiers++;
                            } else {
                                $percent = floor($couponAmount[0]);
                            }
                        } else {
                            $percent = floor($couponAmount[0]);
                        }

                        $percent = $this->_getPercent($percent);

                        $missingTemplate = false;
                        if ($percent > 5 && $percent < 10) {
                            $per = 5;
                            $missingTemplate = true;
                        }
                        if ($percent > 10 && $percent < 15) {
                            $per = 10;
                            $missingTemplate = true;
                        }
                        if ($percent > 15 && $percent < 20) {
                            $per = 15;
                            $missingTemplate = true;
                        }
                        if ($percent > 20) {
                            $per = 20;
                            $missingTemplate = true;
                        }

                        if ($missingTemplate) {
                            echo "Missing template {$couponCode}  {$per} {$percent} \n \n \n";
                        }
                    } else if (strtoupper($coupon['discount_type']) == 'DOLLAR') {
                        if (count($coupon['qual_amt']) > 1 && count($couponAmount) > 1) {
                            $calculatedDiscounts = array();
                            foreach ($couponAmount as $k => $discount) {
                                $disc = $this->calCulatePercent($discount, $coupon['qual_amt'][$k]);
                                $calculatedDiscounts["{$disc}"] = $disc;
                            }
                            $calculatedDiscounts = array_unique($calculatedDiscounts);
                            if (count($calculatedDiscounts) > 1) {
                                list($discounts, $qualifiers) = $this->regroupDiscountQualifier($couponAmount, $coupon['qual_amt'], $coupon['discount_type']);
                                $countMultiRestriction++;
                                $countMultiTieredQualifiers++;
                            } else {
                                $percent = $this->calCulatePercent($couponAmount[0], $coupon['qual_amt'][0]);
                            }
                        } else {
                            $percent = $this->calCulatePercent($couponAmount[0], $coupon['qual_amt'][0]);
                        }
                    }

                    if ($percent != "") {
                        $templateName .= "_{$percent}";
                    }
                    
                    $templateName = $this->_restrictionName($percent);
                }
                if ($countMultiTieredQualifiers > 0) {
                    $prependCounter = 0;
                    echo "Original Coupon Code: {$couponCode} \n";
                    foreach ($discounts as $percentIdx => $discountArray) {
                        $prependCounter++;
                        $multiTieredCtr++;
                        $couponCodeMulti = "{$couponCode}{$prependCounter}";
                        $basicDocument['coupon']['code'] = $couponCodeMulti;
                        $basicDocument['coupon']['name'] = $couponCodeMulti;
                        $basicDocument['coupon']['amountQualifier'] = $qualifiers[$percentIdx];
                        $basicDocument['coupon']['discountVal'] = $discountArray;
//                        $tempName = "{$templateName}_{$percentIdx}";
                        $tempName = $this->_restrictionName($percentIdx);
                        echo "Code: {$basicDocument['coupon']['code']}\n";
                        $startDate = date("m/d/Y", $coupon['start_date']);
                        $endDate = date("m/d/Y", $coupon['end_date']);
                        echo "Date: {$startDate} - {$endDate} \n";
                        echo "Type: {$couponDetails['type']} \n";

                        foreach ($discountArray as $key => $value) {
                            $dValue = "";
                            if ($couponDetails['type'] == "percent") {
                                $dValue = $value . "%";
                            } else {
                                $dValue = "$" . $value;
                            }
                            echo "{$dValue} off orders over $" . $qualifiers[$percentIdx][$key];
                            echo "\n \n \n";
                        }

                        echo "Restriction template: {$tempName} \n";
                        echo "\n \n \n";
                        $this->saveCoupon($basicDocument, $couponCodeMulti, $tempName, $couponWorkingMongoCollection, $couponLiveMongoCollection);
                        $counter++;
                    }
                } else {
                    $this->saveCoupon($basicDocument, $couponCode, $templateName, $couponWorkingMongoCollection, $couponLiveMongoCollection);
                    $counter++;
                }
            }
            echo "mutli-tiered: {$countMultiRestriction} \n \n \n";
            echo "Migration ended: " . date('r') . "\nCount: $counter\n";
        } catch (Exception $e) {
            echo "An error occurred: " . $e->getMessage() . "\n \n \n";
        }
    }

    public function saveCoupon($basicDocument, $couponCode, $templateName, $workingCollection, $liveCollection) {
        $findRestriction = $this->getRestriction(array('templateName' => $templateName));
        $restrictions = "";
        $templateId = "";
        if ($findRestriction) {
            $restrictions = $findRestriction['templateName'];
            $templateId = (int) $findRestriction['templateId'];
        } else {
            echo "Template error: Template {$templateName} does not exist \n Coupon code: {$couponCode} \n \n \n";
        }
        $basicDocument['restrictions'] = $restrictions;
        $basicDocument['templateId'] = $templateId;

        $couponW = $workingCollection->update(
                array('coupon.code' => $couponCode, 'coupon.domains' => "jcwhitney.com"), $basicDocument, array('upsert' => true, 'w' => 1)
        );
        if (!empty($couponW['err'])) {
            echo "Error on working copy upsert. Coupon code: {$couponCode} \n \n \n";
            print_r($couponW);
        }
        $couponL = $liveCollection->update(
                array('coupon.code' => $couponCode, 'coupon.domains' => "jcwhitney.com"), $basicDocument, array('upsert' => true, 'w' => 1)
        );
        if (!empty($couponL['err'])) {
            echo "Error on live copy upsert. Coupon code: {$couponCode}\n \n \n";
            print_r($couponL);
        }
    }

    /**
     * $coupon['amount']
     * $coupon['qual_amt']
     * 
     * 
     */
    public function regroupDiscountQualifier($discountAmount, $qualifierAmount, $discountType) {
        $newSetQualifier = array();
        $newSetDiscount = array();
        $newQualifier = array();
        $newDiscount = array();
        foreach ($discountAmount as $key => $discount) {
            if ($discountType == "percent") {
                $idx = "{$discount}";
            } else {
                $calCulatePercent = $this->calCulatePercent($discount, $qualifierAmount[$key]);
                $idx = "{$calCulatePercent}";
            }

            if (array_key_exists($idx, $newSetQualifier)) {
                $getQual = $newSetQualifier[$idx];
                $getAmt = $newSetDiscount[$idx];
                if (!in_array($qualifierAmount[$key], $newQualifier)) {
                    $newQualifier = array_merge($getQual, array($qualifierAmount[$key]));
                    $newDiscount = array_merge($getAmt, array($discount));
                }
            } else {
                $newQualifier = array($qualifierAmount[$key]);
                $newDiscount = array($discount);
            }
            $newSetQualifier[$idx] = $newQualifier;
            $newSetDiscount[$idx] = $newDiscount;
        }
        return array($newSetDiscount, $newSetQualifier);
    }
    
    private function toInt($value){
        return (int) $value; 
    }

    /**
     *
     * @param type $discountId
     * @return string
     * @throws Exception 
     */
    public function getRestriction($restriction = array()) {
        $restrictionDetails = array();
        if ($restriction) {
            $configStandard = $this->config->standard;
            $standardMongoDb = $this->getDb('standard', 'mongo');
            $templateWorkingMongoCollection = $standardMongoDb->selectCollection($configStandard->restrictions->working);
            if (isset($restriction['templateId'])) {
                $restrictionDetails = $templateWorkingMongoCollection->findOne(array('templateId' => (int) $restriction['templateId']));
            } else if (isset($restriction['templateName'])) {
                $restrictionDetails = $templateWorkingMongoCollection->findOne(array('templateName' => $restriction['templateName']));
            }
            return $restrictionDetails;
        }
        return $restrictionDetails;
    }

    public function insertionSort($input, $length) {
        for ($i = 0; $i < $length; $i++) {
            $j = $i;
            while ($j > 0 && $input[$j - 1] > $input[$j]) {
                $tmp = $input[$j];
                $input[$j] = $input[$j - 1];
                $input[$j - 1] = $tmp;
                $j--;
            }
        }

        return $input;
    }

    public function getOwnerInfo($ownerName) {
        $db = $this->getDb('standard', 'mongo');
        $ownerCollection = $db->selectCollection($this->config->standard->owners->live);
        $ownerCollection->setSlaveOkay($this->config->jcw->setSlaveOk);
        $query = array(
            '$or' => array(
                array('owner' => $ownerName),
                array('value' => $ownerName),
            )
        );
        $owner = $ownerCollection->findOne($query);
        if ($owner) {
            $owner['id'] = $owner['_id']->__toString();
            return $owner;
        } else {
            $ownerUpdate = $ownerCollection->update(
                    array('owner' => $ownerName), array(
                'owner' => $ownerName,
                'value' => $ownerName,
                'createdAt' => new MongoDate(),
                'updatedAt' => new MongoDate(),
                'createdvia' => "jcw_migration",
                'creator' => 'Migrator'
                    ), array('upsert' => true, 'safe' => true)
            );

            $ownerDetails = $ownerCollection->findOne(array('owner' => $ownerName));
            $ownerDetails['id'] = $ownerUpdate['upserted'];
            return $ownerDetails;
        }
    }

    /**
     *
     * @param type $domain
     * @return type 
     */
    public function getSiteInformation($domain = array()) {
        $db = $this->getDb('standard', 'mongo');
        $mongoCollection = $db->selectCollection('dcms_domains');
        if (isset($domain['siteId'])) {
            $cursor = $mongoCollection->findOne(array('siteId' => $domain['siteId']));
        } else if (isset($domain['name'])) {
            $cursor = $mongoCollection->findOne(array('name' => $domain['name']));
        } else if (isset($domain['channelCode'])) {
            $cursor = $mongoCollection->findOne(array('channelCode' => $domain['channelCode']));
        }


        return $cursor;
    }

    /**
     *
     * @param String $code
     * @return Array
     */
    public function getMultipleValues($code) {
        
    }

    /**
     *
     * @return Array
     * 
     * {
      "_id": ObjectId("5147d0778a1ee8546f000031"),
      "coupon": {
      "amountQualifier": {
      "0": "100",
      "1": "200",
      "2": "300"
      },
      "appeasement": false,
      "ceilingAmount": "",
      "channelCode": {
      "0": "29",
      "1": "703",
      "2": "17",
      "3": "32",
      "4": "719",
      "5": "300"
      },
      "class": "1",
      "code": "SAVINGS1234",
      "couponAppliesTo": "SUBTOTAL",
      "creator": "Migrator",
      "discountVal": {
      "0": "10",
      "1": "20",
      "2": "30"
      },
      "domains": {
      "0": "carpartswholesale.com",
      "1": "carparts.com"
      },
      "name": "SAVINGS1234",
      "owner": "5136bdc6efc081cf573f3520",
      "siteId": {
      "0": NumberLong(24),
      "1": NumberLong(22)
      },
      "type": "dollar",
      "versionId": NumberLong(1362542041)
      },
      "createdAt": ISODate("2013-02-25T21:33:52.0Z"),
      "expiration": {
      "endDate": ISODate("2013-03-15T10:00:00.0Z"),
      "startDate": ISODate("2013-02-25T18:00:00.0Z"),
      "status": "used",
      "timezone": "EDT",
      "type": "expiring"
      },
      "modified": false,
      "modifiedby": "",
      "restrictions": "USAP - Restriction List 2",
      "status": "published",
      "templateId": NumberLong(4),
      "updatedAt": ISODate("2013-03-06T03:54:01.0Z")
      }

     */
    public function getCoupons() {
        $jcwMongoDb = $this->getDb('jcw', 'mongo');
        $config = $this->config->jcw;

        $couponsCollection = $jcwMongoDb->selectCollection($config->coupons);
        $now = time();
        $coupons = $couponsCollection->find(
                array('$or' =>
                    array(
                        array("end_date" => array('$gte' => $now)),
                        array("end_date" => false)
                    )
                )
        );

        return $coupons;
    }

    /**
     *
     * @param integer $discountId
     * @return Array
     */
    public function recurringInfo($discountId) {
        $db = $this->getDb('jcw', 'mysql');
        $sql = "SELECT * FROM discount_recurring WHERE discount_id = $discountId";
        $statement = $db->query($sql);
        return $statement->fetchAll();
    }

    /** 	
     * calCulatePercent
     * By RRL
     * 
     * @param $coupon, $brandList, $items
     * @return coupon's data
     */
    public function calCulatePercent($discount, $qualifier) {
        $percent = number_format($discount / $qualifier, 2);
        if ($percent >= 0.01 && $percent < 0.1)
            $per = 5;
        if ($percent >= 0.10 && $percent < 0.15)
            $per = 10;
        if ($percent >= 0.15 && $percent < 0.20)
            $per = 15;
        if ($percent >= 0.20)
            $per = 20;
        return $per;
    }

    private function _getPercent($percent) {
        if ($percent >= 5 && $percent < 10)
            $per = 5;
        if ($percent >= 10 && $percent < 15)
            $per = 10;
        if ($percent >= 15 && $percent < 20)
            $per = 15;
        if ($percent >= 20)
            $per = 20;
        return $per;
    }

    public function migrateRestrictions() {
        echo "\nMigration of JCW Restrictions started: " . date('r') . "\n";
        $jcwMongoDb = $this->getDb('jcw', 'mongo');
        $standardMongoDb = $this->getDb('standard', 'mongo');
        $configStandard = $this->config->standard;
        $configJcw = $this->config->jcw;
        $restrictionCollection = $jcwMongoDb->selectCollection($configJcw->coupon->restrictions);
        $templateLiveMongoCollection = $standardMongoDb->selectCollection($configStandard->restrictions->live);
        $templateLiveMongoCollection->setSlaveOkay($configJcw->setSlaveOk);
        $templateWorkingMongoCollection = $standardMongoDb->selectCollection($configStandard->restrictions->working);
        $templateWorkingMongoCollection->setSlaveOkay($configJcw->setSlaveOk);
        $templateKeyCounterCollection = $standardMongoDb->selectCollection($configStandard->templatecounter->live);
        $templateKeyCounterCollection->setSlaveOkay($configJcw->setSlaveOk);

        $restrictions = $restrictionCollection->find();

        $counter = 0;
        foreach ($restrictions as $restriction) {
            $templateName = "";
            if (isset($restriction['percent'])) {
                $templateName = $this->_restrictionName($restriction['percent']);
            }else{
                $templateName = "JCW_{$restriction['apply_type']}";
            }
            $restrictionTemplate = array();
            $restrictionTemplate['templateName'] = $templateName;
            $restrictionTemplate['publish'] = true;
            //assumed all coupons of jcw are exclusions
            $restrictionTemplate['type'] = "exclude";
            $restrictionTemplate['createdAt'] = new MongoDate(time());
            $restrictionTemplate['updatedAt'] = new MongoDate(time());
            $restrictionTemplate['creator'] = "migration";
            $restrictionTemplate['modified'] = false;
            $brandList = $restriction['brand_list'];
            $brands = array();
            if (strpos($brandList, "|") !== false) {
                $brands = explode("|", $brandList);
            } else {
                $brands = array($brandList);
            }

            $restrictionTemplate['brands'] = $this->insertionSort($brands, count($brands));

            $template = $templateWorkingMongoCollection->findOne(array('templateName' => $templateName));

            if (!$template) {
                $templateIdDocument = $templateKeyCounterCollection->findOne(array('key' => 'unique_key'));
                $templateId = (int) $templateIdDocument['key_counter'] + 1;
                $restrictionTemplate['templateId'] = $templateId;
                $templateKeyCounterCollection->update(
                        array('key' => 'unique_key'), array('$set' => array('key_counter' => $templateId))
                );
                echo "Creating new template: {$templateName} \n\n\n";
            }else{
                if(!isset($template['templateId']) || (int) $template['templateId'] == 0){
                    $templateIdDocument = $templateKeyCounterCollection->findOne(array('key' => 'unique_key'));
                    $templateId = (int) $templateIdDocument['key_counter'] + 1;
                    $restrictionTemplate['templateId'] = $templateId;
                    
                    $templateKeyCounterCollection->update(
                            array('key' => 'unique_key'), array('$set' => array('key_counter' => $templateId))
                    );
                }else{
                    $templateId = (int) $template['templateId'];
                    $restrictionTemplate['templateId'] = $templateId;
                }
                echo "templateId: {$templateId} \n\n\n";
                echo "Updating template: {$templateName} \n\n\n";
            }
            $templateLiveMongoCollection->update(
                    array(
                'templateName' => $templateName
                    ), $restrictionTemplate, array('upsert' => true)
            );
            $templateWorkingMongoCollection->update(
                    array(
                'templateName' => $templateName
                    ), $restrictionTemplate, array('upsert' => true)
            );

            $counter++;
        }

        echo "Migration ended: " . date('r') . "\nCount: $counter";
    }
    
    private function _restrictionName($percent){
        
        $listNumber = "";
        if($percent == '5')
            $listNumber = '1';
        if($percent == '10')
            $listNumber = '2';
        if($percent == '15')
            $listNumber = '3';
        if($percent == '20')
            $listNumber = '4';
        
        $restrictionName = "JCW - Restriction List {$listNumber}";
        return $restrictionName;
        
    }

    private function _getEnabledDomains($domain = array()) {
        $jcwMongoDb = $this->getDb('jcw', 'mongo');
        $config = $this->config->jcw;

        $couponEnabledDomainsCollection = $jcwMongoDb->selectCollection($config->coupon->enabled->domains);
        if (isset($domain['siteId'])) {
            $domain = $couponEnabledDomainsCollection->findOne(array('siteId' => $domain['siteId']));
        } else {
            $domain = $couponEnabledDomainsCollection->findOne(array('name' => $domain['name']));
        }
        return $domain;
    }

}
