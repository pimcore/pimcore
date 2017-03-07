<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Translation
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation\AbstractTranslation;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Translation\AbstractTranslation $model
 */
abstract class Dao extends Model\Dao\AbstractDao implements Dao\DaoInterface
{

    /**
     * @param $key
     * @throws \Exception
     */
    public function getByKey($key)
    {
        $data = $this->db->fetchAll("SELECT * FROM " . static::getTableName() . " WHERE `key` = ? ORDER BY `creationDate` ", [$key]);
        if (!empty($data)) {
            foreach ($data as $d) {
                $this->model->addTranslation($d["language"], $d["text"]);
            }
            $this->model->setKey($d['key']);
            $this->model->setCreationDate($d['creationDate']);
            $this->model->setModificationDate($d['modificationDate']);
        } else {
            throw new \Exception("Translation-Key -->'" . $key . "'<-- not found");
        }
    }


    /**
     * Save object to database
     */
    public function save()
    {
        if ($this->model->getKey() !== '') {
            foreach ($this->model->getTranslations() as $language => $text) {
                $data = [
                    "key" => $this->model->getKey(),
                    "language" => $language,
                    "text" => $text,
                    "modificationDate" => $this->model->getModificationDate(),
                    "creationDate" => $this->model->getCreationDate()
                ];
                $this->db->insertOrUpdate(static::getTableName(), $data);
            }
        }

        $this->model->clearDependentCache();
    }


    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete(static::getTableName(), ["key" => $this->model->getKey()]);

        $this->model->clearDependentCache();
    }

    /**
     * Returns a array containing all available languages
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        $l = $this->db->fetchAll("SELECT * FROM " . static::getTableName()  . "  GROUP BY `language`;");

        foreach ($l as $values) {
            $languages[] = $values["language"];
        }

        return $languages;
    }
}
