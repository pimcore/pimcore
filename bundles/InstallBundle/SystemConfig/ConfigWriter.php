<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
                'language' => 'en',
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
}
