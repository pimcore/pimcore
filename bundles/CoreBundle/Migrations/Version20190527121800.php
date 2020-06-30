<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\File;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Symfony\Component\Yaml\Yaml;

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

        if ($configFile) {
            $this->migrateBranding($configFile);
            $this->migrateEmail($configFile);
            $this->migrateDevmode($configFile);
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
        try {
            $originalConfigFile = \Pimcore\Config::locateConfigFile('system.php');
            $newConfigFile = str_replace('system.php', 'system.yml', $originalConfigFile);

            if (is_file($originalConfigFile)) {
                //write new system.yml file in /var/config
                $content['pimcore'] = include($originalConfigFile);

                //cleanup unused config
                unset($content['pimcore']['outputfilters']);
                unset($content['pimcore']['database']);

                self::standardizeSystemConfigKeys($content);

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
     * @param array $config
     */
    public static function standardizeSystemConfigKeys(&$config)
    {
        //convert system config keys to follow snake_case standard
        $validKeys = [
            'validLanguages' => 'valid_languages',
            'fallbackLanguages' => 'fallback_languages',
            'disableusagestatistics' => 'disable_usage_statistics',
            'instanceIdentifier' => 'instance_identifier',
            'defaultLanguage' => 'default_language',
            'createredirectwhenmoved' => 'create_redirect_when_moved',
            'allowtrailingslash' => 'allow_trailing_slash',
            'generatepreview' => 'generate_preview',
            'defaultUploadPath' => 'default_upload_path',
            'simpleapikey' => 'simple_api_key',
            'browserapikey' => 'browser_api_key',
            'excludePatterns' => 'exclude_patterns',
            'excludeCookie' => 'exclude_cookie',
            'usespecific' => 'use_specific',
            'loginscreencustomimage' => 'login_screen_custom_image',
            'emailaddresses' => 'email_addresses',
        ];

        foreach ($config as $key => &$value) {
            if (isset($validKeys[$key])) {
                $config[$validKeys[$key]] = $value;
                unset($config[$key]);
            }

            if (is_array($value) && array_values($value) !== $value) {
                self::standardizeSystemConfigKeys($value);
            }
        }
    }

    /**
     * Migrate 'Appearance & Branding' configuration from 'pimcore' node to 'pimcore_admin' node in system.yml
     *
     * @param string $systemConfigFile
     *
     * @return bool
     */
    public function migrateBranding($systemConfigFile)
    {
        try {
            $settings = Yaml::parseFile($systemConfigFile);
            $migrate = false;

            if (isset($settings['pimcore']['branding'])) {
                $settings['pimcore_admin']['branding'] = $settings['pimcore']['branding'];
                unset($settings['pimcore']['branding']);
                $migrate = true;
            }

            if (isset($settings['pimcore']['general']['login_screen_custom_image'])) {
                $settings['pimcore_admin']['branding']['login_screen_custom_image'] = $settings['pimcore']['general']['login_screen_custom_image'];
                unset($settings['pimcore']['general']['login_screen_custom_image']);
                $migrate = true;
            }

            if ($migrate) {
                $settings = Yaml::dump($settings, 6);
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
     * @param string $systemConfigFile
     *
     * @return bool
     */
    public function migrateEmail($systemConfigFile)
    {
        try {
            $systemSettings = Yaml::parseFile($systemConfigFile);

            //update transport method from mail to sendmail in email & newsletter settings
            if (isset($systemSettings['pimcore']['email']['method']) && $systemSettings['pimcore']['email']['method'] == 'mail') {
                $systemSettings['pimcore']['email']['method'] = 'sendmail';
            }

            if (isset($systemSettings['pimcore']['newsletter']['method']) && $systemSettings['pimcore']['newsletter']['method'] == 'mail') {
                $systemSettings['pimcore']['newsletter']['method'] = 'sendmail';
            }

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
                                'auth_mode' => '%pimcore_system_config.email.smtp.auth.method%',
                            ],
                            'newsletter_mailer' => [
                                'transport' => '%pimcore_system_config.newsletter.method%',
                                'delivery_addresses' => '%pimcore_system_config.email.debug.emailaddresses%',
                                'host' => '%pimcore_system_config.newsletter.smtp.host%',
                                'username' => '%pimcore_system_config.newsletter.smtp.auth.username%',
                                'password' => '%pimcore_system_config.newsletter.smtp.auth.password%',
                                'port' => '%pimcore_system_config.newsletter.smtp.port%',
                                'encryption' => '%pimcore_system_config.newsletter.smtp.ssl%',
                                'auth_mode' => '%pimcore_system_config.newsletter.smtp.auth.method%',
                            ],
                        ],
                    ],
                ];

                foreach ($settings['swiftmailer']['mailers'] as $mkey => $mailer) {
                    foreach ($mailer as $ckey => $configuration) {
                        $emailSettings = $systemSettings;
                        $keys = explode('.', str_replace('pimcore_system_config', 'pimcore', trim($configuration, '%')));

                        for ($i = 0; $i < count($keys); $i++) {
                            if (isset($emailSettings[$keys[$i]])) {
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

                if (!empty($settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses'])) {
                    $settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses'] = [$settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses']];
                } else {
                    $settings['swiftmailer']['mailers']['pimcore_mailer']['delivery_addresses'] = [];
                }

                if (!empty($settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses'])) {
                    $settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses'] = [$settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses']];
                } else {
                    $settings['swiftmailer']['mailers']['newsletter_mailer']['delivery_addresses'] = [];
                }

                $systemConfigFileContent = array_merge($systemSettings, $settings);

                $content = Yaml::dump($systemConfigFileContent, 6);
                File::put($systemConfigFile, $content);
            }

            return true;
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing system configuration migration: ' . $e->getMessage());
        }
    }

    public function migrateDevmode($systemConfigFile)
    {
        try {
            $systemSettings = Yaml::parseFile($systemConfigFile);
            $debugModeFile = PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php';

            //move devmode setting from system configuration tree to debug-mode.php
            if (file_exists($debugModeFile)) {
                $debugConf = include $debugModeFile;

                if (is_array($debugConf)) {
                    $debugConf['devmode'] = $systemSettings['pimcore']['general']['devmode'];
                    File::putPhpFile($debugModeFile, to_php_data_file_format($debugConf));

                    unset($systemSettings['pimcore']['general']['devmode']);
                    $settings = Yaml::dump($systemSettings, 6);
                    File::put($systemConfigFile, $settings);
                }
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing system configuration migration: ' . $e->getMessage());
        }
    }
}
