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

/**
 * Create the `telemetry_spool` durable outbox table used by server-side product telemetry.
 *
 * The table is also created lazily at runtime (CREATE TABLE IF NOT EXISTS) so telemetry works before
 * this migration runs; the statements here are idempotent (IF NOT EXISTS) and simply make the schema
 * an explicit, versioned artifact for clean installs.
 */
final class Version20260720120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the telemetry_spool outbox table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS `telemetry_spool` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `event_uid` VARCHAR(36) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `payload` LONGTEXT NOT NULL,
                `claimed_at` DATETIME NULL DEFAULT NULL,
                `claim_nonce` VARCHAR(32) NULL DEFAULT NULL,
                `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_telemetry_spool_event_uid` (`event_uid`),
                KEY `idx_telemetry_spool_claim_nonce` (`claim_nonce`),
                KEY `idx_telemetry_spool_created_at` (`created_at`)
            ) DEFAULT CHARSET=utf8mb4'
        );

        // The table may already exist from the lazy runtime create before `attempts` was introduced.
        // Guarded via schema introspection because `ADD COLUMN IF NOT EXISTS` is MariaDB-only.
        if ($schema->hasTable('telemetry_spool') && !$schema->getTable('telemetry_spool')->hasColumn('attempts')) {
            $this->addSql(
                'ALTER TABLE `telemetry_spool` ADD COLUMN `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `telemetry_spool`');
    }
}
