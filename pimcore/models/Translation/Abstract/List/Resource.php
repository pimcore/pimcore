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
 * @package    Translation
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Translation_Abstract_List_Resource extends Pimcore_Model_List_Resource_Abstract implements Translation_Abstract_List_Resource_Interface {

    public function getTotalCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM (SELECT `key` FROM " . static::getTableName() . $this->getCondition() . $this->getGroupBy() . ") AS a", $this->model->getConditionVariables());
        return $amount;
    }

    public function getCount() {
        if (count($this->model->getObjects()) > 0) {
            return count($this->model->getObjects());
        }

        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM (SELECT `key` FROM " . static::getTableName() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit() . ") AS a", $this->model->getConditionVariables());
        return $amount;
    }

    public function getAllTranslations() {

        $cacheKey = static::getTableName()."_data";
        if(!$translations = Pimcore_Model_Cache::load($cacheKey)) {
            $itemClass = static::getItemClass();
            $translations = array();
            $translationsData = $this->db->fetchAll("SELECT * FROM " . static::getTableName());

            foreach ($translationsData as $t) {
                if(!$translations[$t["key"]]) {
                    $translations[$t["key"]] = new $itemClass();
                    $translations[$t["key"]]->setKey($t["key"]);
                }

                $translations[$t["key"]]->addTranslation($t["language"],$t["text"]);

                if($translations[$t["key"]]->getDate() < $t["date"]){
                    $translations[$t["key"]]->setDate($t["date"]);
                }
            }

            Pimcore_Model_Cache::save($translations, $cacheKey, array("translator","translate"), 999);
        }

        
        return $translations;
    }

    public function load () {

        $allTranslations = $this->getAllTranslations();
        $translations = array();
        $this->model->setGroupBy("key");
        $translationsData = $this->db->fetchAll("SELECT `key` FROM " . static::getTableName() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($translationsData as $t) {
            $translations[] = $allTranslations[$t["key"]];
        }

        $this->model->setTranslations($translations);
        return $translations;
    }

    public function cleanup() {
        $keysToDelete = $this->db->fetchCol("SELECT `key` FROM " . static::getTableName() . " as tbl1 WHERE
               (SELECT count(*) FROM " . static::getTableName() . " WHERE `key` = tbl1.`key` AND (`text` IS NULL OR `text` = ''))
               = (SELECT count(*) FROM " . static::getTableName() . " WHERE `key` = tbl1.`key`) GROUP BY `key`;");

        if(is_array($keysToDelete) && !empty($keysToDelete)) {
            $preparedKeys = array();
            foreach ($keysToDelete as $value) {
                $preparedKeys[] = $this->db->quote($value);
            }

            $this->db->delete(static::getTableName(), "`key` IN (" . implode(",", $preparedKeys) . ")");
        }
    }
}
