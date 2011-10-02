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
            $objectsData = $this->db->fetchAll("SELECT " . $this->getTableName() . ".o_id AS o_id,o_type FROM `" . $this->getTableName() . "`" . $this->getJoins() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }

        $tmpIds = array();
        foreach ($objectsData as $objectData) {
            if(!$tmpIds[$objectData["o_id"]]) {
                $objects[] = Object_Abstract::getById($objectData["o_id"]);
                $tmpIds[$objectData["o_id"]] = $objectData["o_id"];
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
            $objectsData = $this->db->fetchCol("SELECT " . $this->getTableName() . ".o_id AS o_id FROM `" . $this->getTableName() . "`" . $this->getJoins() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
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
            // do not use DISTINCT in query because this forces MySQL to create a temp-table, in this case it's better to do the job with PHP wich is faster
            $amount = $this->db->fetchAll("SELECT " . $this->getTableName() . ".o_id FROM `" . $this->getTableName() . "`" . $this->getJoins()  . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables());

            $tmpIds = array();
            foreach ($amount as $a) {
                $tmpIds[$a["o_id"]] = $a["o_id"];
            }

            return count($tmpIds);

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
            // do not use DISTINCT in query because this forces MySQL to create a temp-table, in this case it's better to do the job with PHP wich is faster
            $amount = $this->db->fetchAll("SELECT " . $this->getTableName() . ".o_id FROM `" . $this->getTableName() . "`" . $this->getJoins()  . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

            $tmpIds = array();
            foreach ($amount as $a) {
                $tmpIds[$a["o_id"]] = $a["o_id"];
            }

            return count($tmpIds);

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
        if(preg_match("/Base table or view not found/",$e->getMessage()) && $this->firstException) {
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

            // check for a localized field and if they should be used for this list
            if(property_exists("Object_" . ucfirst($this->model->getClassName()), "localizedfields") && !$this->model->getIgnoreLocalizedFields()) {
                $language = "default";

                if(!$this->model->getIgnoreLocale()) {
                    if($this->model->getLocale()) {
                        if(Pimcore_Tool::isValidLanguage((string) $this->model->getLocale())) {
                            $language = (string) $this->model->getLocale();
                        }
                    }

                    try {
                        $locale = Zend_Registry::get("Zend_Locale");
                        if(Pimcore_Tool::isValidLanguage((string) $locale) && $language == "default") {
                            $language = (string) $locale;
                        }
                    } catch (Exception $e) {}
                }

                $this->tableName = "object_localized_" . $this->model->getClassId() . "_" . $language;
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
