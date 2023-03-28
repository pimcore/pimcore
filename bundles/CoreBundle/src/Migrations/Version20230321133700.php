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

final class Version20230321133700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alters date time columns to timestamp columns for application logs, notifications and scheduled tasks. Converts values to UTC.';
    }

    private function converToTimeZone(
        Schema $schema,
        string $table,
        string $timeStampColumn,
        bool $up = true
    ): void {
        if ($schema->hasTable($table)) {
            $db = \Pimcore\Db::get();
            $fromTimeZone = $up ? date_default_timezone_get() : 'UTC';
            $toTimeZone = $up ? 'UTC' : date_default_timezone_get();

            $this->addSql(
                sprintf(
                    'update %s set %s = CONVERT_TZ(%s,%s,%s)',
                    $db->quoteIdentifier($table),
                    $db->quoteIdentifier($timeStampColumn),
                    $db->quoteIdentifier($timeStampColumn),
                    $db->quote($fromTimeZone),
                    $db->quote($toTimeZone)
                )
            );
        }
    }

    public function up(Schema $schema): void
    {
        $this->converToTimeZone(
            $schema,
            'application_logs',
            'timestamp'
        );

        $this->converToTimeZone(
            $schema,
            'notifications',
            'creationDate'
        );

        $this->converToTimeZone(
            $schema,
            'notifications',
            'modificationDate'
        );
    }

    public function down(Schema $schema): void
    {
        $this->converToTimeZone(
            $schema,
            'application_logs',
            'timestamp',
            false
        );

        $this->converToTimeZone(
            $schema,
            'notifications',
            'creationDate',
            false
        );

        $this->converToTimeZone(
            $schema,
            'notifications',
            'modificationDate',
            false
        );
    }
}
