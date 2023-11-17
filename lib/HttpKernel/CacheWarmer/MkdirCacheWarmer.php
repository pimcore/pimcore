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

namespace Pimcore\HttpKernel\CacheWarmer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Creates needed pimcore directories when warming up the cache
 *
 * @internal
 */
class MkdirCacheWarmer implements CacheWarmerInterface
{
    private int $mode;

    public function __construct(int $mode = 0775)
    {
        $this->mode = $mode;
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir): array
    {
        $directories = [
            // var
            PIMCORE_CLASS_DIRECTORY,
            PIMCORE_CONFIGURATION_DIRECTORY,
            PIMCORE_LOG_DIRECTORY,
            PIMCORE_SYSTEM_TEMP_DIRECTORY,
        ];

        // Since #12392, PIMCORE_CLASS_DEFINITION_WRITABLE = 0 doesn't allow creation in var/classes but is allowed when not set or 1.
        if (true == ($_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE'] ?? true)) {
            $directories[] = PIMCORE_CLASS_DEFINITION_DIRECTORY;
        }

        $fs = new Filesystem();
        foreach (array_unique($directories) as $directory) {
            if (!$fs->exists($directory)) {
                $fs->mkdir($directory, $this->mode);
            }
        }

        return [];
    }
}
