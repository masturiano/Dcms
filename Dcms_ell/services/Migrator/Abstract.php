<?php

class Dcms_Service_MigratorAbstract extends USAP_Service_ServiceAbstract {

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

}