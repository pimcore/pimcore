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

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Tool\TmpStore;

final class LogCleanupTask implements TaskInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // we don't use the RotatingFileHandler of Monolog, since rotating asynchronously is recommended + compression
        $logFiles = glob(PIMCORE_LOG_DIRECTORY.'/*.log');

        foreach ($logFiles as $log) {
            $tmpStoreTimeId = 'log-'.basename($log);
            $lastTimeItem = TmpStore::get($tmpStoreTimeId);
            if ($lastTimeItem) {
                $lastTime = $lastTimeItem->getData();
            } else {
                $lastTime = time() - 86400;
            }

            if (file_exists($log) && date('Y-m-d', $lastTime) != date('Y-m-d')) {
                // archive log (will be cleaned up by maintenance)
                $archiveFilename = preg_replace('/\.log$/', '', $log).'-archive-'.date('Y-m-d', $lastTime).'.log';
                rename($log, $archiveFilename);

                if ($lastTimeItem) {
                    $lastTimeItem->setData(time());
                    $lastTimeItem->update(86400 * 7);
                } else {
                    TmpStore::add($tmpStoreTimeId, time(), null, 86400 * 7);
                }
            }
        }

        // archive and cleanup logs
        $files = [];
        $logFiles = glob(PIMCORE_LOG_DIRECTORY.'/*-archive-*.log');
        if (is_array($logFiles)) {
            $files = array_merge($files, $logFiles);
        }
        $archivedLogFiles = glob(PIMCORE_LOG_DIRECTORY.'/*-archive-*.log.gz');
        if (is_array($archivedLogFiles)) {
            $files = array_merge($files, $archivedLogFiles);
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                if (filemtime($file) < (time() - (86400 * 7))) { // we keep the logs for 7 days
                    unlink($file);
                } elseif (!preg_match("/\.gz$/", $file)) {
                    gzcompressfile($file);
                    unlink($file);
                }
            }
        }
    }
}
