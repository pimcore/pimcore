<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;

final class Version20240323121349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key constraints for quantity value unit columns: clean up orphaned references, fix column types and rebuild definitions';
    }

    public function up(Schema $schema): void
    {
        // Step 1: Find all __unit columns across object tables and clean up orphaned references
        $this->write('Cleaning up orphaned unit references and fixing column types...');

        $unitColumns = $this->connection->fetchAllAssociative(
            "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND COLUMN_NAME LIKE '%\\_\\_unit'
               AND TABLE_NAME REGEXP '^(object_store_|object_query_|object_localized_data_|object_localized_query_|object_brick_store_|object_brick_query_|object_brick_localized_|object_collection_)'"
        );

        foreach ($unitColumns as $col) {
            $tableName = $col['TABLE_NAME'];
            $columnName = $col['COLUMN_NAME'];
            $columnType = strtolower($col['COLUMN_TYPE']);

            // Step 1a: Clean up orphaned unit references — set to NULL if the unit no longer exists
            $this->addSql(
                sprintf(
                    'UPDATE `%s` SET `%s` = NULL WHERE `%s` IS NOT NULL AND `%s` NOT IN (SELECT `id` FROM `quantityvalue_units`)',
                    $tableName,
                    $columnName,
                    $columnName,
                    $columnName
                )
            );

            // Step 1b: Fix VARCHAR mismatch — change varchar(64) to varchar(50) to match quantityvalue_units.id
            if ($columnType === 'varchar(64)') {
                $this->addSql(
                    sprintf(
                        'ALTER TABLE `%s` MODIFY `%s` varchar(50) DEFAULT NULL',
                        $tableName,
                        $columnName
                    )
                );
                $this->write(sprintf('  Fixed column type: %s.%s (varchar(64) -> varchar(50))', $tableName, $columnName));
            }
        }

        // Step 2: Rebuild all class definitions, object bricks and field collections
        // This triggers ensureForeignKeys() which adds the FK constraints
        try {
            $classDefinitions = new DataObject\ClassDefinition\Listing();
            foreach ($classDefinitions->load() as $classDefinition) {
                $this->write(sprintf('Saving class: %s', $classDefinition->getName()));
                $classDefinition->save();
            }

            $objectBricks = new DataObject\Objectbrick\Definition\Listing();
            foreach ($objectBricks->load() as $brickDefinition) {
                $this->write(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
                $brickDefinition->save();
            }

            $fieldCollections = new DataObject\Fieldcollection\Definition\Listing();
            foreach ($fieldCollections->load() as $fieldCollection) {
                $this->write(sprintf('Saving field collection: %s', $fieldCollection->getKey()));
                $fieldCollection->save();
            }
        } catch (DefinitionWriteException $e) {
            $this->write(
                'Could not write class definition file.' . "\n" .
                'Possible causes:' . "\n" .
                '- If using symfony-config write target: Configs are read-only in production mode (non-debug). ' .
                'Temporarily enable debug mode or switch to an alternate config-storage to run this migration.' . "\n" .
                '- Missing write permissions: Set PIMCORE_CLASS_DEFINITION_WRITABLE env variable.' . "\n" .
                sprintf(
                    'If definitions are already migrated, skip this migration: ' . "\n" .
                    '  "php bin/console doctrine:migrations:version --add %s"',
                    __CLASS__
                )
            );

            throw $e;
        }
    }

    public function down(Schema $schema): void
    {
        $this->write(
            sprintf(
                'Please restore your class definition files in %s and run
                    bin/console pimcore:deployment:classes-rebuild manually.',
                PIMCORE_CLASS_DEFINITION_DIRECTORY
            )
        );
    }
}
