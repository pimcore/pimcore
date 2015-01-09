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
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Data\ObjectMetadata;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Resource\AbstractResource {

    /**
     * @param Object\Concrete $object
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @throws \Zend_Db_Adapter_Exception
     */
    public function save(Object\Concrete $object, $ownertype, $ownername, $position) {
        $table = $this->getTablename($object);

        $dataTemplate = array("o_id" => $object->getId(),
            "dest_id" => $this->model->getObject()->getId(),
            "fieldname" => $this->model->getFieldname(),
            "ownertype" => $ownertype,
            "ownername" => $ownername ? $ownername : "",
            "position" => $position ?  $position : "0");

        foreach($this->model->getColumns() as $column) {
            $getter = "get" . ucfirst($column);
            $data = $dataTemplate;
            $data["column"] = $column;
            $data["data"] = $this->model->$getter();
            $this->db->insert($table, $data);
        }

    }

    /**
     * @param $object
     * @return string
     */
    private function getTablename($object) {
        return "object_metadata_" . $object->getClassId();
    }

    /**
     * @param Object\Concrete $source
     * @param $destination
     * @param $fieldname
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @return null|Model\Resource\Pimcore_Model_Abstract
     */
    public function load(Object\Concrete $source, $destination, $fieldname, $ownertype, $ownername, $position) {
        $dataRaw = $this->db->fetchAll("SELECT * FROM " . $this->getTablename($source) . " WHERE o_id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ?", array($source->getId(), $destination->getId(), $fieldname, $ownertype, $ownername, $position));
        if(!empty($dataRaw)) {
            $this->model->setObject($destination);
            $this->model->setFieldname($fieldname);
            $columns = $this->model->getColumns();
            foreach($dataRaw as $row) {
                if(in_array($row['column'], $columns)) {
                    $setter = "set" . ucfirst($row['column']);
                    $this->model->$setter($row['data']);
                }
            }

            return $this->model;
        } else {
            return null;
        }
    }

    /**
     * @return void
     */
    public function createOrUpdateTable($class) {

        $classId = $class->getId();
        $table = "object_metadata_" . $classId;

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
              `o_id` int(11) NOT NULL default '0',
              `dest_id` int(11) NOT NULL default '0',
              `fieldname` varchar(71) NOT NULL,
              `column` varchar(255) NOT NULL,
              `data` text,
              `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
              `ownername` VARCHAR(70) NOT NULL DEFAULT '',
              `position` VARCHAR(70) NOT NULL DEFAULT '0',
              PRIMARY KEY (`o_id`, `dest_id`, `fieldname`, `column`, `ownertype`, `ownername`, `position`),
              INDEX `o_id` (`o_id`),
              INDEX `dest_id` (`dest_id`),
              INDEX `fieldname` (`fieldname`),
              INDEX `column` (`column`),
              INDEX `ownertype` (`ownertype`),
              INDEX `ownername` (`ownername`),
              INDEX `position` (`position`)
		) DEFAULT CHARSET=utf8;");

    }
}
