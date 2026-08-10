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

        $directory = new RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current, $key, $iterator) use ($cutoff) {
            if (strpos($current->getFilename(), '-low-quality-preview.svg') !== false) {
                // do not delete low quality image previews
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

            return true;
        });

        $iterator = new RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }

        if ($clearFolder) {
            $dirIterator = new RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
            $dirWalker = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($dirWalker as $entry) {
                if (!$entry->isDir()) {
                    continue;
                }

                $dirPath = $entry->getPathname();

                if ($dirPath === $folder) {
                    continue;
                }

                $stat = @stat($dirPath);
                $dirTime = $stat ? ($stat['mtime'] ?: $stat['ctime']) : false;

                if ($dirTime && $dirTime < $cutoff) {
                    // rmdir() is atomic: the kernel checks emptiness and removes in one
                    // operation, avoiding the TOCTOU race of a separate is_dir_empty() call.
                    @rmdir($dirPath);
                }
            }
        }
    }
}
