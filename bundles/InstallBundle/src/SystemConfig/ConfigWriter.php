<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\SystemConfig;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class ConfigWriter
{
    protected Filesystem $filesystem;

    public function __construct()
    {
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

    public function writeProductRegistrationConfig(
        string $productKey,
        ?string $instanceIdentifier = null,
        ?string $secret = null
    ): void {
        $config = [
            'pimcore' => [
                'product_registration' => [
                    'product_key' => $productKey,
                ],
            ],
        ];

        if ($instanceIdentifier !== null) {
            $config['pimcore']['product_registration']['instance_identifier'] = $instanceIdentifier;
        }
        if ($secret !== null) {
            $config['pimcore']['encryption']['secret'] = $secret;
        }

        $content = Yaml::dump($config);
        $configFile = PIMCORE_PROJECT_ROOT .'/config/local/product_registration.yaml';
        $this->filesystem->dumpFile($configFile, $content);
    }
}
