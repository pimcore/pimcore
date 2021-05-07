<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Translation;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Translation $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @var string
     */
    const TABLE_PREFIX = 'translations_';

    /**
     * @return string
     */
    protected function getDatabaseTableName(): string
    {
        return self::TABLE_PREFIX . $this->model->getDomain();
    }

    /**
     * @param string $key
     *
     * @throws \Exception
     */
    public function getByKey($key)
    {
        $caseInsensitive = \Pimcore::getContainer()->getParameter('pimcore.config')['translations']['case_insensitive'];

        $condition = '`key` = ?';
        if ($caseInsensitive) {
            $condition = 'LOWER(`key`) = LOWER(?)';
        }
        $data = $this->db->fetchAll('SELECT * FROM ' . $this->getDatabaseTableName() . ' WHERE ' . $condition . ' ORDER BY `creationDate` ', [$key]);

        if (!empty($data)) {
            foreach ($data as $d) {
                $this->model->addTranslation($d['language'], $d['text']);
                $this->model->setKey($d['key']);
                $this->model->setCreationDate($d['creationDate']);
                $this->model->setModificationDate($d['modificationDate']);
            }
        } else {
            throw new \Exception("Translation-Key -->'" . $key . "'<-- not found");
        }
    }

    /**
     * Save object to database
     */
    public function save()
    {
        //Create Domain table if doesn't exist
        $this->createOrUpdateTable();

        if ($this->model->getKey() !== '') {
            foreach ($this->model->getTranslations() as $language => $text) {
                $data = [
                    'key' => $this->model->getKey(),
                    'language' => $language,
                    'text' => $text,
                    'modificationDate' => $this->model->getModificationDate(),
                    'creationDate' => $this->model->getCreationDate(),
                ];
                $this->db->insertOrUpdate($this->getDatabaseTableName(), $data);
            }
        }
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete($this->getDatabaseTableName(), [$this->db->quoteIdentifier('key') => $this->model->getKey()]);
    }

    /**
     * Returns a array containing all available languages
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        $l = $this->db->fetchAll('SELECT * FROM ' . $this->getDatabaseTableName()  . '  GROUP BY `language`;');
        $languages = [];

        foreach ($l as $values) {
            $languages[] = $values['language'];
        }

        return $languages;
    }

    public function createOrUpdateTable()
    {
        $table = $this->getDatabaseTableName();

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $table . "` (
                          `key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
                          `language` varchar(10) NOT NULL DEFAULT '',
                          `text` text DEFAULT NULL,
                          `creationDate` int(11) unsigned DEFAULT NULL,
                          `modificationDate` int(11) unsigned DEFAULT NULL,
                          PRIMARY KEY (`key`,`language`),
                          KEY `language` (`language`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}
