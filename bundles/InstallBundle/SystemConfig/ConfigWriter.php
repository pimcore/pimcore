<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\InstallBundle\SystemConfig;

use Pimcore\File;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class ConfigWriter
{
    /**
     * @var array
     */
    private $defaultConfig = [
        'pimcore' => [
            'general' => [
                'timezone' => 'Europe/Berlin',
                'language' => 'en',
                'valid_languages' => 'en',
            ],
            'documents' => [
                'versions' => [
                    'steps' => '10',
                ],
                'error_pages' => [
                    'default' => '/',
                ],
                'allow_trailing_slash' => 'no',
                'generate_preview' => false,
            ],
            'objects' => [
                'versions' => [
                    'steps' => '10',
                ],
            ],
            'assets' => [
                'versions' => [
                    'steps' => '10',
                ],
            ],
            'services' => [],
            'full_page_cache' => [
                'exclude_cookie' => '',
            ],
            'httpclient' => [
                'adapter' => 'Socket',
            ],
            'email' => [
                'sender' => [
                    'name' => '',
                    'email' => '',
                ],
                'return' => [
                    'name' => '',
                    'email' => '',
                ],
                'method' => 'sendmail',
                'debug' => [
                    'email_addresses' => '',
                ],
            ],
            'newsletter' => [
                'sender' => [
                    'name' => '',
                    'email' => '',
                ],
                'return' => [
                    'name' => '',
                    'email' => '',
                ],
                'method' => 'sendmail',
                'use_specific' => false,
            ],
        ],
        'pimcore_admin' => [
            'branding' => [
                'color_login_screen' => '',
                'color_admin_interface' => '',
                'login_screen_custom_image' => '',
            ],
        ],
    ];

    public function __construct(array $defaultConfig = null)
    {
        if (null !== $defaultConfig) {
            $this->defaultConfig = $defaultConfig;
        }
    }

    public function writeSystemConfig()
    {
        $settings = null;

        // check for an initial configuration template
        // used eg. by the demo installer
        $configTemplatePaths = [
            PIMCORE_CONFIGURATION_DIRECTORY . '/system.yml',
            PIMCORE_CONFIGURATION_DIRECTORY . '/system.template.yml',
        ];

        foreach ($configTemplatePaths as $configTemplatePath) {
            if (!file_exists($configTemplatePath)) {
                continue;
            }

            try {
                $configTemplateArray = Yaml::parseFile($configTemplatePath);

                if (!is_array($configTemplateArray)) {
                    continue;
                }

                $configTemplate = new \Pimcore\Config\Config($configTemplateArray);
                if ($configTemplate->get('pimcore')->general) { // check if the template contains a valid configuration
                    $settings = $configTemplate->toArray();

                    break;
                }
            } catch (\Exception $e) {
            }
        }

        // set default configuration if no template is present
        if (!$settings) {
            // write configuration file
            $settings = $this->defaultConfig;
        }

        $configFile = \Pimcore\Config::locateConfigFile('system.yml');
        $settingsYml = Yaml::dump($settings, 5);
        File::put($configFile, $settingsYml);
    }

    public function writeDbConfig(array $config = [])
    {
        if (count($config)) {
            $content = Yaml::dump($config);
            $configFile = PIMCORE_PROJECT_ROOT .'/config/local/database.yaml';
            File::put($configFile, $content);
        }
    }

    public function writeDebugModeConfig($ip = '')
    {
        File::putPhpFile(PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php', to_php_data_file_format([
            'active' => true,
            'ip' => '',
            'devmode' => false,
        ]));
    }
}
