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
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject;

final class Version20211018104331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $list = new DataObject\ClassDefinition\Listing();

        foreach ($list as $class) {
            $foreignKeys = $this->getForeignKeys($class);

            foreach($foreignKeys as $table => $objectIdColumn) {
                try {
                    $tableSchema = $schema->getTable($table);
                } catch(SchemaException $e) {
                    continue;
                }

                $fkName = AbstractDao::getForeignKeyName($table, $objectIdColumn);
                if (!$tableSchema->hasForeignKey($fkName)) {
                    $column = $tableSchema->getColumn($objectIdColumn);
                    if(!$column->hasCustomSchemaOption('unsigned') || $column->getCustomSchemaOption('unsigned') === false) {
                        $tableSchema->changeColumn($objectIdColumn, ['unsigned' => true]);
                    }

                    $tableSchema->addForeignKeyConstraint('objects', [$objectIdColumn], ['o_id'], ['onDelete' => 'CASCADE'], $fkName);
                }
            }
        }
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
     * @param DataObject\ClassDefinition $class
     * @return string[]
     */
    private function getForeignKeys(DataObject\ClassDefinition $class): array
    {
        $foreignKeys = [
            'object_query_'.$class->getId() => 'oo_id',
            'object_store_'.$class->getId() => 'oo_id',
            'object_relations_'.$class->getId() => 'src_id',
            'object_classificationstore_groups_'.$class->getId() => 'o_id',
            'object_classificationstore_data_'.$class->getId() => 'o_id',
            'object_metadata_'.$class->getId() => 'o_id',
            'object_localized_data_'.$class->getId() => 'ooo_id'
        ];

        $brickList = new DataObject\Objectbrick\Definition\Listing();
        foreach ($brickList->load() as $brickDefinition) {
            $foreignKeys['object_brick_query_'.$brickDefinition->getKey().'_'.$class->getId()] = 'o_id';
            $foreignKeys['object_brick_localized_'.$brickDefinition->getKey().'_'.$class->getId()] = 'ooo_id';
        }

        $fieldCollectionList = new DataObject\Fieldcollection\Definition\Listing();
        foreach ($fieldCollectionList->load() as $fieldCollectionDefinition) {
            $foreignKeys['object_collection_'.$fieldCollectionDefinition->getKey().'_'.$class->getId()] = 'o_id';
            $foreignKeys['object_collection_'.$fieldCollectionDefinition->getKey().'_localized_'.$class->getId()] = 'ooo_id';
        }
        return $foreignKeys;
    }
}
