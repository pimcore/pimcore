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
use Pimcore\Log\Handler\ApplicationLoggerDb;

final class Version20220908113752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames Application Logger DB tables from prefix_MM_YYYY to prefix_YYYY_MM';
    }

    public function up(Schema $schema): void
    {
        foreach ($schema->getTables() as $table) {
            if (str_starts_with($table->getName(), ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX)) {
                preg_match("/(\d{2})_(\d{4})$/", $table->getName(), $matches);
                $month = $matches[1];
                $year = $matches[2];
                $newName = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . "_{$year}_{$month}";

                $this->write("Renaming {$table->getName()} to {$newName}");

                $this->addSql("RENAME TABLE {$table->getName()} TO {$newName};");
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($schema->getTables() as $table) {
            if (str_starts_with($table->getName(), ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX)) {
                preg_match("/(\d{4})_(\d{2})$/", $table->getName(), $matches);
                $year = $matches[1];
                $month = $matches[2];
                $oldName = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . "_{$month}_{$year}";

                $this->write("Restoring {$table->getName()} to {$oldName}");

                $this->addSql("RENAME TABLE {$table->getName()} TO {$oldName};");
            }
        }
    }
}
