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

namespace Pimcore\Model\DataObject\Data\AbstractMetadata;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 */
class Dao extends Model\Dao\AbstractDao
{
    use DataObject\ClassDefinition\Helper\Dao;

    protected const UNIQUE_KEY_NAME = 'metadata_un';

    protected ?array $tableDefinitions = null;

    public function save(DataObject\Concrete $object, string $ownertype, string $ownername, string $position, int $index, string $type = 'object'): void
    {
        throw new Exception('Needs to be implemented by child class');
    }

    protected function getTablename(DataObject\Concrete $object): string
    {
        return 'object_metadata_' . $object->getClassId();
    }

    public function load(DataObject\Concrete $source, int $destinationId, string $fieldname, string $ownertype, string $ownername, string $position, int $index): ?Model\AbstractModel
    {
        throw new Exception('Needs to be implemented by child class');
    }

    public function createOrUpdateTable(DataObject\ClassDefinition $class): void
    {
        $classId = $class->getId();
        $table = 'object_metadata_' . $classId;

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $table . "` (
              `auto_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `id` int(11) UNSIGNED NOT NULL default '0',
              `dest_id` int(11) NOT NULL default '0',
	          `type` VARCHAR(50) NOT NULL DEFAULT '',
              `fieldname` varchar(71) NOT NULL,
              `column` varchar(190) NOT NULL,
              `data` text,
              `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
              `ownername` VARCHAR(70) NOT NULL DEFAULT '',
              `position` VARCHAR(70) NOT NULL DEFAULT '0',
              `index` int(11) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`auto_id`),
              UNIQUE KEY `metadata_un` (
                `id`, `dest_id`, `type`, `fieldname`, `column`, `ownertype`, `ownername`, `position`, `index`
              ),
              INDEX `dest_id` (`dest_id`),
              INDEX `fieldname` (`fieldname`),
              INDEX `column` (`column`),
              INDEX `ownertype` (`ownertype`),
              INDEX `ownername` (`ownername`),
              INDEX `position` (`position`),
              INDEX `index` (`index`),
              CONSTRAINT `".self::getForeignKeyName($table, 'id').'` FOREIGN KEY (`id`)
              REFERENCES objects (`id`) ON DELETE CASCADE
		) DEFAULT CHARSET=utf8mb4;');

        $this->handleEncryption($class, [$table]);
    }
}
