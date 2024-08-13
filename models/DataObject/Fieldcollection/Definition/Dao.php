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

namespace Pimcore\Model\DataObject\Fieldcollection\Definition;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Fieldcollection\Definition $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use DataObject\ClassDefinition\Helper\Dao;

    protected array $tableDefinitions = [];

    public function getTableName(DataObject\ClassDefinition $class): string
    {
        return 'object_collection_' . $this->model->getKey() . '_' . $class->getId();
    }

    public function getLocalizedTableName(DataObject\ClassDefinition $class): string
    {
        return 'object_collection_' . $this->model->getKey() . '_localized_' . $class->getId();
    }

    public function delete(DataObject\ClassDefinition $class): void
    {
        $table = $this->getTableName($class);
        $this->db->executeQuery('DROP TABLE IF EXISTS `' . $table . '`');
    }

    public function createUpdateTable(DataObject\ClassDefinition $class): void
    {
        $table = $this->getTableName($class);

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $table . "` (
		  `id` int(11) UNSIGNED NOT NULL default '0',
		  `index` int(11) default '0',
          `fieldname` varchar(190) default '',
          PRIMARY KEY (`id`,`index`,`fieldname`(190)),
          INDEX `index` (`index`),
          INDEX `fieldname` (`fieldname`),
          CONSTRAINT `".self::getForeignKeyName($table, 'id').'` FOREIGN KEY (`id`) REFERENCES objects (`id`) ON DELETE CASCADE
		) DEFAULT CHARSET=utf8mb4;');

        $existingColumns = $this->getValidTableColumns($table, false); // no caching of table definition
        $columnsToRemove = $existingColumns;
        $protectedColums = ['id', 'index', 'fieldname'];

        DataObject\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, ([$table]));

        foreach ($this->model->getFieldDefinitions() as $value) {
            $key = $value->getName();

            if ($value instanceof DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface
                && $value instanceof DataObject\ClassDefinition\Data) {
                if (is_array($value->getColumnType())) {
                    // if a datafield requires more than one field
                    foreach ($value->getColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($table, $key . '__' . $fkey, $fvalue, '', 'NULL');
                        $protectedColums[] = $key . '__' . $fkey;
                    }
                } else {
                    if ($value->getColumnType()) {
                        $this->addModifyColumn($table, $key, $value->getColumnType(), '', 'NULL');
                        $protectedColums[] = $key;
                    }
                }
                $this->addIndexToField($value, $table, 'getColumnType', true, false, true);
            }

            if ($value instanceof  DataObject\ClassDefinition\Data\Localizedfields) {
                $value->classSaved(
                    $class,
                    [
                        'context' => [
                            'containerType' => 'fieldcollection',
                            'containerKey' => $this->model->getKey(),
                        ],
                    ]
                );
            }
        }

        $this->removeIndices($table, $columnsToRemove, $protectedColums);
        $this->removeUnusedColumns($table, $columnsToRemove, $protectedColums);
        $this->tableDefinitions = [];
    }

    public function classSaved(DataObject\ClassDefinition $classDefinition): void
    {
        $this->handleEncryption($classDefinition, [$this->getTableName($classDefinition)]);
    }
}
