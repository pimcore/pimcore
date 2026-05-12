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
 * @internal
 */
final class Version20260504092057 extends AbstractMigration
{
    private const KEYS = [
        'workflow_change_email_notification_text',
        'workflow_change_email_notification_deeplink',
        'workflow_change_email_notification_note',
        'seo_document_editor',
        'robots.txt',
        'reports',
        'gee_job_run_permission',
        'gee_see_all_job_runs_permission',
        'gee_error_no_job_definition',
        'gee_error_missing_message_implementation',
        'gee_job_cancelled',
        'gee_job_failed',
        'gee_middleware_step_condition_not_met',
        'gee_job_started',
        'gee_job_finished',
        'gee_job_finished_with_errors',
        'gee_updated_selected_elements',
    ];

    public function getDescription(): string
    {
        return 'Migrate CoreBundle backend translations from deprecated admin domain to backend domain';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS `translations_backend` (
            `key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
            `type` varchar(10) DEFAULT NULL,
            `language` varchar(10) NOT NULL DEFAULT '',
            `text` text DEFAULT NULL,
            `creationDate` int(11) unsigned DEFAULT NULL,
            `modificationDate` int(11) unsigned DEFAULT NULL,
            `userOwner` int(11) unsigned DEFAULT NULL,
            `userModification` int(11) unsigned DEFAULT NULL,
            PRIMARY KEY (`key`,`language`),
            KEY `language` (`language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci");

        if (empty($this->connection->fetchAllAssociative("SHOW TABLES LIKE 'translations_admin'"))) {
            $this->write('Skipping migration: translations_admin table does not exist.');

            return;
        }

        $inList = "'" . implode("', '", self::KEYS) . "'";

        // Copy all rows (every locale, including ones absent from getValidLanguages()) and preserve type.
        // INSERT IGNORE skips rows whose (key, language) already exist in translations_backend.
        $this->addSql(
            "INSERT IGNORE INTO `translations_backend` (`key`, `type`, `language`, `text`, `creationDate`, `modificationDate`, `userOwner`, `userModification`)
             SELECT `key`, `type`, `language`, `text`, `creationDate`, `modificationDate`, `userOwner`, `userModification`
             FROM `translations_admin`
             WHERE `key` IN ($inList)"
        );

        // For backend rows that already existed but have no text, fill in the admin value and type.
        $this->addSql(
            "UPDATE `translations_backend` tb
             INNER JOIN `translations_admin` ta ON ta.`key` = tb.`key` AND ta.`language` = tb.`language`
             SET tb.`text` = ta.`text`, tb.`type` = ta.`type`
             WHERE tb.`key` IN ($inList)
               AND (tb.`text` IS NULL OR tb.`text` = '')"
        );

        $this->addSql("DELETE FROM `translations_admin` WHERE `key` IN ($inList)");
    }

    public function down(Schema $schema): void
    {
        if (empty($this->connection->fetchAllAssociative("SHOW TABLES LIKE 'translations_admin'"))) {
            $this->write('Skipping revert: translations_admin table does not exist.');

            return;
        }

        $inList = "'" . implode("', '", self::KEYS) . "'";

        $this->addSql(
            "INSERT IGNORE INTO `translations_admin` (`key`, `type`, `language`, `text`, `creationDate`, `modificationDate`, `userOwner`, `userModification`)
             SELECT `key`, `type`, `language`, `text`, `creationDate`, `modificationDate`, `userOwner`, `userModification`
             FROM `translations_backend`
             WHERE `key` IN ($inList)"
        );

        $this->addSql(
            "UPDATE `translations_admin` ta
             INNER JOIN `translations_backend` tb ON tb.`key` = ta.`key` AND tb.`language` = ta.`language`
             SET ta.`text` = tb.`text`, ta.`type` = tb.`type`
             WHERE ta.`key` IN ($inList)
               AND (ta.`text` IS NULL OR ta.`text` = '')"
        );

        $this->addSql("DELETE FROM `translations_backend` WHERE `key` IN ($inList)");
    }
}
