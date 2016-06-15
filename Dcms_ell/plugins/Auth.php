<?php

class Dcms_Plugin_Auth extends Zend_Controller_Plugin_Abstract {

    protected $_otuGuest = array(
        'dcms.coupon.couponsecondary',
        'dcms.coupon.otucouponsecondary',
    );
    protected $_otuAdmin = array(
        'dcms.coupon.addotucoupon',
        'dcms.coupon.otubatchlist',
        'dcms.coupon.otucoupon',
        'dcms.coupon.updateotucoupon',
        'dcms.coupon.export-otu',
        'dcms.exclusion.ajax',
    );

    public function __construct() {
        
    }

    public function preDispatch($request) {
        if (php_sapi_name() != 'cli') {
            // $request = $this->_request;
            if (strtolower($request->getModuleName()) == 'dcms') {

                $resource = strtolower($request->getModuleName()) . "." . $request->getControllerName() . "." . $request->getActionName();
                $regUser = Zend_Registry::get('user');
                $username = $regUser->getUsername();
                $perm = $regUser->getPermissions();

                $roles = $regUser->getRoles();
                $toollist = Zend_Registry::get('toollist');
                $roles = preg_grep('/^dcms.*/', $roles);

                $perms = array();
                $countRoles = count($roles);
                foreach ($roles as $role) {
                    if ($countRoles == 1) {
                        $perms = $toollist['dcms']['roles'][$role]['permissions'];
                        break;
                    }
                    $perms = array_merge($perms, $toollist['dcms']['roles'][$role]['permissions']);
                }
                $flipped_perms = array_flip($perms);
                $session = new Dcms_Model_Switch();
                $view = Zend_Registry::get('view');
                $switch = $session->getSwitch();
                $view->killswitch = $switch;
                if (!empty($switch) && isset($switch['onetimeuse']) && $switch['onetimeuse']) {
                    $this->filterPage($flipped_perms);
                }
//            if (!in_array($resource, $perms)) {
                if (!isset($flipped_perms[$resource])) {
                    $flashMessener = new Zend_Session_Namespace('FlashMessenger');

                    if (in_array($resource, array_merge(
                                            $this->_otuGuest, $this->_otuAdmin
                                    ))) {
                        $flashMessener->default = array(
                            array(
                            'error' => "OTU is currently disabled please contact administrator.")
                        );
                    }else{
                        $flashMessener->default = array(
                        array(
                            'error' => "You are not allowed to access that page."
                        )
                    );
                    }
                    
                    if (in_array("dcms.admin", $roles)) {
                        $this->_response->setRedirect("/dcms/index/home");
                    } else {
                        $this->_response->setRedirect("/" . str_replace(".", "/", $perms[0]));
                    }
                }
            }
        }
    }

    private function filterPage(&$pages) {

        $otuAdmin = array_merge(
                $this->_otuGuest, $this->_otuAdmin
        );

        foreach ($otuAdmin as $value) {
            unset($pages[$value]);
        }
    }

}

?>
