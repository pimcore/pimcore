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
use Pimcore\Db;
use Pimcore\Model\Dao\AbstractDao;

final class Version20230616085142 extends AbstractMigration
{
    private const ID_COLUMN = 'o_id';
    private const PK_COLUMNS = [
        self::ID_COLUMN, 'dest_id', 'type', 'fieldname', 'column', 'ownertype', 'ownername', 'position', 'index'];
    private const UNIQUE_INDEX_NAME = 'metadata_un';
    private const AUTO_ID = 'id';

    public function getDescription(): string
    {
        return 'Migrate object_metadata schema to have a auto increment column';
    }

    public function up(Schema $schema): void
    {
        $db = Db::get();

        $metaDataTables = $db->fetchAllAssociative(
                "SHOW FULL TABLES
                       WHERE `Tables_in_{$db->getDatabase()}` LIKE 'object_metadata_%' AND Table_type = 'BASE TABLE'"
            );
        foreach ($metaDataTables as $table) {
            $tableName = current($table);
            $metaDataTable = $schema->getTable($tableName);

            if (!$metaDataTable->hasColumn(self::AUTO_ID)) {
                $metaDataTable->addColumn(self::AUTO_ID, 'integer', [
                    'autoincrement' => true,
                ]);

                $fkName = AbstractDao::getForeignKeyName($tableName, self::ID_COLUMN);
                $metaDataTable->removeForeignKey($fkName);
                $metaDataTable->dropPrimaryKey();
                $metaDataTable->setPrimaryKey([self::AUTO_ID]);
                $metaDataTable->addUniqueIndex(self::PK_COLUMNS, self::UNIQUE_INDEX_NAME);

                $metaDataTable->addForeignKeyConstraint(
                    'objects',
                    [self::ID_COLUMN],
                    [self::ID_COLUMN],
                    ["onDelete" => "CASCADE"],
                    $fkName
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $db = Db::get();

        $metaDataTables = $db->fetchAllAssociative(
            "SHOW FULL TABLES
                       WHERE `Tables_in_{$db->getDatabase()}` LIKE 'object_metadata_%' AND Table_type = 'BASE TABLE'"
        );
        foreach ($metaDataTables as $table) {
            $metaDataTable = $schema->getTable(current($table));

            if ($metaDataTable->hasColumn(self::AUTO_ID)) {
                $metaDataTable->dropPrimaryKey();
                $metaDataTable->dropColumn(self::AUTO_ID);
                $metaDataTable->setPrimaryKey(self::PK_COLUMNS);
                $metaDataTable->dropIndex(self::UNIQUE_INDEX_NAME);
            }
        }

    }
}
