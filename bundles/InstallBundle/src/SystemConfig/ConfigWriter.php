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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\InstallBundle\SystemConfig;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class ConfigWriter
{
    private array $defaultConfig = [
        'pimcore' => [
            'general' => [
                'language' => 'en',
            ],
        ],
    ];

    protected Filesystem $filesystem;

    public function __construct(array $defaultConfig = null) {
        if (null !== $defaultConfig) {
            $this->defaultConfig = $defaultConfig;
        }

        $this->filesystem = new Filesystem();
    }

    public function writeSystemConfig(): void
    {
        $settings = null;

        // check for an initial configuration template
        // used eg. by the demo installer
        $configTemplatePaths = [
            PIMCORE_CONFIGURATION_DIRECTORY . '/system.yaml',
            PIMCORE_CONFIGURATION_DIRECTORY . '/system.template.yaml',
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

                if (isset($configTemplateArray['pimcore']['general'])) { // check if the template contains a valid configuration
                    $settings = $configTemplateArray;

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

        $configFile = \Pimcore\Config::locateConfigFile('system.yaml');
        $settingsYml = Yaml::dump($settings, 5);
        $this->filesystem->dumpFile($configFile, $settingsYml);
    }

    public function writeDbConfig(array $config = []): void
    {
        if (count($config)) {
            $content = Yaml::dump($config);
            $configFile = PIMCORE_PROJECT_ROOT .'/config/local/database.yaml';
            $this->filesystem->dumpFile($configFile, $content);
        }
    }
}
