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

    private const MAJOR_VERSION = 11;

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
