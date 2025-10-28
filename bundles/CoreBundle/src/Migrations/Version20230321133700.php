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

    private function convertToTimeZone(
        Schema $schema,
        string $table,
        string $timeStampColumn,
        bool $up = true
    ): void {
        if (!$schema->hasTable($table)) {
            return;
        }

        $db = \Pimcore\Db::get();
        $fromTimeZone = $up ? date_default_timezone_get() : 'UTC';
        $toTimeZone = $up ? 'UTC' : date_default_timezone_get();

        // Test if MySQL CONVERT_TZ works properly
        $testResult = $db->fetchOne(
            "SELECT CONVERT_TZ('2000-01-01 00:00:00', ?, ?)",
            [$fromTimeZone, $toTimeZone]
        );

        $usePhpFallback = !$testResult || $testResult === '0000-00-00 00:00:00';

        if (!$usePhpFallback) {
            $this->addSql(sprintf(
                'UPDATE %s SET %s = CONVERT_TZ(%s, %s, %s)',
                $db->quoteIdentifier($table),
                $db->quoteIdentifier($timeStampColumn),
                $db->quoteIdentifier($timeStampColumn),
                $db->quote($fromTimeZone),
                $db->quote($toTimeZone)
            ));
            return;
        }

        // ⚙️ Fallback to PHP-based conversion
        $rows = $db->fetchAllAssociative(sprintf(
            'SELECT id, %s FROM %s WHERE %s IS NOT NULL',
            $db->quoteIdentifier($timeStampColumn),
            $db->quoteIdentifier($table),
            $db->quoteIdentifier($timeStampColumn)
        ));

        $fromTz = new \DateTimeZone($fromTimeZone);
        $toTz = new \DateTimeZone($toTimeZone);

        foreach ($rows as $row) {
            try {
                $dt = new \DateTime($row[$timeStampColumn], $fromTz);
                $dt->setTimezone($toTz);
                $db->update(
                    $table,
                    [$timeStampColumn => $dt->format('Y-m-d H:i:s')],
                    ['id' => $row['id']]
                );
            } catch (\Exception $e) {
                // Ignore invalid rows gracefully
            }
        }
    }

    public function up(Schema $schema): void
    {
        $this->convertToTimeZone(
            $schema,
            'application_logs',
            'timestamp'
        );

        $this->convertToTimeZone(
            $schema,
            'notifications',
            'creationDate'
        );

        $this->convertToTimeZone(
            $schema,
            'notifications',
            'modificationDate'
        );
    }

    public function down(Schema $schema): void
    {
        $this->convertToTimeZone(
            $schema,
            'application_logs',
            'timestamp',
            false
        );

        $this->convertToTimeZone(
            $schema,
            'notifications',
            'creationDate',
            false
        );

        $this->convertToTimeZone(
            $schema,
            'notifications',
            'modificationDate',
            false
        );
    }
}
