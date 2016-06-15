<?php

/**
 * Dcms_Service_Dcms
 * 
 * @package Dcms_Service
 * @subpackage Dcms
 * @author Faustino M. Olpindo, Jr.
 * @copyright 2012
 * @version $id$
 */
class Dcms_Service_Migrator_Mongo extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config
     */
    protected $config;

    /**
     *
     * @var Mongo
     */
    protected $dbResource;
    /**
     *
     * @var type 
     */
    protected $site;

    /**
     *
     * @param Zend_Config $config
     * @param GearmanWorker $worker
     * @param USAP_Resource_Mongomultidb $mongoResource 
     */
    public function __construct(Zend_Config $config, USAP_Resource_Mongomultidb $mongoResource) {
        $this->config = $config;
        $this->dbResource = $mongoResource;
    }

    /**
     *
     * @param type $site
     * @param type $collection 
     */
    public function migrate($site, $collection) {
        $this->site = strtolower($site);
        $sourceCoupons = $this->getCoupons($site);
        $this->insertCoupons($sourceCoupons, $collection);
    }

    /**
     *
     * @param type $site
     * @return type 
     */
    public function getCoupons($site) {
        $sadapter = $this->mongoResource->getAdapter($site);
        $scol = $sadapter->getCollection($sourceCollection);
        $filter = $this->filter;
        $cursor = $scol->find($filter);
        $coupons = array();
        do {
            $cursor->next();
            $current = $cursor->current();
            unset($current['_id']);
            $coupons[] = $current;
        } while ($cursor->hasNext());
        return $coupons;
    }

    /**
     *
     * @param array $coupons
     * @param type $standard
     * @return boolean 
     */
    public function insertCoupons(Array $coupons, $standard) {
        $coupons = $this->oldToNewCouponMapper($coupons);
        $notInserted = array();
        if (!$coupons) {
            return false;
        } else {
            $tadapter = $this->mongoResource->getAdapter($standard);
            $tcol = $tadapter->getCollection($standardCollection);
            foreach ($coupons as $coupon) {
                $up = $tcol->update(
                        array('code' => $coupon['code']), array('$set' => $coupon), array('upsert' => true)
                );
                if (!empty($up)) {
                    $couponCode = $coupon['code'];
                    $notInserted[$couponCode] = $coupon;
                }
            }
            return true;
        }
    }

    /**
     *
     * @param array $coupons
     * @return boolean|array 
     */
    public function oldToNewCouponMapper(Array $coupons) {
        $site = $this->site;
        if ($site == 'jcw') {
            
        } elseif ($site == 'stt') {
            
        } else {
            return false;
//            throw new Exception("Unable to support migration for site $site.");
        }
        return $coupons;
    }

    /**
     *
     * @param type $coupon
     * @return boolean 
     */
    public function oldCouponModelCheck($coupon) {
        if (!empty($coupon)) {
            $site = $this->site;
            if ($site == 'jcw') {
                if ($this->validateCoupon($coupon)) {
                    return true;
                }
            } elseif ($site == 'stt') {
                if ($this->validateCoupon($coupon)) {
                    return true;
                }
            } else {
                return false;
//                throw new Exception("Model is not recognize for site $site.");
            }
        }
    }

    /**
     *
     * @param type $coupon
     * @return type 
     */
    public function validateCoupon($coupon) {
        $document = $this->getModel();
        $buffer = array();
        foreach ($document as $property) {
            if (!in_array($property, $buffer)) {
                $buffer[] = $property;
            }
        }
        return !empty($buffer) ? false : true;
    }

    /**
     *
     * @return boolean 
     */
    public function getModel() {
        $site = $this->site;
        if ($site == 'stt') {
            return array(
                'stt'
            );
        } elseif ($site == 'jcw') {
            return array(
                'jcw'
            );
        } else {
            return false;
        }
    }

    /**
     *
     * @param type $collection
     * @return array|boolean 
     */
    public function newCouponModel($collection = null) {
        $dcms_coupons = array(
            'coupon' => array(
                'code',
                'createdAt',
                'updatedAt',
                'type',
                'siteId',
                'discountVal',
                'amountQualifier',
                'stackable',
                'creator'
            ),
            'stats' => array('usedCtr', 'appliedCtr'),
            'restrictionTmpl',
            'modified',
            'status'
        );

        $dcms_restrictions = array(
            'type',
            'brands' => array(),
            'parts' => array(),
            'sku' => array(),
            'pfcode' => array(),
            'categoryPath' => array(),
            'creator',
            'modified'
        );

        $dcms_domains = array(
            'siteId',
            'name',
            'enabled',
            'createdAt',
            'updatedAt',
            'creator'
        );

        $dcms_translog = array(
            'type',
            'subject',
            'transDetails',
            'transData' => array(),
            'creator',
            'createdAt',
            'updatedAt'
        );
        $model = array(
            'dcms_coupons' => $dcms_coupons,
            'dcms_restrictions' => $dcms_restrictions,
            'dcms_domains' => $dcms_domains,
            'dcms_translog' => $dcms_translog
        );
        if (!empty($collection)) {
            return $model;
        } else {
            if (array_key_exists($collection, $model)) {
                return $model[$collection];
            } else {
                return false;
            }
        }
    }

}