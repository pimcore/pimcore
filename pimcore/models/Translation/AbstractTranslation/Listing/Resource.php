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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Translation\AbstractTranslation\Listing;

use Pimcore\Model;
use Pimcore\Model\Cache; 

abstract class Resource extends Model\Listing\Resource\AbstractResource implements Resource\ResourceInterface {

    /**
     * @return int
     */
    public function getTotalCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM (SELECT `key` FROM " . static::getTableName() . $this->getCondition() . $this->getGroupBy() . ") AS a", $this->model->getConditionVariables());
        return $amount;
    }

    /**
     * @return int
     */
    public function getCount() {
        if (count($this->model->getObjects()) > 0) {
            return count($this->model->getObjects());
        }

        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM (SELECT `key` FROM " . static::getTableName() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit() . ") AS a", $this->model->getConditionVariables());
        return $amount;
    }

    /**
     * @return array|mixed
     */
    public function getAllTranslations() {

        $cacheKey = static::getTableName()."_data";
        if(!$translations = Cache::load($cacheKey)) {
            $itemClass = static::getItemClass();
            $translations = array();
            $translationsData = $this->db->fetchAll("SELECT * FROM " . static::getTableName());

            foreach ($translationsData as $t) {
                if(!$translations[$t["key"]]) {
                    $translations[$t["key"]] = new $itemClass();
                    $translations[$t["key"]]->setKey($t["key"]);
                }

                $translations[$t["key"]]->addTranslation($t["language"],$t["text"]);

                //for legacy support
                if($translations[$t["key"]]->getDate() < $t["creationDate"]){
                    $translations[$t["key"]]->setDate($t["creationDate"]);
                }

                $translations[$t["key"]]->setCreationDate($t["creationDate"]);
                $translations[$t["key"]]->setModificationDate($t["modificationDate"]);
            }

            Cache::save($translations, $cacheKey, array("translator","translate"), 999);
        }

        
        return $translations;
    }

    /**
     * @return array
     */
    public function loadRaw() {
        $translationsData = $this->db->fetchAll("SELECT * FROM " . static::getTableName() . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $translationsData;
    }

    /**
     * @return array
     */
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

    /**
     * @return bool
     */
    public function isCacheable() {
        $count = $this->db->fetchOne("SELECT COUNT(*) FROM " . static::getTableName());
        if($count > 5000) {
            return false;
        }
        return true;
    }

    /**
     *
     */
    public function cleanup() {
        $keysToDelete = $this->db->fetchCol("SELECT `key` FROM " . static::getTableName() . " as tbl1 WHERE
               (SELECT count(*) FROM " . static::getTableName() . " WHERE `key` = tbl1.`key` AND (`text` IS NULL OR `text` = ''))
               = (SELECT count(*) FROM " . static::getTableName() . " WHERE `key` = tbl1.`key`) GROUP BY `key`;");

        if(is_array($keysToDelete) && !empty($keysToDelete)) {
            $preparedKeys = array();
            foreach ($keysToDelete as $value) {
                if(strpos($value, ":") === false) { // colon causes problems due to a ZF bug, so we exclude them
                    $preparedKeys[] = $this->db->quote($value);
                }
            }

            if(!empty($preparedKeys)) {
                $this->db->delete(static::getTableName(), "`key` IN (" . implode(",", $preparedKeys) . ")");
            }
        }
    }
}
