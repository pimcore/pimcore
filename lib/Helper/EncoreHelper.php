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

namespace Pimcore\Helper;

final class EncoreHelper
{
    public static function getBuildPathsFromEntrypoints(string $entrypointsFile, string $type = 'js'): array
    {
        $entrypointsContent = file_get_contents($entrypointsFile);
        $entrypointsJson = json_decode($entrypointsContent, true)['entrypoints'];
        $entrypoints = array_keys($entrypointsJson);

        $paths = [];
        foreach ($entrypoints as $entrypoint) {
            $paths = array_merge($paths, $entrypointsJson[$entrypoint][$type] ?? []);
        }

        return $paths;
    }
}
