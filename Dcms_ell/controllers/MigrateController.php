<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_MigrateController
 * @author folpindo
 * @copyright 2012
 * @version $Id$
 */
class Dcms_MigrateController extends USAP_Controller_Action {

    public function init() {
        parent::init();
    }

    public function indexAction() {
        try {
            $options = $this->_getOpt();
            $service = 'Dcms_Service_Migrator_' . ucfirst(strtolower($options->domain)) . '_Worker';
            $worker = USAP_Service_ServiceAbstract::getService($service, "");
            $worker->migrate($options->site);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

//    public function preDispatch() {
//        if (php_sapi_name() != 'cli') {
//            $this->_redirect("/dcms");
//        }
//    }
    
    public function migrateRestrictionsAction(){
        try {
            $options = $this->_getOpt();
            $service = 'Dcms_Service_Migrator_' . ucfirst(strtolower($options->domain)) . '_Worker';
            $worker = USAP_Service_ServiceAbstract::getService($service, "");
            $worker->migrateRestrictions();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function syncRestrictionsAction() {

        $options = $this->_getOpt();
        $service = 'Dcms_Service_Migrator_' . ucfirst(strtolower($options->domain)) . '_Worker';
        $worker = USAP_Service_ServiceAbstract::getService($service, "");
        $worker->syncRestrictions();
    }

    public function migrateDomainsAction() {
        try {
            $options = $this->_getOpt();
            if (!file_exists($options->f)) {
                throw new Exception("File does not exist.");
            }
            $fh = fopen($options->f, 'r');
            $counter = 0;
            $bootstrap = $this->getInvokeArg('bootstrap');
            $bootstraps = $bootstrap->getResource('modules');
            $mongoResource = $bootstraps['Dcms']->getPluginResource('mongomultidb');
            $dcmsDomainsCollection = $mongoResource->getAdapter('standard')->getCollection('dcms_domains');
            $dcmsDomainsCollection->setSlaveOkay(false);
            $siteId = 1;
            do {
                if ($counter >= 2) {
                    $buff = null;
                    $row = explode(",", $buffer);
                    $acronym = trim($row[1]);
                    $subdomain = trim($row[2]);
                    $domain = trim($row[3]);
                    $channelCode = trim($row[4]);
                    $criteria = array('name' => $domain);
                    $isUsapSite = false;
                    if ($domain != 'stylintrucks.com' || $domain != 'jcwhitney.com') {
                        $isUsapSite = true;
                        if ($siteId == 2 || $siteId == 4)
                            $siteId++;
                    }
                    if ($domain == 'stylintrucks.com') {
                        $buff = 2;
                        $isUsapSite = false;
                    }
                    if ($domain == 'jcwhitney.com') {
                        $buff = 4;
                        $isUsapSite = false;
                    }
                    $exist = $dcmsDomainsCollection->findOne($criteria);
                    $document = array(
                        'siteId' => !empty($buff) ? $buff : $siteId,
                        'acronym' => $acronym,
                        'name' => $domain,
                        'channelCode' => array($channelCode),
                        'serverName' => array($subdomain),
                        'creator' => '-',
                        'isUsapSite' => $isUsapSite,
                        'createdAt' => new MongoDate(),
                        'updatedAt' => new MongoDate()
                    );
                    if ($exist) {
                        $existingChannelCodes = $exist['channelCode'];
                        if (!in_array($channelCode, $existingChannelCodes)) {
                            $dcmsDomainsCollection->update(
                                    $criteria, array('$push' => array(
                                    'channelCode' => $channelCode,
                                    'serverName' => $subdomain
                                )
                                    ), array('upsert' => true, 'safe' => true)
                            );
                        }
                    } else {
                        $dcmsDomainsCollection->insert($document, array('safe' => true));
                        if ($isUsapSite)
                            $siteId++;
                    }
                }
                $counter++;
            } while (($buffer = fgets($fh, 4096)) !== false);
            $dcmsDomainsCollection->ensureIndex(array('name' => true, 'channelCode' => true));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function restoreAction() {
        try {
            $front = Zend_Controller_Front::getInstance();
            $bootstrap = $this->getInvokeArg('bootstrap');
            $bootstraps = $bootstrap->getResource('modules');
            $mongoResource = $bootstraps['Dcms']->getPluginResource('mongomultidb');

            $restrictionsWorkingCollectionBk = $mongoResource->getAdapter('standard')->getCollection('bk_dcms_restrictions_working');
            $restrictionsWorkingCollection = $mongoResource->getAdapter('standard')->getCollection('dcms_restrictions_working');

            $restrictionsLiveCollectionBk = $mongoResource->getAdapter('standard')->getCollection('bk_dcms_restrictions');
            $restrictionsLiveCollection = $mongoResource->getAdapter('standard')->getCollection('dcms_restrictions');

            $couponsWorkingCollectionBk = $mongoResource->getAdapter('standard')->getCollection('bk_dcms_coupons_working');
            $couponsWorkingCollection = $mongoResource->getAdapter('standard')->getCollection('dcms_coupons_working');

            $couponsLiveCollectionBk = $mongoResource->getAdapter('standard')->getCollection('bk_dcms_coupons');
            $couponsLiveCollection = $mongoResource->getAdapter('standard')->getCollection('dcms_coupons');

            $ownersCollectionBk = $mongoResource->getAdapter('standard')->getCollection('bk_dcms_coupon_owners');
            $ownersCollection = $mongoResource->getAdapter('standard')->getCollection('dcms_coupon_owners');

            $templateKeyCounterCollection = $mongoResource->getAdapter('standard')->getCollection('dcms_unique_keys');

            $couponsWorkingBk = $couponsWorkingCollectionBk->find();

            foreach ($couponsWorkingBk as $couponBk) {
                $templateName = $couponBk['restrictions'];
                if ($templateName != "") {
                    $findMigratedTemplate = $restrictionsWorkingCollection->findOne(array("templateName" => $templateName));
                    if ($findMigratedTemplate) {


                        $couponBk['templateId'] = $findMigratedTemplate['templateId'];
                    } else {
                        $findBackupTemplate = $restrictionsWorkingCollectionBk->findOne(array("templateId" => $couponBk['templateId']));
                        if ($findBackupTemplate) {
                            $templateIdDocument = $templateKeyCounterCollection->findOne(array('key' => 'unique_key'));
                            $templateId = (int) $templateIdDocument['key_counter'] + 1;

                            $templateKeyCounterCollection->update(
                                    array('key' => 'unique_key'), array('$set' => array('key_counter' => $templateId))
                            );
                            $restrictions = $findBackupTemplate;
                            $restrictions['templateId'] = $templateId;
                            $restrictionsWorkingCollection->update(
                                    array(
                                'templateName' => $couponBk['restrictions']
                                    ), $restrictions, array('upsert' => true)
                            );
                            if ($couponBk['status'] == "published") {
                                $restrictionsLiveCollection->update(
                                        array(
                                    'templateName' => $couponBk['restrictions']
                                        ), $restrictions, array('upsert' => true)
                                );
                            }
                            $couponBk['templateId'] = $templateId;
                        }
                    }
                }

                $ownerId = new MongoId($couponBk['coupon']['owner']);
                $findOwnersBk = $ownersCollectionBk->findOne(array("_id" => new MongoId($ownerId)));
                $findOwners = $ownersCollection->findOne(array("owner" => $findOwnersBk['owner']));
                $couponBk['coupon']['owner'] = $findOwners['_id'] . "";
                $couponsWorkingCollection->update(
                        array('coupon.code' => $couponBk['coupon']['code'], 'coupon.domains' => $couponBk['coupon']['domains']), $couponBk, array('upsert' => true)
                );
                if ($couponBk['status'] == "published") {
                    $couponsLiveCollection->update(
                            array('coupon.code' => $couponBk['coupon']['code'], 'coupon.domains' => $couponBk['coupon']['domains']), $couponBk, array('upsert' => true)
                    );
                }
            }

            $otherTemplates = $restrictionsWorkingCollectionBk->find();
            foreach ($otherTemplates as $template) {
                $findTemplate = $restrictionsWorkingCollection->findOne(array("templateName" => $template['templateName']));
                if (!$findTemplate) {
                    echo "working : {$template['templateName']} \n";
                    $templateIdDocument = $templateKeyCounterCollection->findOne(array('key' => 'unique_key'));
                    $templateId = (int) $templateIdDocument['key_counter'] + 1;

                    $templateKeyCounterCollection->update(
                            array('key' => 'unique_key'), array('$set' => array('key_counter' => $templateId))
                    );
                    $restrictions = $template;
                    $restrictions['templateId'] = $templateId;
                    $restrictionsWorkingCollection->update(
                            array(
                        'templateName' => $template['templateName']
                            ), $restrictions, array('upsert' => true)
                    );

                    $findLiveTemplateBk = $restrictionsLiveCollectionBk->findOne(array("templateName" => $template['templateName']));
                    $findLiveTemplate = $restrictionsLiveCollection->findOne(array("templateName" => $template['templateName']));
                    if ($findLiveTemplateBk && !$findLiveTemplate) {
                        echo "live : {$template['templateName']} \n";
                        $restrictionsLiveCollection->update(
                                array(
                            'templateName' => $template['templateName']
                                ), $restrictions, array('upsert' => true)
                        );
                    }
                }
            }
            $otherTemplatesLive = $restrictionsLiveCollectionBk->find();
            foreach ($otherTemplatesLive as $templateLive) {
                $findTemplate = $restrictionsWorkingCollectionBk->findOne(array("templateName" => $templateLive['templateName']));
                if (!$findTemplate) {
                    echo "template : {$templateLive['templateName']} not on working copy \n";
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function testAction() {
        $this->_helper->layout()->disableLayout();

            $front = Zend_Controller_Front::getInstance();
            $bootstrap = $this->getInvokeArg('bootstrap');
            $bootstraps = $bootstrap->getResource('modules');
            $mongoResource = $bootstraps['Dcms']->getPluginResource('mongomultidb');
            $couponsCollection = $mongoResource->getAdapter('jcw')->getCollection('coupons');
            $now = time();
//            $exCoupons = $couponsCollection->find(array("end_date" => array('$ne' => false)));
//            $exCoupons = $couponsCollection->find(array("end_date" => array('$gte' => $now)));
            $exCoupons = $couponsCollection->find(
                    array('$or' =>
                        array(
                            array("end_date" => array('$gte' => $now)), 
                            array("end_date" => false)
                            )
                        )
                    );
            $exCoupons = $exCoupons->sort(array("end_date" => -1));
           echo "<table border=1>
               <tr>
               <th></th>
                <th>Coupon Code</th>
                <th>Coupon Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Discount Type</th>
                <th>Apply to</th>
               </tr>"; 
           $ctr= 1;
            foreach ($exCoupons as $exCoupon){
                echo "<tr>";
               echo "<td>{$ctr}</td>";
               echo "<td>{$exCoupon['code']}</td>";
               echo "<td>{$exCoupon['name']}</td>";
               $startDate = ($exCoupon['start_date']) ? date("m/d/Y", $exCoupon['start_date']) : "Nonexpiring";
               $endDate = ($exCoupon['end_date']) ? date("m/d/Y", $exCoupon['end_date']) : "Nonexpiring";
               echo "<td>{$startDate}</td>";
               echo "<td>{$endDate}</td>";
               echo "<td>{$exCoupon['discount_type']}</td>";
               echo "<td>{$exCoupon['apply']}</td>";
               echo "</tr>";
               $ctr++;
            }
       
        exit;
    }
    
    private function _getOpt(){
        $front = Zend_Controller_Front::getInstance();
            $bootstrap = $front->getParam('bootstrap');
            $bootstraps = $bootstrap->getResource('cli');
            $bootstraps->addOptionRules(
                    array(
                        'domain|d-s' => "Domain name.",
                        'site|s-s' => "Site name.",
                        'csvfile|f-s' => "SC domains csv.",
                    )
            );
            return $bootstraps->getGetOpt();
    }


}
