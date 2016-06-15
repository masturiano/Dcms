<?php

class Dcms_Model_Info extends USAP_Module_Info {

    protected static $_instance;
    protected static $_instanceClassName = __CLASS__;

    /**
     * @static
     * @param null $config
     * @return USAP_Module_Info
     */
    public static function getInstance($config = null) {
        $className = self::$_instanceClassName;
        if (empty(self::$_instance) && !self::$_instance instanceof $className) {
            self::$_instance = new self($config);
            return self::$_instance;
        }
        return self::$_instance;
    }

    public function getDisplayName() {
        return "DCMS";
    }

    public function getDescription() {
        return 'This is a dcms world module.';
    }

    public function getType() {
        return "user";
    }

    public function isPublic() {
        return true;
    }

    public function getRoles() {        
        $dcmsGuest = array(
            'dcms.index.homeguest',
            'dcms.index.search',
            'dcms.coupon.couponsecondary',
            'dcms.coupon.otucouponsecondary',
            'dcms.coupon.viewotucoupon',
            'dcms.coupon.view',
            'dcms.couponowners.view',
            'dcms.template.view'
        );

        $dcmsAdmin = array(
            'dcms.index.homeguest',
            'dcms.index.search',
            'dcms.coupon.couponsecondary',
            'dcms.index.index',
            'dcms.index.home',
            'dcms.index.search',
            'dcms.index.delete',
            'dcms.coupon.index',
            'dcms.coupon.add',
            'dcms.coupon.edit',
            'dcms.coupon.searchcoupon',
            'dcms.coupon.addotucoupon',
            'dcms.coupon.ajax',
            'dcms.coupon.otubatchlist',
            'dcms.coupon.dispensebatchlist',
            'dcms.coupon.otucoupon',
            'dcms.coupon.production',
            'dcms.coupon.updateotucoupon',
            'dcms.coupon.export-otu',
            'dcms.coupon.export',
            'dcms.coupon.coupons',
            'dcms.coupon.pages',
            'dcms.template.add',
            'dcms.template.addupload',
            'dcms.template.brandlist',
            'dcms.template.brandpartlist',
            'dcms.template.brandskulist',
            'dcms.template.delete',
            'dcms.template.edit',
            'dcms.template.index',
            'dcms.template.list',
            'dcms.template.partbrandlist',
            'dcms.template.partlist',
            'dcms.template.view',
            'dcms.template.searchtemplate',
            'dcms.template.options',
            'dcms.template.pages',
            'dcms.exclusion.index',
            'dcms.exclusion.ajax',
            'dcms.exclusion.list',
            'dcms.exclusion.exclusionlist',
            'dcms.exclusion.existsearch',
            'dcms.couponowners.index',
            'dcms.couponowners.addowner',
            'dcms.couponowners.updateowner',
            'dcms.couponowners.deleteowner',
            'dcms.couponowners.ajax',
            'dcms.couponowners.view',
            'dcms.sample.queued',
        );
        return array(
            'dcms.admin' => array(
                'name' => 'Dcms Tool Administrator',
                'permissions' => array_merge($dcmsGuest, $dcmsAdmin),
                'toolset' => 'dcms',
                'role' => 'dcms.admin'
            ),
            'dcms.guest' => array(
                'name' => 'Dcms Guest',
                'permissions' => $dcmsGuest,
                'toolset' => 'dcms',
                'role' => 'dcms.guest'
            ),
            'dcms.super' => array(
                'name' => 'Dcms Tool Supervisor',
                'permissions' => array(
                    'dcms.sites.index',
                    'dcms.sites.addsite',
                    'dcms.sites.updatesite',
                    'dcms.sites.deletesite',
                    'dcms.sites.ajax'),
                'toolset' => 'dcms',
                'role' => 'dcms.super'
            ),
            'dcms.otureadonly' => array(
                'name' => 'Dcms OTU status Read only',
                'permissions' => array('dcms.otucoupon.manage'),
                'toolset' => 'dcms',
                'role' => 'dcms.otureadonly'
            ),
            
            'dcms.otucontrol' => array(
                'name' => 'Dcms OTU status Read/Control',
                'permissions' => array('dcms.otucoupon.manage','dcms.otucoupon.retryworker'),
                'toolset' => 'dcms',
                'role' => 'dcms.otucontrol'
            ),
        );
    }

    public function getPermissions() {
        return array(
            'dcms.read' => array(
                'name' => 'View Coupon',
            ),
            'dcms.create' => array(
                'name' => 'Create Coupon',
            ),
            'dcms.update' => array(
                'name' => 'Export dcms data',
            ),
            'dcms.delete' => array(
                'name' => 'Import dcms data',
            ),
            'dcms.publish' => array(
                'name' => 'Assign tasks to dcms users',
            )
        );
    }

    public function getTools() {
        return array(
            array(
                'name' => 'Home',
                'url' => '/dcms/index/index',
                'roles' => array('dcms.admin')
            ),
            array(
                'name' => 'Template',
                'url' => '/dcms/template/index',
                'roles' => array('dcms.admin')
            ),
            array(
                'name' => 'Coupon',
                'url' => '/dcms/coupon/index',
                'roles' => array('dcms.admin')
            ),
            array(
                'name' => 'Production',
                'url' => '/dcms/coupon/production',
                'roles' => array('dcms.admin')
            ),
            array(
                'name' => 'Home',
                'url' => '/dcms/index/homeguest',
                'roles' => array('dcms.guest')
            ),
            array(
                'name' => 'Search',
                'url' => '/dcms/index/search',
                'roles' => array('dcms.guest')
            ),
            array(
                'name' => 'OTU Coupons',
                'url' => '/dcms/coupon/couponsecondary',
                'roles' => array('dcms.guest')
            ),
            array(
                'name' => 'OTU Status Manager',
                'url' => '/dcms/otucoupon/manage',
                'roles' => array('dcms.otucontrol')
            )
        );
    }

}
