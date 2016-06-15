<?php

/**
 * @catagory USAPTool_Modules
 * @package Dcms_Controller
 * @author mardiente
 * @copyright 2011
 * @version $Id$
 */
class Dcms_IndexController extends USAP_Controller_Action {

    public function init() {
        parent::init();
    }

    public function indexAction() {
        $this->_redirect('/dcms/index/home');
    }

    /**
     * 
     */
    public function homeAction() {
        /**
         * get coupon form details particularly site listing from dcms_coupon_form_details collection 
         */
        $this->view->headTitle('Home');
        $this->_displayList();
    }

    public function homeguestAction() {
        /**
         * get coupon form details particularly site listing from dcms_coupon_form_details collection 
         */
        $this->view->headTitle('Home');
        $this->_displayList();
    }

    /**
     * 
     */
    public function searchAction() {
        /**
         * get coupon form details particularly site listing from dcms_coupon_form_details collection 
         */
        $this->view->headTitle('Search');
        $this->_displayList();
    }

    public function deleteAction() {
        if ($this->getRequest()->isPost()) {
            $couponService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Coupon", "");
            $getPost = $this->_request->getPost();
            unset($getPost['checkall']);
            $result = $couponService->deleteCouponMultiple($getPost);
            if (!isset($result['error'])) {
                $this->addMessage('Coupon/s has been successfully deleted.');
            } else {
				$message = isset($result['message']) ? $result['message'] : "Could not delete coupon/s";
                $this->addMessage($message, $result['error']);
            }
        }
        $this->_redirect('/dcms/index');
    }

    private function _setToView($input) {
        foreach ($input as $k => $v) {
            $this->view->{$k} = $v;
        }
    }

    private function _displayList() {
        $couponListService = USAP_Service_ServiceAbstract::getService("Dcms_Service_CouponList", "");
        $request = $this->_getRequestFilter();
        $couponList = $couponListService->displayCouponList($request);
        $this->_setToView($couponList['setToView']);
        unset($couponList['setToView']);
        $this->_setToView($couponList);
    }

    private function _getRequestFilter() {
        /*         * *
         * set query parameters like page, advance query, site selected, count per page
         */
        if ($this->getRequest()->isPost()) {
            $request = $this->getRequest()->getPost();
            $request['page'] = 1;
        } else {
            $request = $this->_request->getParams();
            unset($request['module']);
            unset($request['controller']);
            unset($request['action']);
            $request['countperpage'] = $this->_request->getParam('countperpage', 10);
            $request['page'] = $this->_request->getParam('page', 1);
        }

        return $request;
    }

}

