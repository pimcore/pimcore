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

namespace Pimcore\Tool;

class Housekeeping
{
    /**
     * @param int $lastAccessGreaterThanDays
     */
    public static function cleanupTmpFiles($lastAccessGreaterThanDays = 90)
    {
        if(!is_dir(PIMCORE_TEMPORARY_DIRECTORY)) {
            return;
        }

        $directory = new \RecursiveDirectoryIterator(PIMCORE_TEMPORARY_DIRECTORY);
        $filter = new \RecursiveCallbackFilterIterator($directory, function (\SplFileInfo $current, $key, $iterator) use ($lastAccessGreaterThanDays) {
            if ($current->isFile()) {
                if ($current->getATime() < (time() - ($lastAccessGreaterThanDays * 86400))) {
                    return true;
                }
            } else {
                return true;
            }

            return false;
        });

        $iterator = new \RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            /**
             * @var \SplFileInfo $file
             */
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }

            if (is_dir_empty($file->getPath())) {
                @rmdir($file->getPath());
            }
        }
    }
}
