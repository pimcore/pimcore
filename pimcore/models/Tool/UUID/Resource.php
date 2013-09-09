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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_UUID_Resource extends Pimcore_Model_Resource_Abstract {

    const TABLE_NAME = 'uuids';
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
        $this->validColumns = $this->getValidTableColumns(static::TABLE_NAME);
    }

    public function save () {
        $data = get_object_vars($this->model);

        foreach($data as $key => $value){
            if(!in_array($key,$this->validColumns)){
                unset($data[$key]);
            }
        }

        $this->db->insertOrUpdate(self::TABLE_NAME,$data);
    }

    public function delete(){
        $uuid = $this->model->getUuid();
        if(!$uuid){
            throw new Exception("Couldn't delete UUID - no UUID specified.");
        }
        $this->db->delete(self::TABLE_NAME,"uuid='". $uuid ."'");
    }

    public function getByUuid($uuid){
        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME ." where uuid='" . $uuid . "'");
        $model = new Tool_UUID();
        $model->setValues($data);
        return $model;
    }
}
