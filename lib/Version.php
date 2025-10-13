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

namespace Pimcore;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * @internal
 */
final class Version
{
    const PACKAGE_NAME = 'pimcore/pimcore';

    private const PLATFORM_VERSION_PACKAGE_NAME = 'pimcore/platform-version';

    private const MAJOR_VERSION = 12;

    public static function getMajorVersion(): int
    {
        return self::MAJOR_VERSION;
    }

    public static function getVersion(): string
    {
        return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);
    }

    public static function getRevision(): string
    {
        return InstalledVersions::getReference(self::PACKAGE_NAME);
    }

    public static function getPlatformVersion(): ?string
    {
        try {
            return InstalledVersions::getPrettyVersion(self::PLATFORM_VERSION_PACKAGE_NAME);
        } catch (OutOfBoundsException $e) {
            return null;
        }
    }
}
