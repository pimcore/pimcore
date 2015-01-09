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

namespace Pimcore\Model\Tool\UUID;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

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

    /**
     *
     */
    public function save () {
        $data = get_object_vars($this->model);

        foreach($data as $key => $value){
            if(!in_array($key,$this->validColumns)){
                unset($data[$key]);
            }
        }

        $this->db->insertOrUpdate(self::TABLE_NAME,$data);
    }

    /**
     * @throws \Exception
     */
    public function delete(){
        $uuid = $this->model->getUuid();
        if(!$uuid){
            throw new \Exception("Couldn't delete UUID - no UUID specified.");
        }
        $this->db->delete(self::TABLE_NAME,"uuid='". $uuid ."'");
    }

    /**
     * @param $uuid
     * @return Tool\UUID
     */
    public function getByUuid($uuid){
        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME ." where uuid='" . $uuid . "'");
        $model = new Model\Tool\UUID();
        $model->setValues($data);
        return $model;
    }
}
