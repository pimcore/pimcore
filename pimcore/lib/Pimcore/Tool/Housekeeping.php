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
        $directory = new \RecursiveDirectoryIterator(PIMCORE_TEMPORARY_DIRECTORY, \FilesystemIterator::FOLLOW_SYMLINKS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) use ($lastAccessGreaterThanDays) {

            // Skip hidden files and directories.
            if ($current->getFilename()[0] === '.' || $current->getFilename()[0] === '..') {
                return false;
            }

            if ($current->isFile()) {
                if ($current->getATime() < (time() - ($lastAccessGreaterThanDays * 86400))) {
                    return true;
                }
            } else {
                return true;
            }
        });
        $iterator = new \RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }
    }
}
