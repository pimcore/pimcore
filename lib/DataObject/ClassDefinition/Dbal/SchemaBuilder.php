<?php


declare(strict_types=1);

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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\ClassDefinition\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Pimcore\Model;
use Pimcore\Tool;

class SchemaBuilder implements SchemaBuilderInterface
{
    private $database;

    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    public function buildSchema(Model\DataObject\ClassDefinition $classDefinition): Schema
    {
        $queryTableName = 'object_query_'.$classDefinition->getId();
        $storeTableName = 'object_store_'.$classDefinition->getId();
        $relationsTableName = 'object_relations_'.$classDefinition->getId();
        $objectView = 'object_'.$classDefinition->getId();

        $hasLocalizedFields = false;
        $hasBricks = false;
        $hasFieldcollections = false;

        $queryTable = $this->getQueryTable($queryTableName, $classDefinition);
        $storeTable = $this->getStoreTable($storeTableName, $classDefinition);
        $relationsTable = $this->getRelationsTable($relationsTableName, $classDefinition);

        $fieldDefinitions = $classDefinition->getFieldDefinitions();

        if (is_array($fieldDefinitions) && count($fieldDefinitions)) {
            foreach ($fieldDefinitions as $key => $value) {
                $this->processFieldDefinitionColumnStoreTable($storeTable, $value);
                $this->processFieldDefinitionColumnQueryTable($queryTable, $value);

                if ($value instanceof Model\DataObject\ClassDefinition\Data\SchemaResourcePersistanceInterface) {
                    foreach ($value->getTables() as $table) {
                        $tables[] = $table;
                    }
                }
            }
        }

        $tables[] = $queryTable;
        $tables[] = $storeTable;
        $tables[] = $relationsTable;

        foreach ($this->getLocalizedTables($classDefinition) as $table) {
            $tables[] = $table;
        }

        foreach ($this->getObjectBrickTables($classDefinition) as $table) {
            $tables[] = $table;
        }

        foreach ($this->getFieldcollectionTables($classDefinition) as $table) {
            $tables[] = $table;
        }

        if ($classDefinition->getEncryption()) {
            foreach ($tables as &$table) {
                $table->addOption('ENCRYTED', 'ENCRYPTED');

                $classDefinition->addEncryptedTables($table->getName());
            }
        }

        $newSchema = new Schema($tables);

        return $newSchema;
    }

    public function getMigrateSchema(Model\DataObject\ClassDefinition $classDefinition): string
    {
        $schemaManager = $this->database->getSchemaManager();

        $queryTableName = 'object_query_'.$classDefinition->getId();
        $storeTableName = 'object_store_'.$classDefinition->getId();
        $relationsTableName = 'object_relations_'.$classDefinition->getId();
        $objectView = 'object_'.$classDefinition->getId();
        $oldTables = [];

        $searchTables = [
            $queryTableName => true,
            $storeTableName => true,
            $relationsTableName => true,
        ];

        $brickDefinitions = new Model\DataObject\Objectbrick\Definition\Listing();
        $brickDefinitions = $brickDefinitions->load();

        $fieldCollectionDefinitions = new Model\DataObject\Fieldcollection\Definition\Listing();
        $fieldCollectionDefinitions = $fieldCollectionDefinitions->load();

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            $searchTables['object_localized_data_'.$classDefinition->getId()] = true;

            foreach (Tool::getValidLanguages() as $language) {
                $searchTables['object_localized_query_'.$classDefinition->getId().'_'.$language] = true;
            }
        }

        foreach ($brickDefinitions as $definition) {
            $searchTables['object_brick_query_'.$definition->getKey().'_'.$classDefinition->getId()] = true;
            $searchTables['object_brick_store_'.$definition->getKey().'_'.$classDefinition->getId()] = true;
            $searchTables['object_brick_localized_'.$definition->getKey().'_'.$classDefinition->getId()] = true;

            foreach (Tool::getValidLanguages() as $language) {
                $searchTables['object_brick_localized_query_'.$definition->getKey().'_'.$classDefinition->getId().'_'.$language] = true;
            }
        }

        foreach ($fieldCollectionDefinitions as $definition) {
            $searchTables['object_collection_'.$definition->getKey().'_'.$classDefinition->getId()] = true;
            $searchTables['object_collection_'.$definition->getKey().'_localized_'.$classDefinition->getId()] = true;
        }

        foreach (array_keys($searchTables) as $searchTableName) {
            if ($schemaManager->tablesExist([$searchTableName])) {
                $oldTables[] = $schemaManager->listTableDetails($searchTableName);
            }
        }

