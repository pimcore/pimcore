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
 * @package    DataObject\Objectbrick
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Objectbrick\Definition;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\Objectbrick\Definition $model
 */
class Dao extends Model\DataObject\Fieldcollection\Definition\Dao
{
    /**
     * @param DataObject\ClassDefinition $class
     * @param bool $query
     *
     * @return string
     */
    public function getTableName(DataObject\ClassDefinition $class, $query = false)
    {
        if ($query) {
            return 'object_brick_query_' . $this->model->getKey() . '_' . $class->getId();
        } else {
            return 'object_brick_store_' . $this->model->getKey() . '_' . $class->getId();
        }
    }

    /**
     * @param DataObject\ClassDefinition $class
     */
    public function delete(DataObject\ClassDefinition $class)
    {
        $table = $this->getTableName($class, false);
        $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');

        $table = $this->getTableName($class, true);
        $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
    }

    /**
     * @param DataObject\ClassDefinition $class
     */
    public function createUpdateTable(DataObject\ClassDefinition $class)
    {
        $tableStore = $this->getTableName($class, false);
        $tableQuery = $this->getTableName($class, true);

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $tableStore . "` (
		  `o_id` int(11) NOT NULL default '0',
          `fieldname` varchar(190) default '',
          PRIMARY KEY (`o_id`,`fieldname`),
          INDEX `o_id` (`o_id`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8mb4;");

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $tableQuery . "` (
		  `o_id` int(11) NOT NULL default '0',
          `fieldname` varchar(190) default '',
          PRIMARY KEY (`o_id`,`fieldname`),
          INDEX `o_id` (`o_id`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8mb4;");

        $existingColumnsStore = $this->getValidTableColumns($tableStore, false); // no caching of table definition
        $columnsToRemoveStore = $existingColumnsStore;
        $existingColumnsQuery = $this->getValidTableColumns($tableQuery, false); // no caching of table definition
        $columnsToRemoveQuery = $existingColumnsQuery;

        $protectedColumnsStore = ['o_id', 'fieldname'];
        $protectedColumnsQuery = ['o_id', 'fieldname'];

        DataObject\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, ([$tableStore, $tableQuery]));

        /** @var DataObject\ClassDefinition\Data $value */
        foreach ($this->model->getFieldDefinitions() as $value) {
            $key = $value->getName();

            if ($value instanceof DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface || method_exists($value, 'getDataForResource')) {
                // if a datafield requires more than one column in the datastore table => only for non-relation types

                /** @var DataObject\ClassDefinition\Data&DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface $value */
                if (!$value->isRelationType()) {
                    if (is_array($value->getColumnType())) {
                        foreach ($value->getColumnType() as $fkey => $fvalue) {
                            $this->addModifyColumn($tableStore, $key . '__' . $fkey, $fvalue, '', 'NULL');
                            $protectedColumnsStore[] = $key . '__' . $fkey;
                        }
                    } elseif ($value->getColumnType()) {
                        $this->addModifyColumn($tableStore, $key, $value->getColumnType(), '', 'NULL');
                        $protectedColumnsStore[] = $key;
                    }
                }

                $this->addIndexToField($value, $tableStore, 'getColumnType', true);
            }

            if ($value instanceof DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface) {
                // if a datafield requires more than one column in the query table
                if (is_array($value->getQueryColumnType())) {
                    foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($tableQuery, $key . '__' . $fkey, $fvalue, '', 'NULL');
                        $protectedColumnsQuery[] = $key . '__' . $fkey;
                    }
                } elseif ($value->getQueryColumnType()) {
                    $this->addModifyColumn($tableQuery, $key, $value->getQueryColumnType(), '', 'NULL');
                    $protectedColumnsQuery[] = $key;
                }

                $this->addIndexToField($value, $tableQuery, 'getQueryColumnType');
            }

            if ($value instanceof  DataObject\ClassDefinition\Data\Localizedfields) {
                $value->classSaved(
                    $class,
                    [
                        'context' => [
                            'containerType' => 'objectbrick',
                            'containerKey' => $this->model->getKey(),
                        ],
                    ]
                );
            }
        }

        $this->removeUnusedColumns($tableStore, $columnsToRemoveStore, $protectedColumnsStore);
        $this->removeUnusedColumns($tableQuery, $columnsToRemoveQuery, $protectedColumnsQuery);
    }

    /**
     * @param DataObject\ClassDefinition $classDefinition
     */
    public function classSaved(DataObject\ClassDefinition $classDefinition)
    {
        $tableStore = $this->getTableName($classDefinition, false);
        $tableQuery = $this->getTableName($classDefinition, true);

        $this->handleEncryption($classDefinition, [$tableQuery, $tableStore]);
    }
}
