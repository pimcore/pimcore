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

class Object_List_Concrete_Resource_Mysql extends Object_List_Resource_Mysql {

    protected $firstException = true;

    /**
     * Loads a list of objects for the specicifies parameters, returns an array of Object_Abstract elements
     *
     * @return array
     */
    public function load() {
        
        $objects = array();

        try {
            $objectsData = $this->db->fetchAll("SELECT o_id,o_type FROM " . $this->getTableName() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit());
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }

        foreach ($objectsData as $objectData) {
            $objects[] = Object_Abstract::getById($objectData["o_id"]);
        }

        $this->model->setObjects($objects);
        return $objects;
    }

    public function getTotalCount() {

        try {
            $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM " . $this->getTableName() . $this->getCondition() . $this->getGroupBy());
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }

        return $amount["amount"];
    }

    public function getCount() {
        if (count($this->model->getObjects()) > 0) {
            return count($this->model->getObjects());
        }

        try {
            $amount = $this->db->fetchAll("SELECT COUNT(*) as amount FROM " . $this->getTableName() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit());
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }
        
        return $amount["amount"];
    }

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

    protected function getTableName () {

        // check for a localized fields
        if(property_exists("Object_" . ucfirst($this->model->getClassName()), "localizedfields")) {
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

            return "object_localized_" . $this->model->getClassId() . "_" . $language;
        }

        return "object_" . $this->model->getClassId();
    }
}
