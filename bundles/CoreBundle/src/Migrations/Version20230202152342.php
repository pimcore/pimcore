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
use Pimcore\Config\ReportConfigWriter;
use Pimcore\Model\Tool\SettingsStore;

final class Version20230202152342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate site data from "default" id to "0"';
    }

    public function up(Schema $schema): void
    {
        //update robots.txt default site data
        $this->addSql("UPDATE `settings_store` SET id = 'robots.txt-0' WHERE id = 'robots.txt-default' AND scope = 'robots.txt'");

        //update default site data in marketing settings
        $settings = SettingsStore::get(ReportConfigWriter::REPORT_SETTING_ID, ReportConfigWriter::REPORT_SETTING_SCOPE);
        if (!$settings) {
            return;
        }
        $data = json_decode($settings->getData(), true);

        $marketingScopes = ['analytics', 'google_search_console', 'tagmanager'];
        foreach ($marketingScopes as $scope) {
            if (isset($data[$scope]['sites']['default'])) {
                $data[$scope]['sites'][0] = $data[$scope]['sites']['default'];
                unset($data[$scope]['sites']['default']);
            }
        }

        SettingsStore::set(
            ReportConfigWriter::REPORT_SETTING_ID,
            json_encode($data),
            SettingsStore::TYPE_STRING,
            ReportConfigWriter::REPORT_SETTING_SCOPE
        );
    }

    public function down(Schema $schema): void
    {
        //revert robots.txt default site data
        $this->addSql("UPDATE `settings_store` SET id = 'robots.txt-default' WHERE id = 'robots.txt-0' AND scope = 'robots.txt'");

        //revert default site in marketing settings
        $settings = SettingsStore::get(ReportConfigWriter::REPORT_SETTING_ID, ReportConfigWriter::REPORT_SETTING_SCOPE);
        $data = json_decode($settings->getData(), true);

        $marketingScopes = ['analytics', 'google_search_console', 'tagmanager'];
        foreach ($marketingScopes as $scope) {
            if (isset($data[$scope]['sites'][0])) {
                $data[$scope]['sites']['default'] = $data[$scope]['sites'][0];
                unset($data[$scope]['sites'][0]);
            }
        }

        SettingsStore::set(
            ReportConfigWriter::REPORT_SETTING_ID,
            json_encode($data),
            SettingsStore::TYPE_STRING,
            ReportConfigWriter::REPORT_SETTING_SCOPE
        );
    }
}
