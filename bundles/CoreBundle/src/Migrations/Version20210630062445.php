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
use Pimcore\Config;
use Pimcore\Config\ReportConfigWriter;
use Pimcore\Model\Tool\SettingsStore;

final class Version20210630062445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $file = Config::locateConfigFile('reports.php');

        if (!file_exists($file)) {
            return;
        }

        $config = Config::getConfigInstance($file);

        SettingsStore::set(
            ReportConfigWriter::REPORT_SETTING_ID,
            json_encode($config),
            SettingsStore::TYPE_STRING,
            ReportConfigWriter::REPORT_SETTING_SCOPE
        );
    }

    public function down(Schema $schema): void
    {
        $reportSettings = SettingsStore::get(
            ReportConfigWriter::REPORT_SETTING_ID, ReportConfigWriter::REPORT_SETTING_SCOPE
        );
        SettingsStore::delete($reportSettings->getId(), ReportConfigWriter::REPORT_SETTING_SCOPE);
    }
}
