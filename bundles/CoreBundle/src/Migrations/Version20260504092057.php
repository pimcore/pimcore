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
use Pimcore\Model\Translation;

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

        foreach (self::KEYS as $key) {
            $adminTranslation = Translation::getByKey($key, 'admin');
            if (!$adminTranslation) {
                continue;
            }

            $backendTranslation = Translation::getByKey($key, Translation::DOMAIN_BACKEND, true);
            foreach ($adminTranslation->getTranslations() as $locale => $value) {
                if (!$backendTranslation->hasTranslation($locale)) {
                    $backendTranslation->addTranslation($locale, $value);
                }
            }

            $backendTranslation->save();
            $adminTranslation->delete();

            $this->write('Migrated translation: ' . $key);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::KEYS as $key) {
            $backendTranslation = Translation::getByKey($key, Translation::DOMAIN_BACKEND);
            if (!$backendTranslation) {
                continue;
            }

            $adminTranslation = Translation::getByKey($key, 'admin');
            $adminTranslationExists = $adminTranslation !== null;
            if (!$adminTranslationExists) {
                $adminTranslation = Translation::getByKey($key, 'admin', true);
            }

            foreach ($backendTranslation->getTranslations() as $locale => $value) {
                if (!$adminTranslationExists || !$adminTranslation->hasTranslation($locale)) {
                    $adminTranslation->addTranslation($locale, $value);
                }
            }

            $adminTranslation->save();
            $backendTranslation->delete();

            $this->write('Reverted translation: ' . $key);
        }
    }
}
