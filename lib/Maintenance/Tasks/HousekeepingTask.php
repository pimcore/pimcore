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

use FilesystemIterator;
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

        // Prune empty directories too: without $clearFolder the system temp tree only ever
        // grows, because rmdir() is never reached. Directories are held for at least a week
        // so that a still-in-use working directory is not pulled out from under a request.
        $this->deleteFilesInFolderOlderThanSeconds(
            PIMCORE_SYSTEM_TEMP_DIRECTORY,
            $this->tmpFileTime,
            true,
            max($this->tmpFileTime, 7 * 86400)
        );
    }

    private function deleteFilesInFolderOlderThanSeconds(string $folder, int $seconds, bool $clearFolder, ?int $dirSeconds = null): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $now = time();
        $cutoff = $now - $seconds;
        $dirCutoff = $now - ($dirSeconds ?? $seconds);
        $dirTimes = [];

        $directory = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
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
                // Capture the directory time before its contents are deleted, so a
                // directory that was already stale can be removed in the same run. The
                // filter may run more than once per entry; keep the first (pre-deletion)
                // value. stat() is served from the cache warmed by isFile() above.
                //
                // max(mtime, ctime) is deliberate: mtime moves when an entry is added or
                // removed, ctime when the inode changes (rename, chmod, link count). atime
                // is not used, because on some stacks a plain readdir() bumps it and every
                // directory this task walks would become immortal.
                if (!array_key_exists($path, $dirTimes)) {
                    $stat = @stat($path);
                    $dirTimes[$path] = $stat ? max($stat['mtime'], $stat['ctime']) : false;
                }
            }

            return true;
        });

        $mode = $clearFolder ? RecursiveIteratorIterator::CHILD_FIRST : RecursiveIteratorIterator::LEAVES_ONLY;
        // CATCH_GET_CHILD: a directory can vanish between the parent's readdir and the
        // attempt to descend into it - either because this run just removed it, or
        // because another node did. On NFS the parent's cached listing makes that
        // routine. Without this flag the UnexpectedValueException aborts the whole
        // walk and the job dies partway; with it, the subtree is skipped and picked
        // up on the next run.
        $iterator = new RecursiveIteratorIterator($filter, $mode, RecursiveIteratorIterator::CATCH_GET_CHILD);

        foreach ($iterator as $entry) {
            $path = $entry->getPathname();

            if (array_key_exists($path, $dirTimes)) {
                $dirTime = $dirTimes[$path];
                // Drop the entry as soon as it is consumed. CHILD_FIRST only yields a
                // directory once its whole subtree has been walked, so the live set stays
                // proportional to the tree depth instead of the tree size (~25MB at 95k
                // directories).
                unset($dirTimes[$path]);

                if ($dirTime && $dirTime < $dirCutoff) {
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
