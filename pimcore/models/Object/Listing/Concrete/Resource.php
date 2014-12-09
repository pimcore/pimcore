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

namespace Pimcore\Model\Object\Listing\Concrete;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;

class Resource extends Model\Object\Listing\Resource {

    /**
     * @var bool
     */
    protected $firstException = true;

     /**
     * @var string
     */
    private $tableName = null;

    /**
     * Loads a list of objects for the specified parameters, returns an array of Object\AbstractObject elements
     *
     * @return array 
     */
    public function load() {
        
        $objects = array();

        try {
            $field = $this->getTableName() . ".o_id";
            $sql = "SELECT " . $this->getSelectPart($field,$field) . " AS o_id,o_type FROM `" . $this->getTableName() . "`" . $this->getJoins() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit();
            $objectsData = $this->db->fetchAll($sql, $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return $this->exceptionHandler($e);
        }

        foreach ($objectsData as $objectData) {
            if($object = Object::getById($objectData["o_id"])) {
                $objects[] = Object::getById($objectData["o_id"]);
            }
        }

        $this->model->setObjects($objects);
        return $objects;
    }

    /**
     * Loads a list of object ids for the specified parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList() {
        try {
            $field = $this->getTableName() . ".o_id";
            $objectsData = $this->db->fetchCol("SELECT " . $this->getSelectPart($field,$field) . " AS o_id FROM `" . $this->getTableName() . "`" . $this->getJoins() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return $this->exceptionHandler($e);
        }
    }

    /**
     * @param $e
     * @return array
     * @throws
     * @throws \Exception
     */
    protected function exceptionHandler ($e) {

        // create view if it doesn't exist already // HACK
        $pdoMySQL = preg_match("/Base table or view not found/",$e->getMessage());
        $Mysqli = preg_match("/Table (.*) doesn't exist/",$e->getMessage());

        if(($Mysqli || $pdoMySQL) && $this->firstException) {
            $this->firstException = false;

            $localizedFields = new Object\Localizedfield();
            $localizedFields->setClass(Object\ClassDefinition::getById($this->model->getClassId()));
            $localizedFields->createUpdateTable();

            return $this->load();
        }

        throw $e;
    }

    /**
     * @return string
     * @throws \Exception
     * @throws \Zend_Exception
     */
    protected function getTableName () {
 
        if(empty($this->tableName)) {

            // default
            $this->tableName = "object_" . $this->model->getClassId();

            if(!$this->model->getIgnoreLocalizedFields()) {

                $language = null;
                // check for a localized field and if they should be used for this list
                if(property_exists("\\Pimcore\\Model\\Object\\" . ucfirst($this->model->getClassName()), "localizedfields")) {
                    if($this->model->getLocale()) {
                        if(Tool::isValidLanguage((string) $this->model->getLocale())) {
                            $language = (string) $this->model->getLocale();
                        }
                    }

                    if(!$language && \Zend_Registry::isRegistered("Zend_Locale")) {
                        $locale = \Zend_Registry::get("Zend_Locale");
                        if(Tool::isValidLanguage((string) $locale)) {
                            $language = (string) $locale;
                        }
                    }

                    if (!$language) {
                        $language = Tool::getDefaultLanguage();
                    }

                    if (!$language) {
                        throw new \Exception("No valid language/locale set. Use \$list->setLocale() to add a language to the listing, or register a global locale");
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

                $join .= " " . $this->db->quoteIdentifier($name);
                $join .= " ON (" . $this->db->quoteIdentifier($name) . ".o_id = " . $this->db->quoteIdentifier($this->getTableName()) . ".o_id";
                if(!empty($fc['fieldname'])) {
                    $join .= " AND " . $this->db->quoteIdentifier($name) . ".fieldname = '" . $fc['fieldname'] . "'";
                }
                $join .= ")";
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
     * @param string $defaultString
     * @param string $column
     * @return string
     */
    protected function getSelectPart($defaultString = "", $column = "oo_id") {
        $selectPart = $defaultString;
        $fieldCollections = $this->model->getFieldCollections();
        if(!empty($fieldCollections)) {
            $selectPart = "DISTINCT " . $column;
        }
        return $selectPart;
    }
}
