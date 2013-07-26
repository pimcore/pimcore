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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Lock_Resource extends Pimcore_Model_Resource_Abstract {

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

    public function isLocked ($key, $expire = 120) {
        $lock = $this->db->fetchRow("SELECT * FROM locks WHERE id = ?", $key);

        // a lock is only valid for 2 minutes
        if(!$lock) {
            return false;
        } else if(is_array($lock) && array_key_exists("id", $lock) && $lock["date"] < (time()-$expire)) {
            if($expire > 0){
                $this->release($key);
                return false;
            }
            return false;
        }

        return true;
    }

    public function acquire ($key) {

        while($this->isLocked($key)) {
            sleep(1);
        }

        $this->db->insert("locks", array(
            "id" => $key,
            "date" => time()
        ));
    }

    public function release ($key) {
        $this->db->delete("locks", "id = '" . $key . "'");
    }

    public function lock ($key) {
        $this->db->insertOrUpdate("locks", array(
            "id" => $key,
            "date" => time()
        ));
    }
}
