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

namespace Pimcore\Model\Translation\AbstractTranslation;

use Pimcore\Model;

abstract class Resource extends Model\Resource\AbstractResource implements Resource\ResourceInterface {

    /**
     * @param $key
     * @throws \Exception
     */
    public function getByKey($key) {
        $data = $this->db->fetchAll("SELECT * FROM " . static::getTableName() . " WHERE `key` = ? ORDER BY `creationDate` ", $key);
        if (!empty($data)) {
            foreach ($data as $d) {
                $this->model->addTranslation($d["language"], $d["text"]);
            }
            $this->model->setKey($d['key']);
            $this->model->setCreationDate($d['creationDate']);
            $this->model->setModificationDate($d['modificationDate']);
        }
        else {
            throw new \Exception("Translation-Key -->'" . $key . "'<-- not found");
        }
    }


    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        if ($this->model->getKey() !== '') {

            foreach ($this->model->getTranslations() as $language => $text) {

                $data = array(
                    "key" => $this->model->getKey(),
                    "language" => $language,
                    "text" => $text,
                    "modificationDate" => $this->model->getModificationDate(),
                    "creationDate" => $this->model->getCreationDate()
                );
                $this->db->insertOrUpdate(static::getTableName() , $data);
            }
        }

        $this->model->clearDependentCache();
    }


    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(static::getTableName() , $this->db->quoteInto("`key`= ?", $this->model->getKey()));

        $this->model->clearDependentCache();
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
