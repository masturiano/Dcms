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
class Dcms_Service_Migrator_MigrateCoupons extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config
     */
    protected $config;
    protected $_sourceOwners;
    protected $_destinationOwners;
    protected $_sourceCouponLive;
    protected $_sourceCouponWorking;
    protected $_destinationCouponLive;
    protected $_destinationCouponWorking;
    protected $_sourceRestrictionsLive;
    protected $_sourceRestrictionsWorking;
    protected $_destinationRestrictionsLive;
    protected $_destinationRestrictionsWorking;
    protected $_templateKeyCounterCollection;
    protected $_setSlaveOkay;

    /**
     *
     * @param Zend_Config $config
     * @param Zend_Application_Resource_Db $mysqlMultiDbResource
     * @param Zend_Application_Resource_ResourceAbstract $mongoMultiDbResource 
     */
    public function __construct(Zend_Config $config) {
        $this->_config = $config;
        $this->_setSlaveOkay = $config->migratecoupons->setSlaveOk;
    }

    public function initMongoConnection($sourceDb, $destinationDb) {
        $connnectionConfig = $this->_config->resources->mongomultidb->standard;

        $configStandard = $this->_config->standard;

        $sourceMongoConnection = array(
            "host" => $connnectionConfig->host,
            "database" => $sourceDb,
            "replica" => $connnectionConfig->replica,
            "collection" => $configStandard->coupons->live
        );

        $sourceMongoConn = new USAP_Db_Mongo_Adapter($sourceMongoConnection);


        $destinationMongoConnection = array(
            "host" => $connnectionConfig->host,
            "database" => $destinationDb,
            "replica" => $connnectionConfig->replica,
            "collection" => $configStandard->coupons->live
        );


        $destinationMongoConn = new USAP_Db_Mongo_Adapter($destinationMongoConnection);


        $this->_sourceCouponLive = $this->_getCollection($sourceMongoConn);
        $this->_sourceCouponWorking = $this->_getCollection($sourceMongoConn, $configStandard->coupons->working);


        $this->_destinationCouponLive = $this->_getCollection($destinationMongoConn);
        $this->_destinationCouponWorking = $this->_getCollection($destinationMongoConn, $configStandard->coupons->working);


        $this->_sourceOwners = $this->_getCollection($sourceMongoConn, $configStandard->owners->live);
        $this->_destinationOwners = $this->_getCollection($destinationMongoConn, $configStandard->owners->live);

        $this->_sourceRestrictionsLive = $this->_getCollection($sourceMongoConn, $configStandard->restrictions->live);
        $this->_sourceRestrictionsWorking = $this->_getCollection($sourceMongoConn, $configStandard->restrictions->working);

        $this->_destinationRestrictionsLive = $this->_getCollection($destinationMongoConn, $configStandard->restrictions->live);
        $this->_destinationRestrictionsWorking = $this->_getCollection($destinationMongoConn, $configStandard->restrictions->working);

        $this->_templateKeyCounterCollection = $this->_getCollection($destinationMongoConn, $configStandard->templatecounter->live);
    }

    /**
     * @return type
     * 
     * php usaptool.php -a dcms.migrate.migratecoupons -e development -q '{"coupon.domains":"jcwhitney.com"}' -x "dcms" -y "dcmsTest" 
     * 
     */
    public function migrate($query, $sourceDb, $destinationDb) {
        echo "starting " . date('r') . "\n";
        try {
            $query = json_decode($query);

            $this->initMongoConnection($sourceDb, $destinationDb);
            $sourceGetCouponsWorking = $this->_sourceCouponWorking->find($query);

            $ctr = 1;
            foreach ($sourceGetCouponsWorking as $coupons) {

                /*
                 * coupon code
                 * 
                 * check owner 
                 *  - if owner doesnt exists in destination db upsert
                 *  - get destination owner id
                 * check restriction
                 *  - if restriction is not yet existing upsert
                 *  - if restriction exists update restriction with source data
                 *  - get templateId
                 * check if coupon exists on destination
                 *  - if exists, update coupon with source data
                 * 
                 */

                echo "{$ctr}.) {$coupons['coupon']['code']} ";

                $ownerMongId = new MongoId($coupons['coupon']['owner']);

                $coupons['coupon']['owner'] = $this->_getOwnerId($ownerMongId);

                $sourceTemplateName = $coupons['restrictions'];

                $templateId = $this->_getRestrictionId();

                $coupons['restrictions'] = $sourceTemplateName;

                $coupons['templateId'] = $templateId;


                unset($coupons['_id']);
                $migrateCoupon = $this->_destinationCouponWorking->update(array('coupon.code' => $coupons['coupon']['code'], 'coupon.domains' => $coupons['coupon']['domains']), array('$set' => $coupons), array('upsert' => true, 'safe' => true));
                if (isset($migrateCoupon['updatedExisting']) && $migrateCoupon['updatedExisting'] == 1) {
                    echo " - updated";
                }
                if ($coupons['status'] == "published" && $migrateCoupon) {
                    $this->_destinationCouponLive->update(array('coupon.code' => $coupons['coupon']['code'], 'coupon.domains' => $coupons['coupon']['domains']), array('$set' => $coupons), array('upsert' => true, 'safe' => true));
                }

                echo "\n";
                $ctr++;
            }
            echo "ended " . date('r') . "\n";
        } catch (Execption $e) {
            echo "ended " . date('r') . "\n";
            echo "with error: " . $e->getMessage() . "\n";
        }
    }

    private function _getOwnerId(MongoId $ownerMongId) {

        if (!is_object($ownerMongId)) {
            $ownerMongId = new MongoId($ownerMongId);
        }

        $getOwnerSource = $this->_sourceOwners->findOne(array('_id' => $ownerMongId));

        if ($getOwnerSource) {

            $getOwnerDestination = $this->_destinationOwners->findOne(array('owner' => $getOwnerSource['owner']));

            if (!$getOwnerDestination) {

                unset($getOwnerSource['_id']);
                $return = $this->_destinationOwners->update(array('owner' => $getOwnerSource['owner']), array('$set' => $getOwnerSource), array('upsert' => true, 'w' => 1));
                $newOwnerId = (string) $return['upserted'];
            } else {
                $newOwnerId = (string) $getOwnerDestination['_id'];
            }
        } else {
            return "";
        }


        return $newOwnerId;
    }

    private function _getRestrictionId($templateName) {
        $templateName = (string) $templateName;

        $getRestrictionsWorkingSource = $this->_sourceRestrictionsWorking->findOne(array('templateName' => $templateName));

        if ($getRestrictionsWorkingSource && $templateName != "") {

            $getRestrictionsWorkingDestination = $this->_destinationRestrictionsWorking->findOne(array('templateName' => $templateName));

            if (!$getRestrictionsWorkingDestination) {

                unset($getRestrictionsWorkingSource['_id']);

                $templateIdDocument = $this->_templateKeyCounterCollection->findOne(array('key' => 'unique_key'));

                $templateId = (int) $templateIdDocument['key_counter'] + 1;

                $this->_templateKeyCounterCollection->update(
                        array('key' => 'unique_key'), array('$set' => array('key_counter' => $templateId))
                );

                $this->_destinationRestrictionsWorking->update(
                        array(
                    'templateName' => $templateName
                        ), $getRestrictionsWorkingSource, array('upsert' => true, 'w' => 1)
                );

                $getRestrictionsLiveSource = $this->_sourceRestrictionsLive->findOne(array('templateName' => $templateName));

                if ($getRestrictionsLiveSource) {
                    $this->_destinationRestrictionsLive->update(
                            array(
                        'templateName' => $templateName
                            ), $getRestrictionsWorkingSource, array('upsert' => true, 'w' => 1)
                    );
                }
            } else {
                $templateId = (int) $getRestrictionsWorkingSource['templateId'];
            }
        } else {
            return "";
        }




        return $templateId;
    }

    private function _getCollection($adapter, $collection = "") {
        if($collection == ""){
            $getCollection = $adapter->getCollection();
        }else{
            $getCollection = $adapter->getCollection($collection);
        }
        
        return $getCollection->setSlaveOkay($this->_setSlaveOkay);
    }

}
