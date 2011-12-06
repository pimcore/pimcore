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
 * @package    Property
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class EmailLog_Resource extends Pimcore_Model_Resource_Abstract {

    protected static $dbTable = 'email_log';
     /**
     * Contains the valid database colums
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid database columns from database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns(self::$dbTable);
    }


     /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM email_log WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
    }

     /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        $data = array();

        $emailLog = get_object_vars($this->model);
        foreach ($emailLog as $key => $value) {
            if (in_array($key, $this->validColumns)) {

                // check if the getter exists
                $getter = "get" . ucfirst($key);
                if(!method_exists($this->model,$getter)) {
                    continue;
                }

                // get the value from the getter
                $value = $this->model->$getter();

                if (is_bool($value)) {
                    $value = (int) $value;
                }else if(is_array($value)){
                    $value = Zend_Json::encode($value);
                }

                $data[$key] = $value;
            }
        }

        try {
            $this->db->update(self::$dbTable, $data,  $this->db->quoteInto("id = ?", $this->model->getId()));
        }
        catch (Exception $e) {
            Logger::emerg('Could not Save emailLog with the id "'.$this->model->getId().'" ');
        }
    }


    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(self::$dbTable, $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    //just an alias
    public function update(){
        $this->save();
    }

     /**
     * Create a new record for the object in the database
     *
     * @return void
     */
    public function create() {
        try {
            $this->db->insert(self::$dbTable, array());

            $date = time();
            $this->model->setId($this->db->lastInsertId());
            $this->model->setCreationDate($date);
            $this->model->setModificationDate($date);

        }
        catch (Exception $e) {
            throw $e;
        }

    }


}
