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
        self::deleteFilesInFolderOlderThanDays(PIMCORE_TEMPORARY_DIRECTORY, $lastAccessGreaterThanDays);
    }

    /**
     * @param int $olderThanDays
     */
    public static function cleanupSymfonyProfilingData($olderThanDays = 4)
    {

        // currently only for the 'dev' environment which has enabled the profiler by default
        $profilerDir = PIMCORE_PRIVATE_VAR . '/cache/dev/profiler';
        self::deleteFilesInFolderOlderThanDays($profilerDir, $olderThanDays);
    }

    /**
     * @param $folder
     * @param $days
     */
    protected static function deleteFilesInFolderOlderThanDays($folder, $days)
    {
        if (!is_dir($folder)) {
            return;
        }

        $directory = new \RecursiveDirectoryIterator($folder);
        $filter = new \RecursiveCallbackFilterIterator($directory, function (\SplFileInfo $current, $key, $iterator) use ($days) {
            if ($current->isFile()) {
                if ($current->getATime() < (time() - ($days * 86400))) {
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
