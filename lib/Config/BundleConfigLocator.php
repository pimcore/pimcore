<?php

declare(strict_types = 1);

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

namespace Pimcore\Config;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 *
 * Locates configs from bundles if Resources/config/pimcore exists.
 *
 * Will first try to locate <name>_<environment>.<suffix> and fall back to <name>.<suffix> if the
 * environment specific lookup didn't find anything. All known suffixes are searched, so e.g. if a config.yaml
 * and a config.php exist, both will be used.
 *
 * Example: lookup for config will try to locate the following files from every bundle (will return all files it finds):
 *
 *  - Resources/config/pimcore/config_dev.php
 *  - Resources/config/pimcore/config_dev.yaml
 *  - Resources/config/pimcore/config_dev.xml
 *
 * If the previous lookup didn't return any results, it will fall back to:
 *
 *  - Resources/config/pimcore/config.php
 *  - Resources/config/pimcore/config.yaml
 *  - Resources/config/pimcore/config.xml
 */
class BundleConfigLocator
{
    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Find config files for the given name (e.g. config)
     *
     *
     */
    public function locate(string $name): array
    {
        $result = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $bundlePath = $bundle->getPath();
            if (!is_dir($dir = $bundlePath.'/Resources/config/pimcore') && !is_dir($dir = $bundlePath.'/config/pimcore')) {
                continue;
            }

            // try to find environment specific file first, fall back to generic one if none found (e.g. config_dev.yaml > config.yaml)
            $finder = $this->buildContainerConfigFinder($name, $dir, true);
            if (!$finder->hasResults()) {
                $finder = $this->buildContainerConfigFinder($name, $dir, false);
            }

            foreach ($finder as $file) {
                $result[] = $file->getRealPath();
            }
        }

        return $result;
    }

    private function buildContainerConfigFinder(string $name, string $directory, bool $includeEnvironment = false): Finder
    {
        if ($includeEnvironment) {
            $name .= '_' . $this->kernel->getEnvironment();
        }

        $finder = new Finder();
        $finder->in($directory);

        foreach (['php', 'yml', 'yaml', 'xml'] as $extension) {
            $finder->name(sprintf('%s.%s', $name, $extension));
        }

        return $finder;
    }
}
