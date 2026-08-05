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

namespace Pimcore\Composer;

use JsonException;
use RuntimeException;

/**
 * @internal
 */
class PackageInfo
{
    private ?array $installedPackages = null;

    /**
     * Gets installed packages, optionally filtered by type
     *
     *
     */
    public function getInstalledPackages(array|string|null $type = null): array
    {
        $packages = $this->readInstalledPackages();

        if (null !== $type) {
            if (!is_array($type)) {
                $type = [$type];
            }

            $packages = array_filter($packages, static fn (array $package) => in_array($package['type'], $type, true));
        }

        return $packages;
    }

    private function readInstalledPackages(): array
    {
        if (null !== $this->installedPackages) {
            return $this->installedPackages;
        }

        $json = $this->readComposerFile(PIMCORE_COMPOSER_PATH . '/composer/installed.json');
        if ($json) {
            return $this->installedPackages = $json['packages'] ?? $json;
        }

        return $this->installedPackages = [];
    }

    private function readComposerFile(string $path): ?array
    {
        if (is_file($path) && is_readable($path)) {
            try {
                return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException(sprintf('Failed to parse composer file %s', $path), previous: $e);
            }
        }

        return null;
    }
}
