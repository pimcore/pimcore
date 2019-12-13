<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Config;
use Pimcore\File;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Symfony\Component\Yaml\Yaml;

class Version20191213115045 extends AbstractPimcoreMigration
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
        try {
            $configFile = Config::locateConfigFile('system.yml');
            if (is_file($configFile)) {
                $config = Config::getConfigInstance($configFile, true);

                $offset = array_search("cache", array_keys($config['pimcore']));

                if ($offset) {
                    $config['pimcore']['cache']['enabled'] = false;

                    $config['pimcore'] = array_merge(
                        array_slice($config['pimcore'], 0,  $offset),
                        ["fullpagecache" => $config['pimcore']['cache']],
                        array_slice($config['pimcore'], $offset)
                    );

                    unset($config['pimcore']['cache']);

                    $config = Yaml::dump($config, 6);
                    File::put($configFile, $config);
                }
            }
        } catch (\Exception $e) {
            // nothing to do
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        try {
            $configFile = Config::locateConfigFile('system.yml');
            if (is_file($configFile)) {
                $config = Config::getConfigInstance($configFile, true);

                $offset = array_search("fullpagecache", array_keys($config['pimcore']));

                if ($offset) {
                    $config['pimcore']['fullpagecache']['enabled'] = false;

                    $config['pimcore'] = array_merge(
                        array_slice($config['pimcore'], 0,  $offset),
                        ["cache" => $config['pimcore']['fullpagecache']],
                        array_slice($config['pimcore'], $offset)
                    );

                    unset($config['pimcore']['fullpagecache']);

                    $config = Yaml::dump($config, 6);
                    File::put($configFile, $config);
                }
            }
        } catch (\Exception $e) {
            // nothing to do
        }
    }
}
