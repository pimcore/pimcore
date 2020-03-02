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

namespace Pimcore\Composer;

class PackageInfo
{
    /**
     * @var array
     */
    private $installedPackages;

    /**
     * Gets installed packages, optionally filtered by type
     *
     * @param string|array|null $type
     *
     * @return array
     */
    public function getInstalledPackages($type = null): array
    {
        $packages = $this->readInstalledPackages();

        if (null !== $type) {
            if (!is_array($type)) {
                $type = [$type];
            }

            $packages = array_filter($packages, function (array $package) use ($type) {
                return in_array($package['type'], $type);
            });
        }

        return $packages;
    }

    /**
     * @return array
     */
    private function readInstalledPackages(): array
    {
        if (null !== $this->installedPackages) {
            return $this->installedPackages;
        }

        // try to read composer.lock first
        $json = $this->readComposerFile(PIMCORE_PROJECT_ROOT . '/composer.lock');
        if ($json && isset($json['packages']) && is_array($json['packages'])) {
            $this->installedPackages = $json['packages'];
        }

        if (null === $this->installedPackages) {
            // try to read vendor/composer/installed.json as fallback
            $json = $this->readComposerFile(PIMCORE_COMPOSER_PATH . '/composer/installed.json');
            if ($json && is_array($json)) {
                $this->installedPackages = $json;
            }
        }

        if (null === $this->installedPackages) {
            $this->installedPackages = [];
        }

        return $this->installedPackages;
    }

    /**
     * @param string $path
     *
     * @return array|null
     */
    private function readComposerFile(string $path)
    {
        if (file_exists($path) && is_readable($path)) {
            $json = json_decode(file_get_contents($path), true);

            if (null === $json) {
                throw new \RuntimeException(sprintf('Failed to parse composer file %s', $path));
            }

            return $json;
        }

        return null;
    }
}
