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

namespace Pimcore\Model\DataObject\Data\ObjectMetadata;

use Pimcore\Db\Helper;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Data\ObjectMetadata $model
 */
class Dao extends DataObject\Data\AbstractMetadata\Dao
{
    use DataObject\ClassDefinition\Helper\Dao;

    protected ?array $tableDefinitions = null;

    public function save(DataObject\Concrete $object, string $ownertype, string $ownername, string $position, int $index, string $type = 'object'): void
    {
        $table = $this->getTablename($object);

        $dataTemplate = ['id' => $object->getId(),
            'dest_id' => $this->model->getElement()->getId(),
            'fieldname' => $this->model->getFieldname(),
            'ownertype' => $ownertype,
            'ownername' => $ownername ? $ownername : '',
            'index' => $index ? $index : '0',
            'position' => $position ? $position : '0',
            'type' => $type ? $type : 'object', ];

        foreach ($this->model->getColumns() as $column) {
            $getter = 'get' . ucfirst($column);
            $data = $dataTemplate;
            $data['column'] = $column;
            $data['data'] = $this->model->$getter();
            Helper::upsert($this->db, $table, $data, $this->getPrimaryKey($table));
        }
    }

    protected function getTablename(DataObject\Concrete $object): string
    {
        return 'object_metadata_' . $object->getClassId();
    }

    public function load(DataObject\Concrete $source, int $destinationId, string $fieldname, string $ownertype, string $ownername, string $position, int $index, string $destinationType = 'object'): ?DataObject\Data\ObjectMetadata
    {
        $typeQuery = " AND (`type` = 'object' or `type` = '')";

        $query = 'SELECT * FROM ' . $this->getTablename($source) . ' WHERE id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ? and `index` = ? ' . $typeQuery;
        $dataRaw = $this->db->fetchAllAssociative($query, [$source->getId(), $destinationId, $fieldname, $ownertype, $ownername, $position, $index]);
        if (!empty($dataRaw)) {
            $this->model->setObjectId($destinationId);
            $this->model->setFieldname($fieldname);
            $columns = $this->model->getColumns();
            foreach ($dataRaw as $row) {
                if (in_array($row['column'], $columns)) {
                    $setter = 'set' . ucfirst($row['column']);
                    $this->model->$setter($row['data']);
                }
            }

            return $this->model;
        } else {
            return null;
        }
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
