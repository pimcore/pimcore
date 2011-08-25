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
 * @package    Object_Class
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

        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . $table . "` (
		  `o_id` int(11) NOT NULL default '0',
		  `dest_id` int(11) NOT NULL default '0',
		  `fieldname` varchar(255) NOT NULL,
		  `column` varchar(255) NOT NULL,
		  `data` text,
		  PRIMARY KEY (`o_id`,`dest_id`, `fieldname`,`column`),
          INDEX `o_id` (`o_id`),
          INDEX `dest_id` (`dest_id`),
          INDEX `fieldname` (`fieldname`),
          INDEX `column` (`column`)
		) DEFAULT CHARSET=utf8;", $classId);

    }

    private function dbexec($sql, $classId) {
        $db = Pimcore_Resource::get();
        $db->query($sql);
        $this->logSql($sql, $classId);
    }

    private function logSql ($sql, $classId) {
        $this->_sqlChangeLog[] = $sql;
        $this->classId = $classId;
    }

    public function __destruct () {

        // write sql change log for deploying to production system
        if(!empty($this->_sqlChangeLog)) {
            $log = implode("\n\n\n", $this->_sqlChangeLog);

            $filename = "db-change-log_".time()."_class-".$this->classId.".sql";
            $file = PIMCORE_SYSTEM_TEMP_DIRECTORY."/".$filename;
            if(defined("PIMCORE_DB_CHANGELOG_DIRECTORY")) {
                $file = PIMCORE_DB_CHANGELOG_DIRECTORY."/".$filename;
            }

            file_put_contents($file, $log);
            chmod($file, 0766);
        }
    }

}
