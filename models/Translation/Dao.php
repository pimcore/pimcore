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

use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\User;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * @internal
 *
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
    public function getDatabaseTableName(): string
    {
        return self::TABLE_PREFIX . $this->model->getDomain();
    }

    /**
     * @param string $key
     * @param array|null $languages
     *
     * @throws NotFoundResourceException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getByKey($key, $languages = null)
    {
        if (is_array($languages)) {
            $sql = 'SELECT * FROM ' . $this->getDatabaseTableName() . ' WHERE `key` = :key
            AND `language` IN (:languages) ORDER BY `creationDate` ';
        } else {
            $sql ='SELECT * FROM ' . $this->getDatabaseTableName() . ' WHERE `key` = :key ORDER BY `creationDate` ';
        }

        $data = $this->db->fetchAllAssociative($sql,
            ['key' => $key, 'languages' => $languages],
            ['languages' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        );

        if (!empty($data)) {
            foreach ($data as $d) {
                $this->model->addTranslation($d['language'], $d['text']);
                $this->model->setKey($d['key']);
                $this->model->setCreationDate($d['creationDate']);
                $this->model->setModificationDate($d['modificationDate']);
                $this->model->setType($d['type']);
                $this->model->setUserOwner($d['userOwner']);
                $this->model->setUserModification($d['userModification']);
            }
        } else {
            throw new NotFoundResourceException("Translation-Key -->'" . $key . "'<-- not found");
        }
    }

    /**
     * Save object to database
     */
    public function save()
    {
        //Create Domain table if doesn't exist
        $this->createOrUpdateTable();

        $this->updateModificationInfos();

        $editableLanguages = [];
        if ($this->model->getDomain() != Model\Translation::DOMAIN_ADMIN) {
            if ($user = User::getById($this->model->getUserModification())) {
                $editableLanguages = $user->getAllowedLanguagesForEditingWebsiteTranslations();
            }
        }

        if ($this->model->getKey() !== '') {
            if (is_array($this->model->getTranslations())) {
                foreach ($this->model->getTranslations() as $language => $text) {
                    if (count($editableLanguages) && !in_array($language, $editableLanguages)) {
                        Logger::warning(sprintf('User %s not allowed to edit %s translation', $user->getUsername(), $language)); // @phpstan-ignore-line

                        continue;
                    }

                    $data = [
                        'key' => $this->model->getKey(),
                        'type' => $this->model->getType(),
                        'language' => $language,
                        'text' => $text,
                        'modificationDate' => $this->model->getModificationDate(),
                        'creationDate' => $this->model->getCreationDate(),
                        'userOwner' => $this->model->getUserOwner(),
                        'userModification' => $this->model->getUserModification(),
                    ];
                    Helper::insertOrUpdate($this->db, $this->getDatabaseTableName(), $data);
                }
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
        $l = $this->db->fetchAllAssociative('SELECT * FROM ' . $this->getDatabaseTableName()  . '  GROUP BY `language`;');
        $languages = [];

        foreach ($l as $values) {
            $languages[] = $values['language'];
        }

        return $languages;
    }

    /**
     * Returns a array containing all available domains
     *
     * @return array
     */
    public function getAvailableDomains()
    {
        $domainTables = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'translations_%'");
        $domains = [];

        foreach ($domainTables as $domainTable) {
            $domains[] = str_replace('translations_', '', $domainTable[array_key_first($domainTable)]);
        }

        return $domains;
    }

    /**
     * Returns boolean, if the domain table exists
     *
     * @param string $domain
     *
     * @return bool
     */
    public function isAValidDomain(string $domain): bool
    {
        try {
            $this->db->fetchOne(sprintf('SELECT * FROM translations_%s LIMIT 1;', $domain));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createOrUpdateTable()
    {
        $table = $this->getDatabaseTableName();

        if ($table == self::TABLE_PREFIX) {
            throw new \Exception('Domain is missing to create new translation domain');
        }

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $table . "` (
                          `key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
                          `type` varchar(10) DEFAULT NULL,
                          `language` varchar(10) NOT NULL DEFAULT '',
                          `text` text DEFAULT NULL,
                          `creationDate` int(11) unsigned DEFAULT NULL,
                          `modificationDate` int(11) unsigned DEFAULT NULL,
                          `userOwner` int(11) unsigned DEFAULT NULL,
                          `userModification` int(11) unsigned DEFAULT NULL,
                          PRIMARY KEY (`key`,`language`),
                          KEY `language` (`language`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    protected function updateModificationInfos(): void
    {
        $updateTime = time();
        $this->model->setModificationDate($updateTime);

        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($updateTime);
        }

        // auto assign user if possible, if no user present, use ID=0 which represents the "system" user
        $userId = \Pimcore\Tool\Admin::getCurrentUser()?->getId() ?? 0;
        $this->model->setUserModification($userId);

        if ($this->model->getUserOwner() === null) {
            $this->model->setUserOwner($userId);
        }
    }
}
