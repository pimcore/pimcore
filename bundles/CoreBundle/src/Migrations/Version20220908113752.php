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
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;

final class Version20220908113752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames Application Logger DB tables from prefix_MM_YYYY to prefix_YYYY_MM';
    }

    public function up(Schema $schema): void
    {
        $tableList = $this->connection->fetchAllAssociative("SHOW TABLES LIKE '" . ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . "%'");
        foreach ($tableList as $table) {
            $tableName = current($table);
            preg_match("/(\d{2})_(\d{4})$/", $tableName, $matches);
            $month = $matches[1];
            $year = $matches[2];
            $newName = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . "_{$year}_{$month}";

            $this->write("Renaming {$tableName} to {$newName}");

            $this->addSql("RENAME TABLE {$tableName} TO {$newName};");
        }
    }

    public function down(Schema $schema): void
    {
        $tableList = $this->connection->fetchAllAssociative("SHOW TABLES LIKE '" . ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . "%'");
        foreach ($tableList as $table) {
            $tableName = current($table);
            preg_match("/(\d{4})_(\d{2})$/", $tableName, $matches);
            $year = $matches[1];
            $month = $matches[2];
            $oldName = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . "_{$month}_{$year}";

            $this->write("Restoring {$tableName} to {$oldName}");

            $this->addSql("RENAME TABLE {$tableName} TO {$oldName};");
        }
    }
}
