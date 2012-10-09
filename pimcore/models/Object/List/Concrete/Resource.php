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

class Object_List_Concrete_Resource extends Object_List_Resource {

    /**
     * @var bool
     */
    protected $firstException = true;

     /**
     * @var string
     */
    private $tableName = null;

    /**
     * Loads a list of objects for the specicifies parameters, returns an array of Object_Abstract elements
     *
     * @return array 
     */
    public function load() {
        
        $objects = array();

        try {
            $field = $this->getTableName() . ".o_id";
            $objectsData = $this->db->fetchAll("SELECT " . $this->getSelectPart($field,$field) . " AS o_id,o_type FROM `" . $this->getTableName() . "`" . $this->getJoins() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }

        foreach ($objectsData as $objectData) {
            if($object = Object_Abstract::getById($objectData["o_id"])) {
                $objects[] = Object_Abstract::getById($objectData["o_id"]);
            }
        }

        $this->model->setObjects($objects);
        return $objects;
    }

    /**
     * Loads a list of object ids for the specicifies parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList() {
        try {
            $field = $this->getTableName() . ".o_id";
            $objectsData = $this->db->fetchCol("SELECT " . $this->getSelectPart($field,$field) . " AS o_id FROM `" . $this->getTableName() . "`" . $this->getJoins() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }

        return $objectsData;
    }

    /**
     * @return array
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(" . $this->getSelectPart("*") . ") as amount FROM `" . $this->getTableName() . "`" . $this->getJoins()  . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables());
            return $amount;
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }
    }

    /**
     * @return array|int
     */
    public function getCount() {
        if (count($this->model->getObjects()) > 0) {
            return count($this->model->getObjects());
        }

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(" . $this->getSelectPart("*") . ") as amount FROM `" . $this->getTableName() . "`" . $this->getJoins()  . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
            return $amount;
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }
    }

    /**
     * @param $e
     * @return array
     */
    protected function exceptionHandler ($e) {

        // create view if it doesn't exist already // HACK
        $pdoMySQL = preg_match("/Base table or view not found/",$e->getMessage());
        $Mysqli = preg_match("/Table (.*) doesn't exist/",$e->getMessage());

        if(($Mysqli || $pdoMySQL) && $this->firstException) {
            $this->firstException = false;

            $localizedFields = new Object_Localizedfield();
            $localizedFields->setClass(Object_Class::getById($this->model->getClassId()));
            $localizedFields->createUpdateTable();

            return $this->load();
        }

        throw $e;
    }

    /**
     * @return string
     */
    protected function getTableName () {
 
        if(empty($this->tableName)) {

            // default
            $this->tableName = "object_" . $this->model->getClassId();

            if(!$this->model->getIgnoreLocalizedFields()) {

                // check for a localized field and if they should be used for this list
                if(property_exists("Object_" . ucfirst($this->model->getClassName()), "localizedfields")) {

                    $language = "default";

                    if(!$this->model->getIgnoreLocale()) {
                        if($this->model->getLocale()) {
                            if(Pimcore_Tool::isValidLanguage((string) $this->model->getLocale())) {
                                $language = (string) $this->model->getLocale();
                            }
                        }

                        if(Zend_Registry::isRegistered("Zend_Locale") && $language == "default") {
                            $locale = Zend_Registry::get("Zend_Locale");
                            if(Pimcore_Tool::isValidLanguage((string) $locale)) {
                                $language = (string) $locale;
                            }
                        }
                    }
                    $this->tableName = "object_localized_" . $this->model->getClassId() . "_" . $language;
                }
            }
        }
        return $this->tableName;
    }

    /**
     * @return string
     */
    protected function getJoins() {
        $join = ""; 

        $fieldCollections = $this->model->getFieldCollections();
        if(!empty($fieldCollections)) {
            foreach($fieldCollections as $fc) {
                $join .= " LEFT JOIN object_collection_" . $fc['type'] . "_" . $this->model->getClassId();

                $name = $fc['type'];
                if(!empty($fc['fieldname'])) {
                    $name .= "~" . $fc['fieldname'];
                }


                $join .= " `" . $name . "`";
                $join .= " ON `" . $name . "`.o_id = `" . $this->getTableName() . "`.o_id";
            }
        }

        $objectbricks = $this->model->getObjectbricks();
        if(!empty($objectbricks)) {
            foreach($objectbricks as $ob) {
                $join .= " LEFT JOIN object_brick_query_" . $ob . "_" . $this->model->getClassId();

                $name = $ob;

                $join .= " `" . $name . "`";
                $join .= " ON `" . $name . "`.o_id = `" . $this->getTableName() . "`.o_id";
            }
        }

        return $join;
    }

    protected function getSelectPart($defaultString = "", $column = "oo_id") {
        $selectPart = $defaultString;
        $fieldCollections = $this->model->getFieldCollections();
        if(!empty($fieldCollections)) {
            $selectPart = "DISTINCT " . $column;
        }
        return $selectPart;
    }

    /**
     * @return string
     */
    protected function getCondition() {
        $condition = parent::getCondition();

        $fieldCollections = $this->model->getFieldCollections();
        if(!empty($fieldCollections)) {
            foreach($fieldCollections as $fc) {
                if(!empty($fc['fieldname'])) {
                    $name = $fc['type'];
                    if(!empty($fc['fieldname'])) {
                        $name .= "~" . $fc['fieldname'];
                    }


                    if(!empty($condition)) {
                        $condition .= " AND ";
                    }
                    $condition .= "`" . $name . "`.fieldname = '" . $fc['fieldname'] . "'";


                }
            }

        }
        return $condition;
    }
}
