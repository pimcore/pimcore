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
 * @package    Tracking
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tracking_Event_Resource extends Pimcore_Model_Resource_Abstract {

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
        try {
            $this->validColumns = $this->getValidTableColumns($this->getTableName());
        } catch (Exception $e) {

            $this->db->query("
                CREATE TABLE `" . $this->getTableName() . "` (
                    `id` varchar(255) DEFAULT NULL,
                    `date` date DEFAULT NULL,
                    `time` time DEFAULT NULL,
                    `category` varchar(255) DEFAULT NULL,
                    `action` varchar(255) DEFAULT NULL,
                    `label` varchar(255) DEFAULT NULL,
                    `valueType` enum('number','text','document','asset','object') DEFAULT NULL,
                    `value` varchar(255) DEFAULT NULL,
                    KEY `id` (`id`),
                    KEY `date` (`date`),
                    KEY `category` (`category`),
                    KEY `action` (`action`),
                    KEY `label` (`label`),
                    KEY `valueType` (`valueType`),
                    KEY `value` (`value`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

            $this->validColumns = $this->getValidTableColumns($this->getTableName());
        }
    }

    public function getTableName() {
        return "tracking_event_"  . date("Y_m", strtotime($this->model->getDate()));
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     * @return void
     */
    public function save () {
        try {
            $type = get_object_vars($this->model);

            $data = array();
            foreach ($type as $key => $value) {
                if (in_array($key, $this->validColumns)) {
                    $data[$key] = $value;
                }
            }

            $this->db->insert($this->getTableName(), $data);
        }
        catch (Exception $e) {
            throw $e;
        }
    }
}
