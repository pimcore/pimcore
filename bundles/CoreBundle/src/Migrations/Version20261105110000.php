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
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Dao\AbstractDao;

/**
 * Adds the per-class store of the per-relation column values of the advanced relation pair, beside
 * the document table of every class that already has one.
 *
 * The classes are discovered from the document tables themselves rather than from the class
 * definitions: a class whose Classification Store field is on the document engine has that table,
 * and reading the definitions would make a schema migration depend on the definition files being
 * in the state the database is in.
 *
 * A class saved after this migration gets the table from
 * {@see \Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine::createUpdateTables()},
 * which is also what creates it for a class that is flipped to the document engine later.
 */
final class Version20261105110000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const DOCUMENT_TABLE_PREFIX = 'object_classificationstore_doc_';

    private const METADATA_TABLE_PREFIX = 'object_classificationstore_meta_';

    public function getDescription(): string
    {
        return 'Add the per-class Classification Store relation metadata tables';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->getClassIds() as $classId) {
            $table = self::METADATA_TABLE_PREFIX . $classId;

            if ($schema->hasTable($table)) {
                continue;
            }

            $this->addSql(
                'CREATE TABLE `' . $table . '` ('
                . '`id` INT(11) UNSIGNED NOT NULL, '
                . '`fieldname` VARCHAR(70) NOT NULL, '
                . '`groupId` INT(11) UNSIGNED NOT NULL, '
                . '`keyId` BIGINT(20) NOT NULL, '
                . '`language` VARCHAR(10) NOT NULL, '
                . '`index` INT(11) UNSIGNED NOT NULL DEFAULT \'0\', '
                . '`column` VARCHAR(190) NOT NULL, '
                . '`data` LONGTEXT NULL, '
                . 'PRIMARY KEY (`id`, `fieldname`, `groupId`, `keyId`, `language`, `index`, `column`), '
                . 'CONSTRAINT `' . AbstractDao::getForeignKeyName($table, 'id') . '` FOREIGN KEY (`id`)'
                . ' REFERENCES `objects` (`id`) ON DELETE CASCADE, '
                . 'CONSTRAINT `' . AbstractDao::getForeignKeyName($table, 'groupId') . '` FOREIGN KEY (`groupId`)'
                . ' REFERENCES `classificationstore_groups` (`id`) ON DELETE CASCADE'
                . ') DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci'
            );
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    public function down(Schema $schema): void
    {
        foreach ($this->getClassIds() as $classId) {
            $table = self::METADATA_TABLE_PREFIX . $classId;

            if ($schema->hasTable($table)) {
                $this->addSql('DROP TABLE `' . $table . '`');
            }
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    /**
     * The ids of the classes holding Classification Store documents, read off their document tables.
     *
     * @return string[]
     */
    private function getClassIds(): array
    {
        $tables = $this->connection->fetchFirstColumn(
            'SELECT TABLE_NAME FROM information_schema.TABLES'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE ?',
            [self::DOCUMENT_TABLE_PREFIX . '%']
        );

        return array_map(
            static fn (mixed $table): string => substr((string) $table, strlen(self::DOCUMENT_TABLE_PREFIX)),
            $tables
        );
    }

    /**
     * The column list of a table is memoized per table name, and a table that has just appeared or
     * gone must not be answered from an entry written before that.
     */
    private function resetValidTableColumnsCache(): void
    {
        $runtimeCache = RuntimeCache::getInstance();

        foreach (array_keys($runtimeCache->getArrayCopy()) as $cacheKey) {
            if (str_starts_with((string) $cacheKey, self::CACHEKEY . self::METADATA_TABLE_PREFIX)) {
                $runtimeCache->offsetUnset($cacheKey);
            }
        }

        Cache::clearTags(['system', 'resource']);
    }
}