        $newSchema = $this->buildSchema($classDefinition);
        $oldSchema = new Schema($oldTables);

        //Doctrine Schema doesn't support Views....
        $view = 'CREATE OR REPLACE VIEW `'.$objectView.'` AS SELECT * FROM `'.$queryTableName.'` JOIN `objects` ON `objects`.`o_id` = `'.$queryTableName.'`.`oo_id`;';

        $diffs = $newSchema->getMigrateFromSql($oldSchema, $this->database->getDatabasePlatform());

        $diffString = '';

        foreach ($diffs as &$diff) {
            $diffString .= $diff.';'.PHP_EOL;
        }

        $diff = $diffString.PHP_EOL.$view;

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($oldSchema, $newSchema);
        $relationDeleteSqls = [];

        foreach ($schemaDiff->changedTables as $changedTable) {
            if ($changedTable->getNewName() !== $storeTableName) {
                continue;
            }

            foreach ($changedTable->removedColumns as $column) {
                $qb = new QueryBuilder($this->database);
                $qb->from($relationsTableName)
                    ->where('fieldname = :fieldname')
                    ->andWhere('ownertype = "object"')
                    ->setParameter('fieldname', $column)
                    ->delete();

                $relationDeleteSqls[] = $qb->getSQL();
            }
        }

        $diff .= PHP_EOL.implode(';'.PHP_EOL, $relationDeleteSqls);

