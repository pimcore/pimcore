<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Symfony\Component\Yaml\Yaml;
use Pimcore\File;

class Version20190527121800 extends AbstractPimcoreMigration
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
        $configFile = $this->migrateSystemConfiguration();

        if($configFile) {
            $this->migrateDb($configFile);
            $this->migrateBranding($configFile);
            $this->migrateEmail($configFile);
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }

    public function migrateSystemConfiguration()
    {
        try{
            $originalConfigFile = PIMCORE_CONFIGURATION_DIRECTORY . '/system.php';
            $newConfigFile = PIMCORE_CONFIGURATION_DIRECTORY . '/system.yml';

            if (is_file($originalConfigFile)) {
                //write new system.yml file in /var/config
                $content['pimcore'] = include($originalConfigFile);

                $content = Yaml::dump($content, 6);
                File::put($newConfigFile, $content);


                if (file_exists($newConfigFile)) {
                    $this->writeMessage('Please cleanup "system.php" file from directory '.PIMCORE_CONFIGURATION_DIRECTORY.' manually as system settings migrated to new "system.yml" file.');

                    return $newConfigFile;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing system configuration migration: ' . $e->getMessage());
        }
    }

    /**
     * Migrate database configuration from system.yml to /app/config/local/database.yml
     *
     * @param $systemConfigFile
     * @return bool
     */
    public function migrateDb($systemConfigFile)
    {
        try{
            $databaseFilePath = PIMCORE_APP_ROOT . '/config/local/database.yml';

            $systemConfigContent = Yaml::parseFile($systemConfigFile);

            if(isset($systemConfigContent['pimcore']['database']) && isset($systemConfigContent['pimcore']['database']['params'])) {
                //change username key to user
                $systemConfigContent['pimcore']['database']['params']['user'] = $systemConfigContent['pimcore']['database']['params']['username'];
                unset($systemConfigContent['pimcore']['database']['params']['username']);

                $content['doctrine']['dbal']['connections']['default'] = $systemConfigContent['pimcore']['database']['params'];

                $content = Yaml::dump($content,6);
                File::put($databaseFilePath, $content);

                unset($systemConfigContent['pimcore']['database']);

                $settingsYml = Yaml::dump($systemConfigContent, 4);

                File::put($systemConfigFile, $settingsYml);
            }

            return true;
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing system configuration migration: ' . $e->getMessage());
        }
    }

    /**
     * Migrate 'Appearance & Branding' configuration from 'pimcore' node to 'pimcore_admin' node in system.yml
     *
     * @param $systemConfigFile
     * @return bool
     */
    public function migrateBranding($systemConfigFile)
    {
        try {
            $settings = Yaml::parseFile($systemConfigFile);
            $migrate = false;

            if(isset($settings['pimcore']['branding'])) {
                $settings['pimcore_admin']['branding'] = $settings['pimcore']['branding'];
                unset($settings['pimcore']['branding']);
                $migrate = true;
            }

            if(isset($settings['pimcore']['general']['loginscreencustomimage'])) {
                $settings['pimcore_admin']['branding']['loginscreencustomimage'] = $settings['pimcore']['general']['loginscreencustomimage'];
                unset($settings['pimcore']['general']['loginscreencustomimage']);
                $migrate = true;
            }

            if($migrate) {
                $settings = Yaml::dump($settings,6);
                File::put($systemConfigFile, $settings);
            }
            return true;
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing system configuration migration: ' . $e->getMessage());
        }
    }

    /**
     * Migrate email & newletter smtp configuration from 'pimcore' node to 'swiftmailer' node in system.yml
     *
     * @param $systemConfigFile
     * @return bool
     */
    public function migrateEmail($systemConfigFile)
    {
        try {
            $systemSettings = Yaml::parseFile($systemConfigFile);

            if (isset($systemSettings['pimcore']['email'])) {
                $settings = [
                    'swiftmailer' => [
                        'mailers' => [
                            'pimcore_mailer' => [
                                'transport' => '%pimcore_system_config.email.method%',
                                'delivery_addresses' => '%pimcore_system_config.email.debug.emailaddresses%',
                                'host' => '%pimcore_system_config.email.smtp.host%',
                                'username' => '%pimcore_system_config.email.smtp.auth.username%',
                                'password' => '%pimcore_system_config.email.smtp.auth.password%',
                                'port' => '%pimcore_system_config.email.smtp.port%',
                                'encryption' => '%pimcore_system_config.email.smtp.ssl%',
                                'auth_mode' => '%pimcore_system_config.email.smtp.auth.method%'
                            ],
                            'newsletter_mailer' => [
                                'transport' => '%pimcore_system_config.newsletter.method%',
                                'delivery_addresses' => '%pimcore_system_config.email.debug.emailaddresses%',
                                'host' => '%pimcore_system_config.newsletter.smtp.host%',
                                'username' => '%pimcore_system_config.newsletter.smtp.auth.username%',
                                'password' => '%pimcore_system_config.newsletter.smtp.auth.password%',
                                'port' => '%pimcore_system_config.newsletter.smtp.port%',
                                'encryption' => '%pimcore_system_config.newsletter.smtp.ssl%',
                                'auth_mode' => '%pimcore_system_config.newsletter.smtp.auth.method%'
                            ],
                        ],
                    ],
                ];

                foreach ( $settings['swiftmailer']['mailers'] as $mkey => $mailer) {
                    foreach ($mailer as $ckey => $configuration) {
                        $emailSettings = $systemSettings;
                        $keys = explode(".", str_replace('pimcore_system_config','pimcore',trim($configuration,'%')));

                        for ($i = 0; $i < count($keys); $i++) {
                            if(isset($emailSettings[$keys[$i]])) {
                                $emailSettings = $emailSettings[$keys[$i]];
                            } else {
                                $emailSettings = null;
                                break;
                            }
                        }
                        $settings['swiftmailer']['mailers'][$mkey][$ckey] = $emailSettings;
                    }
                }

                unset($systemSettings['pimcore']['email']['smtp']);
                unset($systemSettings['pimcore']['newsletter']['smtp']);

                if(!empty($settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses'])) {
                    $settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses'] = [$settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses']];
                } else {
                    $settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses'] = [];
                }

                if(!empty($settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses'])) {
                    $settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses'] = [$settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses']];
                } else {
                    $settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses'] = [];
                }


                $systemConfigFileContent = array_merge($systemSettings, $settings);

                $content = Yaml::dump($systemConfigFileContent,6);
                File::put($systemConfigFile, $content);
            }

            return true;
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing system configuration migration: ' . $e->getMessage());
        }

    }
}
