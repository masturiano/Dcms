<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Model_CouponValues {

    public function getTypes() {
        return array('percent' => "Percent", 'dollar' => "Dollar");
    }

    public function getSites() {
        return array(
            'apw' => "autopartswarehouse.com",
            'cs' => "car-stuff.com",
            'ia' => "innerauto.com",
            'jcwhitney.com' => "jcwhitney.com",
            'pt' => "partstrain.com",
            'rp' => "racepages.com",
            'stylintrucks.com' => "stylintrucks.com",
            'tpb' => "thepartsbin.com",
            'uap' => "usautoparts.net"
        );
    }

    public function getApplyCouponTo() {
        return array(
            '1' => 'Online',
            '0' => 'Offline',
            '3' => 'Both'
        );
    }

    public function getApplyDiscountTo() {
        return array(
//            'ORDERTOTAL' => "Order Total",
            'SUBTOTAL' => "Sub-total",
            'SHIPPING' => "Shipping",
            //'TAX' => "Sales Tax",
            'ITEM' => "Order Line Item"
        );
    }

    public function getExpirations() {
        return array(
            'nonexpiring' => "Non-Expiring",
            'expiring' => "Expiring",
            'recurring' => "Recurring",
            'firstxusers' => "First X Users"
        );
    }

    public function getNumberOfMonths() {
        $months = array();
        for ($x = 1; $x <= 12; $x++) {
            $months["$x"] = "$x month" . (($x > 1) ? "s" : "");
        }
        return $months;
    }

    public function getMonths() {
        return array(
            '1' => "Jan",
            '2' => "Feb",
            '3' => "Mar",
            '4' => "Apr",
            '5' => "May",
            '6' => "Jun",
            '7' => "Jul",
            '8' => "Aug",
            '9' => "Sep",
            '10' => "Oct",
            '11' => "Nov",
            '12' => "Dec"
        );
    }

    public function getYears() {
        $years = array();
        $curyear = date("Y");
        $start = $curyear;
        $end = $curyear + 10;
        for ($ctr = $start; $ctr <= $end; $ctr++) {
            $years[$ctr] = $ctr;
        }
        return $years;
    }

    public function getOwner() {
        return array(
            'e_commerce_merchandising' => "E-commerce Merchandising",
            'social_media' => "Social Media",
            'affliate' => "Affliate"
        );
    }

    public function getCountPerPage() {
        $pages = array();
        $pagecount = 10;
        for ($counter = 1; $counter <= 10; $counter++, $pagecount += 10) {
            $pages["$pagecount"] = $pagecount;
        }
        return $pages;
    }

}