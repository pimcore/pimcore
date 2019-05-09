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
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->deleteFilesInFolderOlderThanDays(PIMCORE_TEMPORARY_DIRECTORY, 90);

        $environments = Config::getEnvironmentConfig()->getProfilerHousekeepingEnvironments();

        foreach ($environments as $environment) {
            $profilerDir = sprintf('%s/cache/%s/profiler', PIMCORE_PRIVATE_VAR, $environment);

            $this->deleteFilesInFolderOlderThanDays($profilerDir, 4);
        }
    }

    /**
     * @param $folder
     * @param $days
     */
    protected function deleteFilesInFolderOlderThanDays($folder, $days)
    {
        if (!is_dir($folder)) {
            return;
        }

        $directory = new \RecursiveDirectoryIterator($folder);
        $filter = new \RecursiveCallbackFilterIterator($directory, function (\SplFileInfo $current, $key, $iterator) use ($days) {
            if ($current->isFile()) {
                if ($current->getATime() && $current->getATime() < (time() - ($days * 86400))) {
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
