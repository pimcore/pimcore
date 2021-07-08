<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Google\Service\CloudSourceRepositories\Repo;
use Pimcore\Config;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Config\ReportConfigWriter;

final class Version20210630062445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $file = Config::locateConfigFile('reports.php');
        $config = Config::getConfigInstance($file);
        $config = $config->toArray();

        SettingsStore::set(
            ReportConfigWriter::REPORT_SETTING_ID,
            json_encode($config),
            'string',
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
