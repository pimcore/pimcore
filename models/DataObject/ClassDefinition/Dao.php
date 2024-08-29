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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Exception;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\ClassDefinition $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use DataObject\ClassDefinition\Helper\Dao;
    use DataObject\Traits\CompositeIndexTrait;

    /**
     * @var DataObject\ClassDefinition
     */
    protected $model;

    protected array $tableDefinitions = [];

    public function getNameById(string $id): ?string
    {
        try {
            if (!empty($id)) {
                if ($name = $this->db->fetchOne('SELECT name FROM classes WHERE id = ?', [$id])) {
                    return $name;
                }
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getIdByName(string $name): string
    {
        $id = null;

        try {
            if (!empty($name)) {
                $id = $this->db->fetchOne('SELECT id FROM classes WHERE name = ?', [$name]);
            }
        } catch (Exception $e) {
        }

        if (empty($id)) {
            throw new Model\Exception\NotFoundException(sprintf(
                'Data object class definition with name "%s" does not exist.', $name
            ), 0, $e ?? null);
        }

        return $id;
    }

    /**
     *
     * @throws Exception
     */
    public function save(bool $isUpdate = true): void
    {
        if (!$this->model->getId() || !$isUpdate) {
            $this->create();
        }

        $this->update();
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        $class = $this->model->getObjectVars();
        $data = [];

        foreach ($class as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('classes'))) {
                $data[$key] = $value;
            }
        }

        $data['definitionModificationDate'] = $this->model->getModificationDate();

        $this->db->update('classes', $data, ['id' => $this->model->getId()]);

        $objectTable = 'object_query_' . $this->model->getId();
        $objectDatastoreTable = 'object_store_' . $this->model->getId();
        $objectDatastoreTableRelation = 'object_relations_' . $this->model->getId();

        $objectView = 'object_' . $this->model->getId();

        // create object table if not exists
        $protectedColumns = ['oo_id', 'oo_classId', 'oo_className'];
        $protectedDatastoreColumns = ['oo_id'];

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $objectTable . "` (
			  `oo_id` int(11) UNSIGNED NOT NULL default '0',
			  `oo_classId` varchar(50) default '" . $this->model->getId() . "',
			  `oo_className` varchar(255) default '" . $this->model->getName() . "',
			  PRIMARY KEY  (`oo_id`),
			  CONSTRAINT `".self::getForeignKeyName($objectTable, 'oo_id').'` FOREIGN KEY (`oo_id`) REFERENCES objects (`id`) ON DELETE CASCADE
			) DEFAULT CHARSET=utf8mb4;');

        // update default value of classname columns
        $this->db->executeQuery('ALTER TABLE `' . $objectTable . "` ALTER COLUMN `oo_className` SET DEFAULT '" . $this->model->getName() . "';");

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $objectDatastoreTable . "` (
			  `oo_id` int(11) UNSIGNED NOT NULL default '0',
			  PRIMARY KEY  (`oo_id`),
			  CONSTRAINT `".self::getForeignKeyName($objectDatastoreTable, 'oo_id').'` FOREIGN KEY (`oo_id`) REFERENCES objects (`id`) ON DELETE CASCADE
			) DEFAULT CHARSET=utf8mb4;');

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $objectDatastoreTableRelation . "` (
              `id` BIGINT(20) NOT NULL PRIMARY KEY  AUTO_INCREMENT,
              `src_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
              `dest_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
              `type` enum('object', 'asset','document') NOT NULL,
              `fieldname` varchar(70) NOT NULL DEFAULT '0',
              `index` int(11) unsigned NOT NULL DEFAULT '0',
              `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
              `ownername` varchar(70) NOT NULL DEFAULT '',
              `position` varchar(70) NOT NULL DEFAULT '0',
              INDEX `forward_lookup` (`src_id`, `ownertype`, `ownername`, `position`),
              INDEX `reverse_lookup` (`dest_id`, `type`),
              INDEX `fieldname` (`fieldname`),
			  CONSTRAINT `".self::getForeignKeyName($objectDatastoreTableRelation, 'src_id').'` FOREIGN KEY (`src_id`) REFERENCES objects (`id`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8mb4;');

        $this->handleEncryption($this->model, [$objectTable, $objectDatastoreTable, $objectDatastoreTableRelation]);

        $existingColumns = $this->getValidTableColumns($objectTable, false); // no caching of table definition
        $existingDatastoreColumns = $this->getValidTableColumns($objectDatastoreTable, false); // no caching of table definition

        $columnsToRemove = $existingColumns;
        $datastoreColumnsToRemove = $existingDatastoreColumns;

        DataObject\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, [$objectTable, $objectDatastoreTable]);

        // add non existing columns in the table
        foreach ($this->model->getFieldDefinitions() as $key => $value) {
            if ($value instanceof DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface
                && $value instanceof DataObject\ClassDefinition\Data) {
                // if a datafield requires more than one column in the datastore table => only for non-relation types
                if (!$value->isRelationType()) {
                    if (is_array($value->getColumnType())) {
                        foreach ($value->getColumnType() as $fkey => $fvalue) {
                            $this->addModifyColumn($objectDatastoreTable, $key . '__' . $fkey, $fvalue, '', 'NULL');
                            $protectedDatastoreColumns[] = $key . '__' . $fkey;
                        }
                    } elseif ($value->getColumnType()) {
                        $this->addModifyColumn($objectDatastoreTable, $key, $value->getColumnType(), '', 'NULL');
                        $protectedDatastoreColumns[] = $key;
                    }
                }

                $this->addIndexToField($value, $objectDatastoreTable, 'getColumnType', true);
            }

            if ($value instanceof DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface
                && $value instanceof DataObject\ClassDefinition\Data) {
                // if a datafield requires more than one column in the query table
                if (is_array($value->getQueryColumnType())) {
                    foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($objectTable, $key . '__' . $fkey, $fvalue, '', 'NULL');
                        $protectedColumns[] = $key . '__' . $fkey;
                    }
                } elseif ($value->getQueryColumnType()) {
                    $this->addModifyColumn($objectTable, $key, $value->getQueryColumnType(), '', 'NULL');
                    $protectedColumns[] = $key;
                }

                $this->addIndexToField($value, $objectTable, 'getQueryColumnType');
            }
        }

        // remove unused columns in the table
        $this->removeUnusedColumns($objectTable, $columnsToRemove, $protectedColumns);
        $this->removeUnusedColumns($objectDatastoreTable, $datastoreColumnsToRemove, $protectedDatastoreColumns);

        // remove / cleanup unused relations
        foreach ($columnsToRemove as $value) {
            if (!in_array(strtolower($value), array_map('strtolower', $protectedColumns))) {
                $this->db->delete($objectDatastoreTableRelation, ['fieldname' => $value, 'ownertype' => 'object']);
                // @TODO: remove localized fields and fieldcollections
            }
        }

        // create view
        try {
            //$this->db->executeQuery('CREATE OR REPLACE VIEW `' . $objectView . '` AS SELECT * FROM `objects` left JOIN `' . $objectTable . '` ON `objects`.`id` = `' . $objectTable . '`.`oo_id` WHERE `objects`.`classId` = ' . $this->model->getId() . ';');
            $this->db->executeQuery('CREATE OR REPLACE VIEW `' . $objectView . '` AS SELECT * FROM `' . $objectTable . '` JOIN `objects` ON `objects`.`id` = `' . $objectTable . '`.`oo_id`;');
        } catch (Exception $e) {
            Logger::debug((string) $e);
        }

        $this->updateCompositeIndices($objectDatastoreTable, 'store', $this->model->getCompositeIndices());
        $this->updateCompositeIndices($objectTable, 'query', $this->model->getCompositeIndices());

        $this->tableDefinitions = [];
    }

    /**
     * Create a new record for the object in database
     *
     */
    public function create(): void
    {
        $this->db->insert(
            'classes',
            [
                'name' => $this->model->getName(),
                'id' => $this->model->getId(),
                'definitionModificationDate' => $this->model->getModificationDate(),
            ]
        );
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('classes', ['id' => $this->model->getId()]);

        $objectTable = 'object_query_' . $this->model->getId();
        $objectDatastoreTable = 'object_store_' . $this->model->getId();
        $objectDatastoreTableRelation = 'object_relations_' . $this->model->getId();
        $objectMetadataTable = 'object_metadata_' . $this->model->getId();

        $this->db->executeQuery('DROP TABLE IF EXISTS `' . $objectTable . '`');
        $this->db->executeQuery('DROP TABLE IF EXISTS `' . $objectDatastoreTable . '`');
        $this->db->executeQuery('DROP TABLE IF EXISTS `' . $objectDatastoreTableRelation . '`');
        $this->db->executeQuery('DROP TABLE IF EXISTS `' . $objectMetadataTable . '`');

        $this->db->executeQuery('DROP VIEW IF EXISTS `object_' . $this->model->getId() . '`');

        // delete data
        $this->db->delete('objects', ['classId' => $this->model->getId()]);

        // remove fieldcollection tables
        $allTables = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'object\_collection\_%\_" . $this->model->getId() . "'");
        foreach ($allTables as $table) {
            $collectionTable = current($table);
            $this->db->executeQuery('DROP TABLE IF EXISTS `'.$collectionTable.'`');
        }

        // remove localized fields tables and views
        $allViews = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_" . $this->model->getId() . "\_%'");
        foreach ($allViews as $view) {
            $localizedView = current($view);
            $this->db->executeQuery('DROP VIEW IF EXISTS `'.$localizedView.'`');
        }

        $allTables = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_query\_" . $this->model->getId() . "\_%'");
        foreach ($allTables as $table) {
            $queryTable = current($table);
            $this->db->executeQuery('DROP TABLE IF EXISTS `'.$queryTable.'`');
        }

        $this->db->executeQuery('DROP TABLE IF EXISTS object_localized_data_' . $this->model->getId());

        // objectbrick tables
        $allTables = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'object\_brick\_%\_" . $this->model->getId() . "'");
        foreach ($allTables as $table) {
            $brickTable = current($table);
            $this->db->executeQuery('DROP TABLE IF EXISTS `'.$brickTable.'`');
        }

        $this->db->executeQuery('DROP TABLE IF EXISTS object_classificationstore_data_'.$this->model->getId());
        $this->db->executeQuery('DROP TABLE IF EXISTS object_classificationstore_groups_'.$this->model->getId());

        // clean slug table
        DataObject\Data\UrlSlug::handleClassDeleted($this->model->getId());
    }

    /**
     * Update the class name in all object
     *
     */
    public function updateClassNameInObjects(string $newName): void
    {
        $this->db->update('objects', ['className' => $newName], ['classId' => $this->model->getId()]);

        $this->db->executeStatement('update ' . $this->db->quoteIdentifier('object_query_' . $this->model->getId()) .
        ' set oo_classname = :className', ['className' => $newName]);
    }

    public function getNameByIdIgnoreCase(string $id): ?string
    {
        if ($id !== '') {
            if ($name = $this->db->fetchOne('SELECT name FROM classes WHERE LOWER(id) = ?', [strtolower($id)])) {
                return $name;
            }
        }

        return null;
    }
}
