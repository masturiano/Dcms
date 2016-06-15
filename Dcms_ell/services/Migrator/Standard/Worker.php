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
class Dcms_Service_Migrator_Standard_Worker extends USAP_Service_ServiceAbstract {

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
        $this->specSite = $site;
        echo "\nMigration started: " . date('r') . "\n";

        $done = array();
        $coupons = $this->getCoupons();
        $restrictions = $this->getRestrictions();
        $counter = 0;
        $mongodb = $this->getDb('standard', 'mongo');
        $config = $this->config;

        $couponWorkingMongoCollection = $mongodb->selectCollection($config->standard->coupons->working);
        $couponLiveMongoCollection = $mongodb->selectCollection($config->standard->coupons->live);
        $templateLiveMongoCollection = $mongodb->selectCollection($config->standard->restrictions->live);
        $templateWorkingMongoCollection = $mongodb->selectCollection($config->standard->restrictions->working);
        $templateKeyCounterCollection = $mongodb->selectCollection($config->standard->templatecounter->live);
        $ownerCollection = $mongodb->selectCollection($config->standard->owners->live);
        foreach ($coupons as $coupon) {
            if ($coupon['website'] == $this->specSite) {
                if (!in_array($coupon['discount_code'], $done)) {
                    echo "${coupon['discount_code']}\n";
                    $targetCoupon = array();
                    $basic = array();
                    $restriction = array();
                    $versionId = time();
                    $couponDetails = array();
                    $type = '';
                    $recurring = array();

                    $multipleValues = $this->getMultipleValues($coupon['discount_code']);
                    $couponDetails['code'] = strtoupper($coupon['discount_code']);
                    $couponDetails['name'] = strtoupper($coupon['discount_name']);
                    $couponDetails['type'] = $coupon['discount_type'];
                    $couponDetails['discountVal'] = $multipleValues['discountVal'];
                    $couponDetails['amountQualifier'] = $multipleValues['amountQualifier'];
                    $couponDetails['ceilingAmount'] = '';
                    $couponDetails['appeasement'] = false;

                    if ($coupon['discount_apply'] == 'order') {
                        $applyTo = 'SUBTOTAL';
                    } else {
                        $applyTo = $coupon['discount_apply'];
                    }

                    $couponDetails['couponAppliesTo'] = $applyTo;
                    $basic['creator'] = 'Migrator';
                    $basic['createdvia'] = 'migration';
                    $couponDetails['versionId'] = $versionId;
                    $couponDetails['class'] = '1';
                    $couponDetails['domains'] = $multipleValues['domains'];
                    $owner = $coupon['owner'];
                    $ownerId = '';
                    $ownerCollection->update(
                            array('owner' => $owner), array(
                        'owner' => $owner,
                        'value' => $owner,
                        'createdAt' => new MongoDate(),
                        'updatedAt' => new MongoDate(),
                        'creator' => 'Migrator'
                            ), array('upsert' => true, 'safe' => true)
                    );

                    $ownerDetails = $ownerCollection->findOne(array('owner' => $owner));

                    if (!empty($ownerDetails)) {
                        $ownerId = $ownerDetails['_id']->__toString();
                    }

                    $couponDetails['owner'] = $ownerId;
                    //                $domainInformation = $this->getSiteInformation($coupon['website']);
                    //                $couponDetails['siteId'] = $domainInformation['siteId'];
                    //                $couponDetails['channelCode'] = $domainInformation['channelCodes'];
                    $couponDetails['siteId'] = $multipleValues['siteId'];
                    $couponDetails['channelCode'] = $multipleValues['channelCode'];
                    $basic['coupon'] = $couponDetails;
                    $basic['modified'] = false;
                    $basic['modifiedby'] = '';
                    $basic['status'] = 'published';
                    $restriction = $this->getRestriction($coupon['discount_id']);
                    if ($restriction) {
                        $template = $templateLiveMongoCollection->findOne(array('templateName' => $restriction['templateName']));
                        //                    $template = $templateWorkingMongoCollection->findOne(array('templateName' => $restriction['templateName']));

                        if (!$template) {
                            $templateIdDocument = $templateKeyCounterCollection->findOne(array('key' => 'unique_key'));
                            $templateId = (int) $templateIdDocument['key_counter'] + 1;
                            $restriction['templateId'] = $templateId;
                            $templateKeyCounterCollection->update(
                                    array('key' => 'unique_key'), array('$set' => array('key_counter' => $templateId))
                            );

                            $templateLiveMongoCollection->update(
                                    array(
                                'templateName' => $restriction['templateName']
                                    ), $restriction, array('upsert' => true)
                            );
                            $templateWorkingMongoCollection->update(
                                    array(
                                'templateName' => $restriction['templateName']
                                    ), $restriction, array('upsert' => true)
                            );
                        } else {
                            $templateId = $template['templateId'];
                        }
                    }

                    $basic['templateId'] = $templateId;

                    if (is_null($restriction['templateName'])) {
                        $basic['templateId'] = "";
                        $basic['restrictions'] = "";
                    }

                    $basic['restrictions'] = $restriction['templateName'];
                    $basic['createdAt'] = new MongoDate(strtotime($coupon['date_created']));
                    $basic['updatedAt'] = new MongoDate($versionId);
                    $type = 'expiring';
                    $recurringInfo = array();
                    $recurringInfo = $this->recurringInfo($coupon['discount_id']);
                    if (!empty($recurringInfo)) {
                        $type = 'recurring';
                        $recurring = array(
                            'startDayOfMonth' => $recurringInfo[0]['startrecurring'],
                            'startDayOfMonth_converted' => $recurringInfo[0]['startrecurring'],
                            'endDayOfMonth' => $recurringInfo[0]['endrecurring'],
                            'endDayOfMonth_converted' => $recurringInfo[0]['endrecurring'],
                            'numberOfMonths' => $recurringInfo[0]['total_month'],
                            'monthYearStart' => $recurringInfo[0]['startmonth']
                            . ", " . $recurringInfo[0]['startyear']
                        );
                    }
                    if (empty($multipleValues['startDate'])) {
                        $type = 'nonexpiring';
                    }

                    $basic['expiration'] = array_merge($recurring, array(
                        'type' => $type,
                        'startDate' => new MongoDate($multipleValues['startDate']),
                        'endDate' => new MongoDate($multipleValues['endDate']),
                        'timezone' => 'EDT',
                        'status' => 'unused'
                            ));

                    $couponWorkingMongoCollection->update(
                            array('coupon.code' => $coupon['discount_code'], 'coupon.domains' => $this->specSite), $basic, array('upsert' => true)
                    );
                    $couponLiveMongoCollection->update(
                            array('coupon.code' => $coupon['discount_code'], 'coupon.domains' => $this->specSite), $basic, array('upsert' => true)
                    );

                    $done[] = $coupon['discount_code'];
                    $counter++;
                }
            }
        }
        echo "Migration ended: " . date('r') . "\nCount: $counter";
    }

    /**
     *
     * @param type $discountId
     * @return string
     * @throws Exception 
     */
    public function getRestriction($discountId) {
        $db = $this->getDb('standard', 'mysql');

        $sql = "SELECT
        tl.template_name as templateName,
        tl.date_created as modified,
        tl.excluded_parts as part_excluded,
        tl.excluded_brands as brand_excluded
        FROM template_discount as td
        INNER JOIN template_list as tl on tl.template_id=td.template_id
        WHERE td.discount_id = '" . $discountId . "'";

        $statement = $db->query($sql);
        $result = $statement->fetchAll();

        if ($result) {
            $restriction = array();
            $parts = array();
            $brands = array();
            $restriction['modified'] = false;
            $restriction['creator'] = 'migration';
            $restriction['createdAt'] = new MongoDate(strtotime($result[0]['modified']));
            $restriction['updatedAt'] = new MongoDate(strtotime($result[0]['modified']));
            $counter = 0;
            foreach ($result as $template) {
                if (!isset($restriction['templateName'])) {
                    $restriction['templateName'] = $template['templateName'];
                }

                $sqlBrand = "SELECT tb.brand_name as brand
                FROM template_discount as td
                INNER JOIN template_brand as tb on tb.template_id=td.template_id
                WHERE td.discount_id = '" . $discountId . "'";
                $brandStatement = $db->query($sqlBrand);
                $brandResult = $brandStatement->fetchAll();

                if ($brandResult) {
                    foreach ($brandResult as $brand) {
                        if (!in_array($brand['brand'], $brands)) {
                            $brands[] = $brand['brand'];
                        }
                    }
                }

                $sqlPart .= "SELECT tp.part_name as part
                FROM template_discount as td
                INNER JOIN template_part as tp on tp.template_id=td.template_id
                WHERE td.discount_id = '" . $discountId . "'";
                $partStatement = $db->query($sqlPart);
                $partResult = $partStatement->fetchAll();

                if ($partResult) {
                    foreach ($partResult as $part) {
                        if (!in_array($part['part'], $parts)) {
                            $parts[] = $part['part'];
                        }
                    }
                }
            }

            $bc = count($brands) - 1;

            $restriction['brands'] = $this->insertionSort($brands, count($brands));
            $restriction['parts'] = $this->insertionSort($parts, count($parts));

            if ($template['part_excluded'] == '0' && $template['brand_excluded'] == '0') {
                $restriction['type'] = 'include';
            } elseif ($template['part_excluded'] == '1' && $template['brand_excluded'] == '1') {
                $restriction['type'] = 'exclude';
            }

            if (($template['part_excluded'] == '0' && $template['brand_excluded'] == '1') || ($template['part_excluded'] == '1' && $template['brand_excluded'] == '0')) {
                $mongodb = $this->getDb('standard', 'mongo');
                $config = $this->config;
                $migratorLogsCollection = $mongodb->selectCollection($config->standard->migrator->logs);

                $log = array(
                    'discountid' => $discountId,
                    'templateName' => $restriction['templateName'],
                    'Error' => "Template is a combination of exclude and include"
                );

                $migratorLogsCollection->update(
                        array(
                    'templateName' => $restriction['templateName']
                        ), $log, array('upsert' => true)
                );
                return false;
            }

            return $restriction;
        }
        return false;
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

    /**
     *
     * @param type $domain
     * @return type 
     */
    public function getSiteInformation($domain) {
        $db = $this->getDb('standard', 'mongo');
        $mongoCollection = $db->selectCollection('dcms_domains');
        $cursor = $mongoCollection->find(array('name' => $domain));
        $domainInformation = array();
        $siteId = "";
        $siteChannelCodes = array();
        do {
            $cursor->next();
            $coupon = $cursor->current();
            $siteChannelCodes = array_merge($siteChannelCodes, $coupon['channelCode']);
            $siteId = $coupon['siteId'];
        } while ($cursor->hasnext());
        $domainInformation['siteId'] = $siteId;
        $domainInformation['channelCodes'] = $siteChannelCodes;
        return $domainInformation;
    }

    /**
     *
     * @param String $code
     * @return Array
     */
    public function getMultipleValues($code) {
        $db = $this->getDb('standard', 'mysql');
        $sql = "SELECT *
                FROM discount_master as dm
                INNER JOIN discount_websites as dw on dw.discount_id = dm.discount_id
                INNER JOIN discount_date_created as dtc on dtc.discount_id = dm.discount_id
                 WHERE dm.discount_code='" . $code . "'";
// dm.discount_end > UNIX_TIMESTAMP() AND
        $statement = $db->query($sql);
        $result = $statement->fetchAll();
        $multipleValues = array();
        $discountQualifier = array();
        $discountValues = array();
        $domains = array();
        $discountStartContainer = array();
        $discountEndContainer = array();
        $siteIdContainer = array();
        $channelCodeContainer = array();
        foreach ($result as $coupon) {
            if ($coupon['website'] == $this->specSite) {
                if (!in_array($coupon['discount_amount'], $discountValues)) {
                    array_push($discountValues, $coupon['discount_amount']);
                    array_push($discountQualifier, $coupon['dollar_amt_qual']);
                }

                if (!in_array($coupon['website'], $domains)) {
                    $domains[] = $coupon['website'];
                    $domainInfo = $this->getSiteInformation($coupon['website']);
                    $siteIdContainer[] = $domainInfo['siteId'];
                    $channelCodeContainer = array_merge($channelCodeContainer, $domainInfo['channelCodes']);
                }
                $discountStartContainer[] = $coupon['discount_start'];
                $discountEndContainer[] = $coupon['discount_end'];
            }
        }

        $channelCodeContainerUnique = array_unique($channelCodeContainer);
        $multipleValues['discountVal'] = $discountValues;
        $multipleValues['amountQualifier'] = $discountQualifier;
        $multipleValues['domains'] = $domains;
        $multipleValues['siteId'] = $siteIdContainer;
        $multipleValues['channelCode'] = $channelCodeContainerUnique;
        $multipleValues['startDate'] = min($discountStartContainer);
        $multipleValues['endDate'] = max($discountEndContainer);
        return $multipleValues;
    }

    /**
     *
     * @return Array
     */
    public function getCoupons() {
        $db = $this->getDb('standard', 'mysql');
        $sql = "SELECT *
                FROM discount_master as dm
                INNER JOIN discount_websites as dw on dw.discount_id = dm.discount_id
                INNER JOIN discount_date_created as dtc on dtc.discount_id = dm.discount_id";
// WHERE dm.discount_end > UNIX_TIMESTAMP()

        $statement = $db->query($sql);
        return $statement->fetchAll();
    }

    public function getRestrictions() {
        $mongodb = $this->getDb('standard', 'mongo');
        $config = $this->config;
        $templateWorkingMongoCollection = $mongodb->selectCollection($config->standard->restrictions->working);
        $migratorLogsCollection = $mongodb->selectCollection($config->standard->migrator->logs);

        $db = $this->getDb('standard', 'mysql');
        $sql = "SELECT template_name as templateName,
            template_id as id, date_created as modified,
            excluded_parts as part_excluded,
            excluded_brands as brand_excluded
            FROM template_list";

        $statement = $db->query($sql);
        $results = $statement->fetchAll();

        if ($results) {
            foreach ($results as $result) {
                $restriction = array();

                $parts = array();
                $brands = array();
                $restriction['modified'] = false;
                $restriction['creator'] = 'migration';
                $restriction['createdAt'] = new MongoDate(strtotime($result['modified']));
                $restriction['updatedAt'] = new MongoDate(strtotime($result['modified']));
                $counter = 0;

                $restriction['templateName'] = $result['templateName'];

                $sqlBrand = "SELECT brand_name as brand
                FROM template_brand
                WHERE template_id = '{$result['id']}'";
                $brandStatement = $db->query($sqlBrand);
                $brandResult = $brandStatement->fetchAll();

                if ($brandResult) {
                    foreach ($brandResult as $brand) {
                        if (!in_array($brand['brand'], $brands)) {
                            $brands[] = $brand['brand'];
                        }
                    }
                }

                $sqlPart = "SELECT part_name as part
                FROM template_part
                WHERE template_id = '{$result['id']}'";
                $partStatement = $db->query($sqlPart);
                $partResult = $partStatement->fetchAll();

                if ($partResult) {
                    foreach ($partResult as $part) {
                        if (!in_array($part['part'], $parts)) {
                            $parts[] = $part['part'];
                        }
                    }
                }

                $bc = count($brands) - 1;

                $restriction['brands'] = $this->insertionSort($brands, count($brands));
                $restriction['parts'] = $this->insertionSort($parts, count($parts));

                if ($result['part_excluded'] == '0' && $result['brand_excluded'] == '0') {
                    $restriction['type'] = 'include';
                } elseif ($result['part_excluded'] == '1' && $result['brand_excluded'] == '1') {
                    $restriction['type'] = 'exclude';
                }

                if (($result['part_excluded'] == '0' && $result['brand_excluded'] == '1') || ($result['part_excluded'] == '1' && $result['brand_excluded'] == '0')) {
                    $log = array(
                        'templateName' => $restriction['templateName'],
                        'Error' => "Template is a combination of exclude and include"
                    );

                    $migratorLogsCollection->update(
                            array(
                        'templateName' => $restriction['templateName']
                            ), $log, array('upsert' => true)
                    );
                } else {
                    /* $templateWorkingMongoCollection->update(
                      array(
                      'templateName' => $restriction['templateName']
                      ),
                      $restriction, array('upsert' => true)
                      ); */
                }
            }
            return true;
        }
        return false;
    }

    /**
     *
     * @param integer $discountId
     * @return Array
     */
    public function recurringInfo($discountId) {
        $db = $this->getDb('standard', 'mysql');
        $sql = "SELECT * FROM discount_recurring WHERE discount_id = $discountId";
        $statement = $db->query($sql);
        return $statement->fetchAll();
    }

    public function syncRestrictions() {
        echo "Start Synching..\n";
        $mongodb = $this->getDb('standard', 'mongo');
        $config = $this->config;
        $templateLiveMongoCollection = $mongodb->selectCollection($config->standard->restrictions->live);
        $templateLiveMongoCollection->setSlaveOkay(false);
        $templateWorkingMongoCollection = $mongodb->selectCollection($config->standard->restrictions->working);
        $templateWorkingMongoCollection->setSlaveOkay(false);
        $templateLive = $templateLiveMongoCollection->find(array("creator" => "migration"))->sort(array("templateName" => 1));
        $ctr = 1;
        foreach ($templateLive as $template) {
            $templateName = $template['templateName'];
            $templateId = $template['templateId'];
            echo "{$ctr} {$template['templateName']} ";
            $countBrands = count($template['brands']);
            echo "count: {$countBrands} \n";
            $db = $this->getDb('standard', 'mysql');

            $sql = "SELECT
            td.discount_id as discountId,
            tl.template_name as templateName,
            tl.date_created as modified,
            tl.excluded_parts as part_excluded,
            tl.excluded_brands as brand_excluded
            FROM template_discount as td
            INNER JOIN template_list as tl on tl.template_id=td.template_id
            WHERE tl.template_name = '" . $templateName . "' order by td.discount_id desc limit 1";

            $statement = $db->query($sql);
            $result = $statement->fetchAll();

            foreach ($result as $template) {
                $discountId = $template['discountId'];
                echo "discount id: {$discountId} \n";
                $sqlBrand = "SELECT tb.brand_name as brand
                FROM template_discount as td
                INNER JOIN template_brand as tb on tb.template_id=td.template_id
                WHERE td.discount_id = '" . $discountId . "'";
                $brandStatement = $db->query($sqlBrand);
                $brandResult = $brandStatement->fetchAll();
                $brands = array();
                $dupBrands = 0; 
                if ($brandResult) {
                    foreach ($brandResult as $brand) {
                        if (!in_array($brand['brand'], $brands)) {
                            $brands[] = $brand['brand'];
                        } else {
//                            echo $brand['brand'] . "\n";
                            $dupBrands++;
                        }
                    }
                }
                echo "number of duplicate brands: {$dupBrands} \n";
                $sqlPart = "SELECT tp.part_name as part
                FROM template_discount as td
                INNER JOIN template_part as tp on tp.template_id=td.template_id
                WHERE td.discount_id = '" . $discountId . "'";
                $partStatement = $db->query($sqlPart);
                $partResult = $partStatement->fetchAll();
                $parts = array();
                $dupParts = 0; 
                if ($partResult) {
                    foreach ($partResult as $part) {
                        if (!in_array($part['part'], $parts)) {
                            $parts[] = $part['part'];
                        } else {
//                            echo $part['part'] . "\n";
                            $dupParts++;
                        }
                    }
                }
                echo "number of duplicate parts: {$dupParts} \n";
            }
            $countNewBrands = count($brandResult);
            $countNewParts = count($parts);

            $restriction = array();
            $restriction['brands'] = $this->insertionSort($brands, count($brands));
            $restriction['parts'] = $this->insertionSort($parts, count($parts));
            $restriction['modified'] = false;
            $restriction['updatedAt'] = new MongoDate(time());

            if ($result['part_excluded'] == '0' && $result['brand_excluded'] == '0') {
                $restriction['type'] = 'include';
            } elseif ($result['part_excluded'] == '1' && $result['brand_excluded'] == '1') {
                $restriction['type'] = 'exclude';
            }



            $template = $templateWorkingMongoCollection->findOne(array('templateId' => $templateId));
            if ($template) {
                $inExisting = array_diff($template['brands'], $restriction['brands']);
                echo "Brands in dcms : " . implode(",", $inExisting) . "\n";
                $inUpdated = array_diff($restriction['brands'], $template['brands']);
                echo "Brands in alloem_marketing : " . implode(",", $inUpdated) . "\n";
//                $liveUpdate = $templateLiveMongoCollection->update(
//                        array('templateId' => $templateId), array('$set' => $restriction), array('w' => 1)
//                );
//                $workingUpdate = $templateWorkingMongoCollection->update(
//                        array('templateId' => $templateId), array('$set' => $restriction), array('w' => 1)
//                );
            }

            echo "New brand count: {$countNewBrands} \n";
            echo "New part count: {$countNewParts} \n";
            echo "\n";
            $ctr++;
        }
    }

}
