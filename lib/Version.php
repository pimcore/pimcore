<?php
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

namespace Pimcore;

use PackageVersions\Versions;

class Version
{
    const PART_NUMBER = 0;
    const PART_HASH = 1;

    /**
     * @return string
     */
    public static function getVersion()
    {
        $version = self::getVersionPart(self::PART_NUMBER);

        return $version;
    }

    protected static function getVersionPart($part = self::PART_NUMBER)
    {
        $parts = explode('@', Versions::getVersion('pimcore/pimcore'));

        return $parts[$part];
    }

    /**
     * @return int
     */
    public static function getRevision()
    {
        $hash = self::getVersionPart(self::PART_HASH);

        return $hash;
    }
}
