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

namespace Pimcore\Helper;

use InvalidArgumentException;

final class EncoreHelper
{
    public static function getBuildPathsFromEntrypoints(string $entrypointsFile, string $type = 'js'): array
    {
        if (!file_exists($entrypointsFile)) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $entrypointsFile));
        }

        $entrypointsContent = file_get_contents($entrypointsFile);
        $entrypoints = json_decode($entrypointsContent, true, flags: JSON_THROW_ON_ERROR)['entrypoints'];

        $paths = [];
        foreach ($entrypoints as $entrypoint) {
            $paths[] = $entrypoint[$type] ?? [];
        }

        return array_merge(...$paths);
    }
}
