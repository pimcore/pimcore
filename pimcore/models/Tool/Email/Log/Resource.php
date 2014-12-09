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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Email\Log;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     * Name of the db table
     * @var string
     */
    protected static $dbTable = 'email_log';

     /**
     * Contains the valid database columns
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
     * Save document to database
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
                    //converts the dynamic params to a basic json string
                    $preparedData = self::createJsonLoggingObject($value);
                    $value = \Zend_Json::encode($preparedData);
                }

                $data[$key] = $value;
            }
        }

        try {
            $this->db->update(self::$dbTable, $data,  $this->db->quoteInto("id = ?", $this->model->getId()));
        }
        catch (\Exception $e) {
            \Logger::emerg('Could not Save emailLog with the id "'.$this->model->getId().'" ');
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

    /**
     * just an alias for $this->save();
     */
    public function update(){
        $this->save();
    }

    /**
     * @throws \Exception
     */
    public function create() {
        try {
            $this->db->insert(self::$dbTable, array());

            $date = time();
            $this->model->setId($this->db->lastInsertId());
            $this->model->setCreationDate($date);
            $this->model->setModificationDate($date);

        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $data
     * @return array|string
     */
    protected function createJsonLoggingObject($data){
        if(!is_array($data)){
            return \Zend_Json::encode(new \stdClass());
        }else{
           $loggingData = array();
           foreach($data as $key => $value){
              $loggingData[] = self::prepareLoggingData($key,$value);
            }
           return $loggingData;
        }
    }

    /**
     * Creates the basic logging for the treeGrid in the backend
     * Data will be enhanced with live-data in the backend
     *
     * @param $key
     * @param $value
     * @return \stdClass
     */
    protected function prepareLoggingData($key,$value){
        $class = new \stdClass();
        $class->key = $key.' '; //dirty hack - key has to be a string otherwise the treeGrid won't work

        if(is_string($value) || is_int($value) || is_null($value)){
            $class->data = array('type' => 'simple',
                'value' => $value);
        }elseif($value instanceof \Zend_Date){
            $class->data = array('type' => 'simple',
                'value' => $value->get(\Zend_Date::DATETIME));
        }elseif(is_object($value) && method_exists($value,'getId')){
            $class->data = array('type' => 'object',
                'objectId' => $value->getId(),
                'objectClass' => get_class($value));
        }elseif(is_array($value)){
            foreach($value as $entryKey => $entryValue){
                $class->children[] = self::prepareLoggingData($entryKey,$entryValue);
            }
        }
        return $class;
    }
}
