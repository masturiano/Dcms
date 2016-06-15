<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OtuLookupDb4
 *
 * @author gconstantino
 */
class Dcms_Model_OtuLookupDb4 {

    protected $handle;
    protected $mode;
    protected $dbName = '';

    public function __construct($dbName, $mode = 'r') {
        $this->mode = $mode; //n overwrite

        if (empty($dbName)) {
            throw new Db4_lib_Exception("db name is required");
        }
        $this->dbName = $dbName;
    }

    public function open() {
        $this->handle = @dba_open($this->dbName, $this->mode, 'db4');
        if (!$this->handle) {
            throw new Db4_lib_OpenException("Unable to open the database: " . $this->dbName);
        }
    }

    public function lookup($coupon) {
        if (!$this->handle) {
            $this->open();
        }

        $value = dba_exists($coupon, $this->handle);
        return $value;
    }

    public function save($coupon, Array $attribs = array()) {
        $attribs['__coupon'] = $coupon;
        $attribs['__gentime'] = time();

        if (!dba_insert($coupon, serialize($attribs), $this->handle)) {
            throw new Db4_lib_InsertException("Unable to insert $coupon to the database");
        }
    }

    public function firstKey() {
        $this->_checkHandle();
        return dba_firstkey($this->handle);
    }

    public function nextKey() {
        $this->_checkHandle();
        return dba_nextkey($this->handle);
    }

    public function fetch($key) {
        $this->_checkHandle();
        return dba_fetch($key, $this->handle);
    }

    public function close() {
        $this->_checkHandle();
        return @dba_close($this->handle);
    }

    private function _checkHandle() {
        if (!$this->handle) {
            throw new Db4_lib_OpenException("Unable to open the database: " . $this->handle);
        }
    }

    public function getHandle() {
        return $this->handle;
    }

}

?>
