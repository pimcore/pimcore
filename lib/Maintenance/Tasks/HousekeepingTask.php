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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @internal
 */
class HousekeepingTask implements TaskInterface
{
    protected int $tmpFileTime;

    protected int $profilerTime;

    public function __construct(int $tmpFileTime, int $profilerTime)
    {
        $this->tmpFileTime = $tmpFileTime;
        $this->profilerTime = $profilerTime;
    }

    public function execute(): void
    {
        foreach (['dev'] as $environment) {
            $profilerDir = sprintf('%s/%s/profiler', PIMCORE_SYMFONY_CACHE_DIRECTORY, $environment);

            $this->deleteFilesInFolderOlderThanSeconds($profilerDir, $this->profilerTime, true);
        }

        $this->deleteFilesInFolderOlderThanSeconds(PIMCORE_SYSTEM_TEMP_DIRECTORY, $this->tmpFileTime, false);
    }

    private function deleteFilesInFolderOlderThanSeconds(string $folder, int $seconds, bool $clearFolder): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $cutoff = time() - $seconds;
        $dirTimes = [];

        $directory = new RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current, $key, $iterator) use ($cutoff, $clearFolder, &$dirTimes) {
            if (str_contains($current->getFilename(), '-low-quality-preview.svg') && $current->isFile()) {
                return false;
            }

            if ($current->isFile()) {
                $aTime = $current->getATime();
                $mTime = $current->getMTime();
                $timeToCheck = $aTime ?: $mTime;

                if ($timeToCheck && $timeToCheck < $cutoff) {
                    return true;
                }

                return false;
            }

            if ($clearFolder) {
                $path = $current->getPathname();
                // Capture the mtime before this directory's contents are deleted, so a
                // directory that was already stale can be removed in the same run. The
                // filter may run more than once per entry; keep the first (pre-deletion)
                // value. stat() is served from the cache warmed by isFile() above.
                if (!array_key_exists($path, $dirTimes)) {
                    $stat = @stat($path);
                    $dirTimes[$path] = $stat ? ($stat['mtime'] ?: $stat['ctime']) : false;
                }
            }

            return true;
        });

        $mode = $clearFolder ? RecursiveIteratorIterator::CHILD_FIRST : RecursiveIteratorIterator::LEAVES_ONLY;
        $iterator = new RecursiveIteratorIterator($filter, $mode);

        foreach ($iterator as $entry) {
            $path = $entry->getPathname();

            if (isset($dirTimes[$path])) {
                if ($dirTimes[$path] && $dirTimes[$path] < $cutoff) {
                    // rmdir() is atomic: the kernel checks emptiness and removes in one
                    // operation, avoiding the TOCTOU race of a separate is_dir_empty() call.
                    @rmdir($path);
                }
            } else {
                @unlink($path);
            }
        }
    }
}
