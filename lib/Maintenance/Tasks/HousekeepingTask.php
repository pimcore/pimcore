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

        $directory = new RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current, $key, $iterator) use ($seconds) {
            if (strpos($current->getFilename(), '-low-quality-preview.svg') !== false) {
                // do not delete low quality image previews
                return false;
            }

            if ($current->isFile()) {
                $aTime = $current->getATime();
                $mTime = $current->getMTime();
                $timeToCheck = $aTime ?: $mTime;

                if ($timeToCheck && $timeToCheck < (time() - $seconds)) {
                    return true;
                }
                return false;
            }

            return true;
        });

        $iterator = new RecursiveIteratorIterator($filter);

        $dirTimes = [];

        foreach ($iterator as $file) {
            /**
             * @var SplFileInfo $file
             */
            if ($file->isFile()) {
                if ($clearFolder) {
                    $dirPath = $file->getPath();
                    if (!array_key_exists($dirPath, $dirTimes)) {
                        $stat = @stat($dirPath);
                        $dirTimes[$dirPath] = $stat ? ($stat['mtime'] ?: $stat['ctime']) : false;
                    }
                }

                @unlink($file->getPathname());
            }
        }

        if ($clearFolder && $dirTimes !== []) {
            $dirPaths = array_keys($dirTimes);
            // Remove deepest directories first so parents aren't deleted while they still contain children.
            usort($dirPaths, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

            foreach ($dirPaths as $dirPath) {
                $dirTime = $dirTimes[$dirPath];
                if ($dirTime && $dirTime < (time() - $seconds) && is_dir_empty($dirPath)) {
                    @rmdir($dirPath);
                }
            }
        }
    }
}
