<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject;
use Pimcore\Tool;

final class Version20211018104331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function preUp(Schema $schema): void
    {
        //disable foreign key checks
        $this->connection->executeQuery('SET foreign_key_checks = 0');
    }

    public function postUp(Schema $schema): void
    {
        //enable foreign key checks
        $this->connection->executeQuery('SET foreign_key_checks = 1');
    }

    public function up(Schema $schema): void
    {
        $list = new DataObject\ClassDefinition\Listing();

        foreach ($list as $class) {
            $foreignKeys = $this->getForeignKeys($class);

            foreach ($foreignKeys as $table => $objectIdColumn) {
                try {
                    $tableSchema = $schema->getTable($table);
                } catch (SchemaException $e) {
                    continue;
                }

                $this->createForeignKey($tableSchema, $objectIdColumn);
            }
        }

        $this->createForeignKey($schema->getTable('object_url_slugs'), 'objectId');
    }

    public function down(Schema $schema): void
    {
        $list = new DataObject\ClassDefinition\Listing();

        foreach ($list as $class) {
            $foreignKeys = $this->getForeignKeys($class);

            foreach ($foreignKeys as $table => $objectIdColumn) {
                try {
                    $tableSchema = $schema->getTable($table);
                } catch (SchemaException $e) {
                    continue;
                }

                $fkName = AbstractDao::getForeignKeyName($table, $objectIdColumn);
                if ($tableSchema->hasForeignKey($fkName)) {
                    $tableSchema->removeForeignKey($fkName);
                }
            }
        }
    }

    /**
     * @return string[]
     */
    private function getForeignKeys(DataObject\ClassDefinitionInterface $class): array
    {
        $foreignKeys = [
            'object_query_'.$class->getId() => 'oo_id',
            'object_store_'.$class->getId() => 'oo_id',
            'object_relations_'.$class->getId() => 'src_id',
            'object_classificationstore_groups_'.$class->getId() => 'o_id',
            'object_classificationstore_data_'.$class->getId() => 'o_id',
            'object_metadata_'.$class->getId() => 'o_id',
            'object_localized_data_'.$class->getId() => 'ooo_id',
        ];

        foreach (Tool::getValidLanguages() as $language) {
            $foreignKeys['object_localized_query_'.$class->getId().'_'.$language] = 'ooo_id';
        }

        $brickList = new DataObject\Objectbrick\Definition\Listing();
        foreach ($brickList->loadNames() as $brickName) {
            $foreignKeys['object_brick_query_'.$brickName.'_'.$class->getId()] = 'o_id';
            $foreignKeys['object_brick_localized_'.$brickName.'_'.$class->getId()] = 'ooo_id';
            $foreignKeys['object_brick_store_'.$brickName.'_'.$class->getId()] = 'o_id';
        }

        $fieldCollectionList = new DataObject\Fieldcollection\Definition\Listing();
        foreach ($fieldCollectionList->loadNames() as $fieldCollectionName) {
            $foreignKeys['object_collection_'.$fieldCollectionName.'_'.$class->getId()] = 'o_id';
            $foreignKeys['object_collection_'.$fieldCollectionName.'_localized_'.$class->getId()] = 'ooo_id';
        }

        return $foreignKeys;
    }

    private function createForeignKey(Table $tableSchema, string $localForeignKeyColumn): void
    {
        $fkName = AbstractDao::getForeignKeyName($tableSchema->getName(), $localForeignKeyColumn);
        if (!$tableSchema->hasForeignKey($fkName)) {
            $column = $tableSchema->getColumn($localForeignKeyColumn);

            if ($column->getPrecision() !== 10 || !($column->getType() instanceof IntegerType)) {
                $tableSchema->modifyColumn($localForeignKeyColumn, [
                    'type' => new IntegerType(),
                    'precision' => 10,
                    'unsigned' => true,
                ]);
            }

            if (!$column->hasPlatformOption('unsigned') || $column->getPlatformOption('unsigned') === false) {
                $tableSchema->modifyColumn($localForeignKeyColumn, ['unsigned' => true]);
            }

            $tableSchema->addForeignKeyConstraint('objects', [$localForeignKeyColumn], ['o_id'], ['onDelete' => 'CASCADE'], $fkName);
        }
    }
}
