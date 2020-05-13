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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Config;
use Pimcore\Maintenance\TaskInterface;

final class HousekeepingTask implements TaskInterface
{
    /**
     * @var int
     */
    protected $tmpFileTime;

    /**
     * @var int
     */
    protected $profilerTime;

    /**
     * @param int $tmpFileTime
     * @param int $profilerTime
     */
    public function __construct(int $tmpFileTime, int $profilerTime)
    {
        $this->tmpFileTime = $tmpFileTime;
        $this->profilerTime = $profilerTime;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->deleteFilesInFolderOlderThanSeconds(PIMCORE_TEMPORARY_DIRECTORY, $this->tmpFileTime);

        $environments = Config::getEnvironmentConfig()->getProfilerHousekeepingEnvironments();

        foreach ($environments as $environment) {
            $profilerDir = sprintf('%s/%s/profiler', PIMCORE_SYMFONY_CACHE_DIRECTORY, $environment);

            $this->deleteFilesInFolderOlderThanSeconds($profilerDir, $this->profilerTime);
        }
    }

    /**
     * @param string $folder
     * @param int $seconds
     */
    protected function deleteFilesInFolderOlderThanSeconds($folder, $seconds)
    {
        if (!is_dir($folder)) {
            return;
        }

        $directory = new \RecursiveDirectoryIterator($folder);
        $filter = new \RecursiveCallbackFilterIterator($directory, function (\SplFileInfo $current, $key, $iterator) use ($seconds) {
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
