<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Config;
use Pimcore\File;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\Site;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190124105627 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $siteList = new Site\Listing();
        $siteList->load();

        $robotFiles = [];

        $defaultRobotsPath = PIMCORE_CONFIGURATION_DIRECTORY.'/robots-default.txt';

        if (file_exists($defaultRobotsPath)) {
            $robotFiles['default'] = file_get_contents($defaultRobotsPath);
        }

        foreach ($siteList->getSites() as $site) {
            if (!$site instanceof Site) {
                continue;
            }

            $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY.'/robots-'.$site->getId().'.txt';

            if (file_exists($robotsPath)) {
                $robotFiles[$site->getId()] = file_get_contents($robotsPath);
            }
        }

        File::putPhpFile(
            Config::locateConfigFile('robots.php'),
            to_php_data_file_format($robotFiles)
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $configPath = Config::locateConfigFile('robots.php');

        if (!file_exists($configPath)) {
            return;
        }

        $content = include($configPath);

        if (!is_array($content)) {
            $content = [];
        }

        $config = new Config\Config($content);
        $config = $config->toArray();

        foreach ($config as $siteId => $robotsContent) {
            $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY.'/robots-'.$siteId.'.txt';

            file_put_contents($robotsPath, $robotsContent);
        }
    }
}
