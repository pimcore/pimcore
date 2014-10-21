<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Lock;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("locks");
    }

    /**
     * @param $key
     * @param int $expire
     * @return bool
     */
    public function isLocked ($key, $expire = 120) {
        if(!is_numeric($expire)) {
            $expire = 120;
        }

        $lock = $this->db->fetchRow("SELECT * FROM locks WHERE id = ?", $key);

        // a lock is only valid for a certain time (default: 2 minutes)
        if(!$lock) {
            return false;
        } else if(is_array($lock) && array_key_exists("id", $lock) && $lock["date"] < (time()-$expire)) {
            if($expire > 0){
                \Logger::debug("Lock '" . $key . "' expired (expiry time: " . $expire . ", lock date: " . $lock["date"] . " / current time: " . time() . ")");
                $this->release($key);
                return false;
            }
        }

        return true;
    }

    /**
     * @param $key
     * @param int $expire
     * @param int $refreshInterval
     */
    public function acquire ($key, $expire = 120, $refreshInterval = 1) {

        \Logger::debug("Acquiring key: '" . $key . "' expiry: " . $expire);

        if(!is_numeric($refreshInterval)) {
            $refreshInterval = 1;
        }

        while(true) {
            while($this->isLocked($key, $expire)) {
                sleep($refreshInterval);
            }

            try {
                $this->lock($key, false);
                return true;
            } catch (\Exception $e) {
                \Logger::debug($e);
            }
        }
    }

    /**
     * @param $key
     */
    public function release ($key) {

        \Logger::debug("Releasing: '" . $key . "'");

        $this->db->delete("locks", "id = " . $this->db->quote($key));
    }

    /**
     * @param $key
     * @param bool $force
     */
    public function lock ($key, $force = true) {

        \Logger::debug("Locking: '" . $key . "'");

        $updateMethod = $force ? "insertOrUpdate" : "insert";

        $this->db->$updateMethod("locks", array(
            "id" => $key,
            "date" => time()
        ));
    }

    public function getById($key) {
        $lock = $this->db->fetchRow("SELECT * FROM locks WHERE id = ?", $key);
        $this->assignVariablesToModel($lock);
    }
}
