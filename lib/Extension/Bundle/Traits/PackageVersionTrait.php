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

namespace Pimcore\Extension\Bundle\Traits;

use Composer\InstalledVersions;
use Pimcore\Composer\PackageInfo;

/**
 * Exposes a simple getVersion() and getComposerPackageName() implementation by looking up the installed versions
 * via composer's version info which is generated on composer install.
 */
trait PackageVersionTrait
{
    /**
     * Returns the composer package name used to resolve the version
     */
    public function getComposerPackageName(): string
    {
        foreach (InstalledVersions::getAllRawData() as $installed) {
            foreach ($installed['versions'] as $packageName => $packageInfo) {
                if (!isset($packageInfo['install_path'])) {
                    // It's a replaced or provided (virtual) package
                    continue;
                }

                if (str_starts_with(__DIR__, realpath($packageInfo['install_path']))) {
                    return $packageName;
                }
            }
        }

        return '';
    }

    public function getVersion(): string
    {
        $version = InstalledVersions::getPrettyVersion($this->getComposerPackageName());

        // normalizes e.g. 'v2.3.0' to '2.3.0'
        $version = preg_replace('/^v/', '', $version);

        return $version;
    }

    public function getDescription(): string
    {
        $packageInfo = new PackageInfo();

        foreach ($packageInfo->getInstalledPackages('pimcore-bundle') as $bundle) {
            if ($bundle['name'] === $this->getComposerPackageName()) {
                return $bundle['description'] ?? '';
            }
        }

        return '';
    }
}
