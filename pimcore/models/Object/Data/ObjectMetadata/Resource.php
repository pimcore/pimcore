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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Data_ObjectMetadata_Resource extends Pimcore_Model_Resource_Abstract {


    public function save(Object_Concrete $object) {
        $table = $this->getTablename($object);

        $dataTemplate = array("o_id" => $object->getId(), "dest_id" => $this->model->getObject()->getId(), "fieldname" => $this->model->getFieldname());

        foreach($this->model->getColumns() as $column) {
            $getter = "get" . ucfirst($column);
            $data = $dataTemplate;
            $data["column"] = $column;
            $data["data"] = $this->model->$getter();
            $this->db->insert($table, $data);
        }

    }


    private function getTablename($object) {
        return "object_metadata_" . $object->getClassId();
    }


    public function load(Object_Concrete $source, $destination, $fieldname) {
        $dataRaw = $this->db->fetchAll("SELECT * FROM " . $this->getTablename($source) . " WHERE o_id = ? AND dest_id = ? AND fieldname = ?", array($source->getId(), $destination->getId(), $fieldname));
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
		  PRIMARY KEY (`o_id`,`dest_id`, `fieldname`,`column`),
          INDEX `o_id` (`o_id`),
          INDEX `dest_id` (`dest_id`),
          INDEX `fieldname` (`fieldname`),
          INDEX `column` (`column`)
		) DEFAULT CHARSET=utf8;");

    }
}
