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

            $this->deleteFilesInFolderOlderThanSeconds($profilerDir, $this->profilerTime);
        }
    }

    private function deleteFilesInFolderOlderThanSeconds(string $folder, int $seconds): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $directory = new RecursiveDirectoryIterator($folder);
        $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current, $key, $iterator) use ($seconds) {
            if (strpos($current->getFilename(), '-low-quality-preview.svg')) {
                // do not delete low quality image previews
                return false;
            }

            if ($current->isFile()) {
                if ($current->getATime() && $current->getATime() < (time() - $seconds)) {
                    return true;
                }
            } else {
                return true;
            }

            return false;
        });

        $iterator = new RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            /**
             * @var SplFileInfo $file
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