        return $diff;
    }

    protected function processFieldDefinitionColumnStoreTable(
        Table $storeTable,
        Model\DataObject\ClassDefinition\Data $fd
    ) {
        if ($fd instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
            $this->addColumnsToTable($storeTable, $fd, $fd->getSchemaColumns());
        }
    }

    protected function processFieldDefinitionColumnQueryTable(
        Table $queryTable,
        Model\DataObject\ClassDefinition\Data $fd
    ) {
        if ($fd instanceof Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface) {
            $this->addColumnsToTable($queryTable, $fd, $fd->getQuerySchemaColumns());
        }
    }

    private function addColumnsToTable(
        Table $table,
        ?Model\DataObject\ClassDefinition\Data $fd = null,
        array $schemaColumns
    ) {
        foreach ($schemaColumns as $col) {
            if (!$col instanceof Column) {
                throw new \InvalidArgumentException(sprintf('Expected Type %s, got type %s', Column::class,
                    get_class($col)));
            }

            $table->addColumn($col->getName(), $col->getType()->getName(), $col->toArray());
        }

        if ($fd && $fd->getIndex()) {
            $indexFields = [];

            foreach ($schemaColumns as $column) {
                $indexFields[] = $column->getName();
            }

            if ($fd->getUnique()) {
                $table->addUniqueIndex($indexFields);
            } else {
                $table->addIndex($indexFields);
            }
        }
    }

    private function getQueryTable(
        $tableName,
        Model\DataObject\ClassDefinition $classDefinition
    ): Table {
        $objectTable = new Table($tableName);
        $objectTable->addColumn('oo_id', Types::INTEGER, [
            'notnull' => true,
            'length' => 11,
            'default' => 0,
        ]);
        $objectTable->addColumn('oo_classId', Types::STRING, [
            'length' => 50,
            'notnull' => false,
            'default' => $classDefinition->getId(),
        ]);
        $objectTable->addColumn('oo_className', Types::STRING, [
            'length' => 255,
            'notnull' => false,
            'default' => $classDefinition->getName(),
        ]);
        $objectTable->setPrimaryKey(['oo_id']);

        return $objectTable;
    }

    private function getStoreTable(
        $tableName,
        Model\DataObject\ClassDefinition $classDefinition
    ): Table {
        $dataStoreTable = new Table($tableName);
        $dataStoreTable->addColumn('oo_id', Types::INTEGER, [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $dataStoreTable->setPrimaryKey(['oo_id']);

        return $dataStoreTable;
    }

    private function getRelationsTable(
        $tableName,
        Model\DataObject\ClassDefinition $classDefinition
    ): Table {
        $dataStoreRelationsTable = new Table($tableName);
        $dataStoreRelationsTable->addColumn('id', Types::BIGINT, [
            'length' => 20,
            'notnull' => true,
            'autoincrement' => true,
        ]);
        $dataStoreRelationsTable->addColumn('src_id', Types::INTEGER, [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $dataStoreRelationsTable->addColumn('dest_id', Types::INTEGER, [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $dataStoreRelationsTable->addColumn('type', Types::STRING, [
            'length' => 50,
            'notnull' => true,
            'default' => '',
        ]);
        $dataStoreRelationsTable->addColumn('fieldname', Types::STRING, [
            'length' => 70,
            'notnull' => true,
            'default' => '0',
        ]);
        $dataStoreRelationsTable->addColumn('index', Types::INTEGER, [
            'length' => 11,
            'unsigned' => true,
            'notnull' => true,
            'default' => '0',
        ]);
        $dataStoreRelationsTable->addColumn('ownertype', Types::STRING, [
            'columnDefinition' => "enum('object','fieldcollection','localizedfield','objectbrick')",
            'notnull' => true,
            'default' => 'object',
        ]);
        $dataStoreRelationsTable->addColumn('ownername', Types::STRING, [
            'length' => 70,
            'notnull' => true,
            'default' => '',
        ]);
        $dataStoreRelationsTable->addColumn('position', Types::STRING, [
            'length' => 70,
            'notnull' => true,
            'default' => '0',
        ]);
        $dataStoreRelationsTable->setPrimaryKey(['id']);
        $dataStoreRelationsTable->addIndex(['src_id', 'ownertype', 'ownername', 'position'], 'forward_lookup');
        $dataStoreRelationsTable->addIndex(['dest_id', 'type'], 'reverse_lookup');

        return $dataStoreRelationsTable;
    }

    private function getLocalizedTables(Model\DataObject\ClassDefinition $classDefinition): array
    {
        $fieldDefinition = $classDefinition->getFieldDefinition('localizedfields', ['suppressEnrichment' => true]);

        if ($fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
            $tableName = 'object_localized_data_'.$classDefinition->getId();
            $tables = [];

            $storeTable = new Table($tableName);

            $this->processFieldDefinitionColumnStoreTable($storeTable, $fieldDefinition);

            $tables[] = $storeTable;

            foreach (Tool::getValidLanguages() as $validLanguage) {
                $queryTableName = 'object_localized_query_'.$classDefinition->getId().'_'.$validLanguage;
                $queryTable = new Table($queryTableName);

                $this->processFieldDefinitionColumnQueryTable($queryTable, $fieldDefinition);

                $tables[] = $queryTable;
            }

            return $tables;
        }

        return [];
    }

    protected function getLocalizedStoreTable(
        Table $storeTable,
        Model\DataObject\ClassDefinition\Data\Localizedfields $localizedfield
    ) {
        $storeTable->addColumn('ooo_id', Types::INTEGER, [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $storeTable->addColumn('language', Types::STRING, [
            'length' => 10,
            'notnull' => true,
            'default' => '',
        ]);
        $storeTable->setPrimaryKey(['ooo_id', 'language']);
        $storeTable->addIndex(['language'], 'language');

        foreach ($localizedfield->getFieldDefinitions(['suppressEnrichment' => true]) as $definition) {
            if ($definition instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
                $this->processFieldDefinitionColumnStoreTable($storeTable, $definition);
            }
        }
    }

    protected function getLocalizedQueryTable(
        Table $queryTable,
        Model\DataObject\ClassDefinition\Data\Localizedfields $localizedfield
    ) {
        $queryTable->addColumn('ooo_id', Types::INTEGER, [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $queryTable->addColumn('language', Types::STRING, [
            'length' => 10,
            'notnull' => true,
            'default' => '',
        ]);
        $queryTable->setPrimaryKey(['ooo_id', 'language']);
        $queryTable->addIndex(['language'], 'language');

        foreach ($localizedfield->getFieldDefinitions(['suppressEnrichment' => true]) as $definition) {
            if ($definition instanceof Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface) {
                $this->processFieldDefinitionColumnQueryTable($queryTable, $definition);
            }
        }
    }

    private function getObjectBrickTables(Model\DataObject\ClassDefinition $classDefinition): array
    {
        $tables = [];
        $typesDone = [];

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\Objectbricks) {
                $types = $fieldDefinition->getAllowedTypes();

                foreach ($types as $type) {
                    $brickDefinition = Model\DataObject\Objectbrick\Definition::getByKey($type);

                    $queryTableName = 'object_brick_query_'.$brickDefinition->getKey().'_'.$classDefinition->getId();
                    $storeTableName = 'object_brick_store_'.$brickDefinition->getKey().'_'.$classDefinition->getId();

                    $queryTable = new Table($queryTableName);
                    $queryTable->addColumn('o_id', Types::INTEGER, [
                        'length' => 11,
                        'notnull' => true,
                        'default' => 0,
                    ]);
                    $queryTable->addColumn('fieldname', Types::STRING, [
                        'length' => 190,
                        'notnull' => false,
                        'default' => '',
                    ]);
                    $queryTable->addIndex(['o_id'], 'o_id');
                    $queryTable->addIndex(['fieldname'], 'fieldname');

                    $storeTable = new Table($storeTableName);
                    $storeTable->addColumn('o_id', Types::INTEGER, [
                        'length' => 11,
                        'notnull' => true,
                        'default' => 0,
                    ]);
                    $storeTable->addColumn('fieldname', Types::STRING, [
                        'length' => 190,
                        'notnull' => false,
                        'default' => '',
                    ]);
                    $storeTable->addIndex(['o_id'], 'o_id');
                    $storeTable->addIndex(['fieldname'], 'fieldname');

                    $tables[] = $queryTable;
                    $tables[] = $storeTable;

                    foreach ($brickDefinition->getFieldDefinitions() as $definition) {
                        if ($definition instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
                            $this->addColumnsToTable($storeTable, $definition, $definition->getSchemaColumns());
                        }

                        if ($definition instanceof Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface) {
                            $this->addColumnsToTable($queryTable, $definition, $definition->getQuerySchemaColumns());
                        }

                        if ($definition instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
                            $tableName = 'object_brick_localized_'.$brickDefinition->getKey().'_'.$classDefinition->getId();

                            $localizedStoreTable = new Table($tableName);
                            $this->getLocalizedStoreTable($localizedStoreTable, $definition);

                            $localizedStoreTable->addColumn('fieldname', Types::STRING, [
                                'length' => 190,
                                'notnull' => false,
                                'default' => '',
                            ]);
                            $localizedStoreTable->addColumn('index', Types::INTEGER, [
                                'length' => 11,
                                'notnull' => true,
                                'default' => 0,
                            ]);

                            $tables[] = $localizedStoreTable;

                            foreach (Tool::getValidLanguages() as $validLanguage) {
                                $tableName = 'object_brick_localized_query_'.$brickDefinition->getKey().'_'.$classDefinition->getId().'_'.$validLanguage;
                                $localizedQueryTable = new Table($tableName);

                                $this->getLocalizedQueryTable($localizedQueryTable, $definition);

                                $tables[] = $localizedQueryTable;
                            }
                        }
                    }
                }
            }
        }

        return $tables;
    }

    private function getFieldcollectionTables(Model\DataObject\ClassDefinition $classDefinition): array
    {
        $tables = [];
        $typesDone = [];

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\Fieldcollections) {
                $types = $fieldDefinition->getAllowedTypes();

                foreach ($types as $type) {
                    $brickDefinition = Model\DataObject\Fieldcollection\Definition::getByKey($type);

                    $tableName = 'object_collection_'.$brickDefinition->getKey().'_'.$classDefinition->getId();

                    $table = new Table($tableName);
                    $table->addColumn('o_id', Types::INTEGER, [
                        'length' => 11,
                        'notnull' => true,
                        'default' => 0,
                    ]);
                    $table->addColumn('index', Types::INTEGER, [
                        'length' => 11,
                        'notnull' => true,
                        'default' => 0,
                    ]);
                    $table->addColumn('fieldname', Types::STRING, [
                        'length' => 190,
                        'notnull' => false,
                        'default' => '',
                    ]);
                    $table->addIndex(['o_id'], 'o_id');
                    $table->addIndex(['index'], 'index');
                    $table->addIndex(['fieldname'], 'fieldname');

                    $tables[] = $table;

                    foreach ($brickDefinition->getFieldDefinitions() as $definition) {
                        if ($definition instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
                            $this->addColumnsToTable($table, $definition, $definition->getSchemaColumns());
                        }

                        if ($definition instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
                            $tableName = 'object_collection_'.$brickDefinition->getKey().'_localized_'.$classDefinition->getId();

                            $localizedStoreTable = new Table($tableName);
                            $this->getLocalizedStoreTable($localizedStoreTable, $definition);

                            $localizedStoreTable->addColumn('fieldname', Types::STRING, [
                                'length' => 190,
                                'notnull' => false,
                                'default' => '',
                            ]);
                            $localizedStoreTable->addColumn('index', Types::INTEGER, [
                                'length' => 11,
                                'notnull' => true,
                                'default' => 0,
                            ]);

                            $tables[] = $localizedStoreTable;
                        }
                    }
                }
            }
        }

        return $tables;
    }
}
