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
 * @package    Element
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Email_Blacklist_Resource extends Pimcore_Model_Resource_Abstract {

    /**
     * Contains all valid columns in the database table
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("email_blacklist");
    }

    /**
     * Get the data for the object from database for the given id
     * @param integer $id
     * @return void
     */
    public function getByAddress($address) {
        $data = $this->db->fetchRow("SELECT * FROM email_blacklist WHERE address = ?", $address);

        if (!$data["address"]) {
            throw new Exception("blacklist item with address " . $address . " not found");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        $this->model->setModificationDate(time());
        if(!$this->model->getCreationDate()) {
            $this->model->setCreationDate(time());
        }

        $version = get_object_vars($this->model);

        // save main table
        foreach ($version as $key => $value) {
            if (in_array($key, $this->validColumns)) {

                if(is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate("email_blacklist", $data);

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("email_blacklist", $this->db->quoteInto("address = ?", $this->model->getAddress() ));
    }
}
