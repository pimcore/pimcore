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
    private const SUBDIRECTORY = 'system_settings';

    private array $defaultConfig = [
        'pimcore' => [
            'general' => [
                'language' => 'en',
            ],
        ],
    ];

    protected Filesystem $filesystem;

    public function __construct(array $defaultConfig = null)
    {
        if (null !== $defaultConfig) {
            $this->defaultConfig = $defaultConfig;
        }

        $this->filesystem = new Filesystem();
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
