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

namespace Pimcore\Install\SystemConfig;

use Pimcore\File;

class ConfigWriter
{
    /**
     * @var array
     */
    private $defaultConfig = [
        'general' => [
            'timezone' => 'Europe/Berlin',
            'language' => 'en',
            'validLanguages' => 'en',
        ],
        'database' => [
            'params' => [
                'username' => 'root',
                'password' => '',
                'dbname' => '',
            ]
        ],
        'documents' => [
            'versions' => [
                'steps' => '10'
            ],
            'default_controller' => 'default',
            'default_action' => 'default',
            'error_pages' => [
                'default' => '/'
            ],
            'createredirectwhenmoved' => '',
            'allowtrailingslash' => 'no',
            'generatepreview' => '1'
        ],
        'objects' => [
            'versions' => [
                'steps' => '10'
            ]
        ],
        'assets' => [
            'versions' => [
                'steps' => '10'
            ]
        ],
        'services' => [],
        'cache' => [
            'excludeCookie' => ''
        ],
        'httpclient' => [
            'adapter' => 'Socket'
        ],
        'email' => [
            'sender' => [
                'name' => '',
                'email' => ''
            ],
            'return' => [
                'name' => '',
                'email' => ''
            ],
            'method' => 'mail',
            'smtp' => [
                'host' => '',
                'port' => '',
                'ssl' => null,
                'name' => '',
                'auth' => [
                    'method' => null,
                    'username' => '',
                    'password' => ''
                ]
            ],
            'debug' => [
                'emailaddresses' => ''
            ]
        ],
        'newsletter' => [
            'sender' => [
                'name' => '',
                'email' => ''
            ],
            'return' => [
                'name' => '',
                'email' => ''
            ],
            'method' => 'mail',
            'smtp' => [
                'host' => '',
                'port' => '',
                'ssl' => null,
                'name' => '',
                'auth' => [
                    'method' => null,
                    'username' => '',
                    'password' => ''
                ]
            ],
            'usespecific' => ''
        ]
    ];

    public function __construct(array $defaultConfig = null)
    {
        if (null !== $defaultConfig) {
            $this->defaultConfig = $defaultConfig;
        }
    }

    public function writeSystemConfig(array $config = [])
    {
        $settings = null;

        // check for an initial configuration template
        // used eg. by the demo installer
        $configTemplatePaths = [
            PIMCORE_CONFIGURATION_DIRECTORY . '/system.php',
            PIMCORE_CONFIGURATION_DIRECTORY . '/system.template.php'
        ];

        foreach ($configTemplatePaths as $configTemplatePath) {
            if (!file_exists($configTemplatePath)) {
                continue;
            }

            try {
                $configTemplateArray = include($configTemplatePath);

                if (!is_array($configTemplateArray)) {
                    continue;
                }

                $configTemplate = new \Pimcore\Config\Config($configTemplateArray);
                if ($configTemplate->general) { // check if the template contains a valid configuration
                    $settings = $configTemplate->toArray();

                    // unset database configuration
                    unset($settings['database']['params']['host']);
                    unset($settings['database']['params']['port']);

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

        $settings = array_replace_recursive($settings, $config);

        $configFile = \Pimcore\Config::locateConfigFile('system.php');
        File::putPhpFile($configFile, to_php_data_file_format($settings));
    }

    public function writeDebugModeConfig($ip = '')
    {
        File::putPhpFile(PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php', to_php_data_file_format([
            'active' => true,
            'ip' => '',
        ]));
    }

    public function generateParametersFile(string $secret = null)
    {
        if (null === $secret) {
            $secret = generateRandomSymfonySecret();
        }

        // generate parameters.yml
        $parametersFilePath = PIMCORE_APP_ROOT . '/config/parameters.yml';
        if (file_exists($parametersFilePath)) {
            return;
        }

        $parameters = file_get_contents(PIMCORE_APP_ROOT . '/config/parameters.example.yml');
        $parameters = str_replace('ThisTokenIsNotSoSecretChangeIt', $secret, $parameters);

        File::put($parametersFilePath, $parameters);
    }
}
