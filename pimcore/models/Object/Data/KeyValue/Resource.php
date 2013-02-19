<?php
class Object_Data_KeyValue_Resource extends Pimcore_Model_Resource_Abstract {

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
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        Logger::debug("save called");
        $this->delete();
        $db = $this->db;
        $model = $this->model;
        $objectId = $model->getObjectId();
        $properties = $model->getInternalProperties();
        foreach ($properties as $pair) {
            $key = $db->quote($pair["key"]);
            $value = $db->quote($pair["value"]);
            $sql = "INSERT INTO " . $this->getTableName() . " (`o_id`, `key`, `value`) VALUES (" . $objectId . "," . $key . "," . $value . ")";
            Logger::debug($sql);
            $db->query($sql);
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {

        $sql = $this->db->quoteInto("o_id = ?", $this->model->getObjectId());

        // $sql = "o_id = " . $this->model->getObjectId();
        Logger::debug("query= " . $sql);
        $this->db->delete($this->getTableName(), $sql);
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        // TODO implement
        Logger::debug("update called");


//        try {
//            $type = get_object_vars($this->model);
//
//            foreach ($type as $key => $value) {
//                if (in_array($key, $this->validColumns)) {
//                    if(is_bool($value)) {
//                        $value = (int) $value;
//                    }
//                    if(is_array($value) || is_object($value)) {
//                        $value = serialize($value);
//                    }
//
//                    $data[$key] = $value;
//                }
//            }
//
//            $this->db->update(SavedSearch_Plugin::TABLE_NAME, $data, $this->db->quoteInto("id = ?", $this->model->getId()));
//        }
//        catch (Exception $e) {
//            throw $e;
//        }
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
    }

    public function getTableName() {
        $model = $this->model;
        $class = $model->getClass();
        $classId = $class->getId();
        return "object_keyvalue_" . $classId;
    }

    public function createUpdateTable () {
        Logger::debug("createUpdateTable called");

        $model = $this->model;
        $class = $model->getClass();;
        $classId = $class->getId();
        $table = $this->getTableName();

        $db = Pimcore_Resource::get();
        $db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
    		`id` INT NOT NULL AUTO_INCREMENT,
    		`o_id` INT NOT NULL,
    		`key` INT NOT NULL,
    		`value` VARCHAR(255),
            `translated` LONGTEXT NULL,
    	    PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $validColumns = $this->getValidTableColumns($table, false); // no caching of table definition

        if (!in_array("translated", $validColumns)) {
            $db->query("ALTER TABLE `" . $table . "` ADD COLUMN `translated` LONGTEXT NULL AFTER `value`;");
        }

        Logger::debug("createUpdateTable done");
    }

    public function load() {
        $model = $this->model;
        Logger::debug("load called");

        $table = $this->getTableName();
        $db = Pimcore_Resource::get();
        $sql = "SELECT * FROM " . $table . " WHERE o_id = " . $model->getObjectId();
        $result = $db->fetchAll($sql);
        $model->setProperties($result);

        Logger::debug("result=" . $result);
    }
}
