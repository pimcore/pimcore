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

abstract class Translation_Abstract_Resource extends Pimcore_Model_Resource_Abstract implements Translation_Abstract_Resource_Interface {

    /**
     * Get the data for the object from database for the given key
     *
     * @param integer $key
     * @return void
     */
    public function getByKey($key) {
        $data = $this->db->fetchAll("SELECT * FROM " . static::getTableName() . " WHERE `key` = ? ORDER BY `date`", $key);

        if (!empty($data)) {
            foreach ($data as $d) {
                $date = $d["date"];
                $this->model->addTranslation($d["language"], $d["text"]);
            }
            $this->model->setKey($key);
            $this->model->setDate($date);
        }
        else {
            throw new Exception("Translation-Key -->'" . $key . "'<-- not found");
        }
    }


    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        if ($this->model->getKey()) {

            foreach ($this->model->getTranslations() as $language => $text) {

                $data = array(
                    "key" => $this->model->getKey(),
                    "language" => $language,
                    "text" => $text,
                    "date" => time()
                );

                try {
                    $this->db->insert(static::getTableName() , $data);
                } catch (Exception $e) {
                    $this->db->update(static::getTableName() , $data, $this->db->quoteInto("`key` = ?", $this->model->getKey()) . " AND " . $this->db->quoteInto("language = ?", $language));
                }
            }
        }

        $this->model->clearDependedCache();
    }


    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(static::getTableName() , $this->db->quoteInto("`key`= ?", $this->model->getKey()));

        $this->model->clearDependedCache();
    }

    /**
     * Returns a array containing all available languages
     *
     * @return void
     */
    public function getAvailableLanguages() {
        $l = $this->db->fetchAll("SELECT * FROM " . static::getTableName()  . "  GROUP BY `language`;");

        foreach ($l as $values) {
            $languages[] = $values["language"];
        }

        return $languages;
    }
}
