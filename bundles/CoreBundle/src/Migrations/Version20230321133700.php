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

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;

final class Version20230321133700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Converts datetime/timestamp values to UTC for application logs and notifications.';
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

        // Fallback to PHP-based conversion
        $fromTz = new DateTimeZone($fromTimeZone);
        $toTz = new DateTimeZone($toTimeZone);

        // First pass: distinct timestamps only, as many entries could have the same timestamp
        $sql = sprintf(
            'SELECT DISTINCT %s FROM %s WHERE %s IS NOT NULL',
            $db->quoteIdentifier($timeStampColumn),
            $db->quoteIdentifier($table),
            $db->quoteIdentifier($timeStampColumn)
        );

        $convertedMap = [];
        $collisions = [];
        $collidingResults = [];

        // Stream through distinct timestamps, convert date and check for the collision
        foreach ($db->iterateAssociative($sql) as $row) {
            try {
                $oldValue = $row[$timeStampColumn];
                $dt = new DateTime($oldValue, $fromTz);
                $dt->setTimezone($toTz);
                $newValue = $dt->format('Y-m-d H:i:s');

                //If dates before/after conversion casually match, put aside
                if (
                    in_array($oldValue, $convertedMap, true) ||
                    array_key_exists($newValue, $convertedMap)
                ) {
                    $collisions[$oldValue] = true;
                }
                $convertedMap[$oldValue] = $newValue;

            } catch (Exception $e) {
                // Ignore invalid or unparsable timestamps
            }
        }

        // Handle collisions safely (by ID)
        if (!empty($collisions)) {
            // Prepare the list of colliding timestamps
            $collisionTimestamps = array_keys($collisions);

            // Build placeholders for prepared statement
            $quoted = array_map(fn ($ts) => $db->quote((string)$ts), $collisionTimestamps);
            $collidingTimestamps = implode(',', $quoted);

            $sql = sprintf(
                'SELECT id, %s FROM %s WHERE %s IN (%s)',
                $db->quoteIdentifier($timeStampColumn),
                $db->quoteIdentifier($table),
                $db->quoteIdentifier($timeStampColumn),
                $collidingTimestamps
            );
            $collidingResults = $db->fetchAllAssociative($sql);
        }

        // Batch update for non-colliding timestamps
        foreach ($convertedMap as $old => $new) {
            if (!isset($collisions[$old])) {
                $db->executeStatement(sprintf(
                    'UPDATE %s SET %s = ? WHERE %s = ?',
                    $db->quoteIdentifier($table),
                    $db->quoteIdentifier($timeStampColumn),
                    $db->quoteIdentifier($timeStampColumn)
                ), [$new, $old]);
            }
        }

        // Stream rows that actually need per-ID updates to avoid collision
        foreach ($collidingResults as $row) {
            $db->update(
                $table,
                [$timeStampColumn => $convertedMap[$row[$timeStampColumn]]],
                ['id' => $row['id']]
            );
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
