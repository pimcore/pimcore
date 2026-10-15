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

final class Version20261015100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the single-column cid and ctype indexes on schedule_tasks with a composite (cid, ctype) index';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('schedule_tasks');
        $changes = [];

        if (!$table->hasIndex('cid_ctype')) {
            $changes[] = 'ADD INDEX `cid_ctype` (`cid`, `ctype`)';
        }
        foreach (['cid', 'ctype'] as $index) {
            if ($table->hasIndex($index)) {
                $changes[] = sprintf('DROP INDEX `%s`', $index);
            }
        }

        $this->alterTable($changes);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('schedule_tasks');
        $changes = [];

        foreach (['cid', 'ctype'] as $index) {
            if (!$table->hasIndex($index)) {
                $changes[] = sprintf('ADD INDEX `%s` (`%s`)', $index, $index);
            }
        }
        if ($table->hasIndex('cid_ctype')) {
            $changes[] = 'DROP INDEX `cid_ctype`';
        }

        $this->alterTable($changes);
    }

    /**
     * Applies all index changes in one ALTER TABLE statement, so that the table is never without
     * an index for lookups by cid and ctype while the migration runs.
     *
     * @param string[] $changes
     */
    private function alterTable(array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $this->addSql('ALTER TABLE `schedule_tasks` ' . implode(', ', $changes));
    }
}
