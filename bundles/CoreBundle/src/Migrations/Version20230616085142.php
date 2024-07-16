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
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Dao\AbstractDao;

final class Version20230616085142 extends AbstractMigration
{
    private const ID_COLUMN = 'id';

    private const O_PREFIX = 'o_';

    private const PK_COLUMNS = '`' . self::ID_COLUMN .
        '`,`dest_id`, `type`, `fieldname`, `column`, `ownertype`, `ownername`, `position`, `index`';

    private const UNIQUE_KEY_NAME = 'metadata_un';

    private const AUTO_ID = 'auto_id';

    public function getDescription(): string
    {
        return 'Migrate object_metadata schema to have a auto increment column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET foreign_key_checks = 0');

        $metaDataTables = $this->connection->fetchAllAssociative(
            "SHOW FULL TABLES
                   WHERE `Tables_in_{$this->connection->getDatabase()}`
                    LIKE 'object_metadata_%' AND Table_type = 'BASE TABLE'"
        );

        foreach ($metaDataTables as $table) {
            $tableName = current($table);
            $metaDataTable = $schema->getTable($tableName);
            $foreignKeyName = AbstractDao::getForeignKeyName($tableName, self::ID_COLUMN);
            $foreignKeyNameWithOPrefix = AbstractDao::getForeignKeyName($tableName, self::O_PREFIX . self::ID_COLUMN);

            if (!$metaDataTable->hasColumn(self::AUTO_ID)) {
                if ($recreateForeignKey = $metaDataTable->hasForeignKey($foreignKeyName)) {
                    $this->addSql('ALTER TABLE `' . $tableName . '` DROP FOREIGN KEY `' . $foreignKeyName . '`');
                } elseif ($recreateForeignKey = $metaDataTable->hasForeignKey($foreignKeyNameWithOPrefix)) {
                    $this->addSql('ALTER TABLE `' . $tableName . '` DROP FOREIGN KEY `' . $foreignKeyNameWithOPrefix . '`');
                }

                if ($metaDataTable->getPrimaryKey()) {
                    $this->addSql('ALTER TABLE `' . $tableName . '` DROP PRIMARY KEY');
                }

                $this->addSql('ALTER TABLE ' . $tableName . ' ADD `' . self::AUTO_ID .
                    '` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');

                if (!$metaDataTable->hasIndex(self::UNIQUE_KEY_NAME)) {
                    $this->addSql(
                        'ALTER TABLE `' . $tableName . '`
                            ADD CONSTRAINT `' . self::UNIQUE_KEY_NAME . '`
                            UNIQUE (' . self::PK_COLUMNS . ')'
                    );
                }

                if ($recreateForeignKey) {
                    $this->addSql(
                        'ALTER TABLE `' . $tableName . '`
                            ADD CONSTRAINT `'.$foreignKeyName.'`
                            FOREIGN KEY (`' . self::ID_COLUMN . '`)
                            REFERENCES `objects` (`' . self::ID_COLUMN . '`)
                            ON UPDATE NO ACTION
                            ON DELETE CASCADE;'
                    );
                }
            }
        }

        $this->addSql('SET foreign_key_checks = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET foreign_key_checks = 0');

        $metaDataTables = $this->connection->fetchAllAssociative(
            "SHOW FULL TABLES
                   WHERE `Tables_in_{$this->connection->getDatabase()}`
                    LIKE 'object_metadata_%' AND Table_type = 'BASE TABLE'"
        );

        foreach ($metaDataTables as $table) {
            $tableName = current($table);
            $metaDataTable = $schema->getTable($tableName);
            $foreignKeyName = AbstractDao::getForeignKeyName($tableName, self::ID_COLUMN);

            if ($metaDataTable->hasColumn(self::AUTO_ID)) {
                if ($recreateForeignKey = $metaDataTable->hasForeignKey($foreignKeyName)) {
                    $this->addSql('ALTER TABLE `' . $tableName . '` DROP FOREIGN KEY `' . $foreignKeyName . '`');
                }

                $this->addSql('ALTER TABLE `' . $tableName . '` DROP COLUMN `' . self::AUTO_ID . '`');
                $this->addSql(
                    'ALTER TABLE `' . $tableName . '` ADD PRIMARY KEY (' . self::PK_COLUMNS  . ')'
                );

                if ($metaDataTable->hasIndex(self::UNIQUE_KEY_NAME)) {
                    $this->addSql(
                        'ALTER TABLE `' . $tableName . '` DROP INDEX `' . self::UNIQUE_KEY_NAME . '`'
                    );
                }

                if ($recreateForeignKey) {
                    $this->addSql(
                        'ALTER TABLE `' . $tableName . '`
                            ADD CONSTRAINT `'.$foreignKeyName.'`
                            FOREIGN KEY (`' . self::ID_COLUMN . '`)
                            REFERENCES `objects` (`' . self::ID_COLUMN . '`)
                            ON UPDATE RESTRICT
                            ON DELETE CASCADE;'
                    );
                }
            }
        }

        $this->addSql('SET foreign_key_checks = 1');
    }
}
