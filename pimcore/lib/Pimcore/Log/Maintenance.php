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

namespace Pimcore\Log;

use Pimcore\Config;
use Pimcore\Logger;
use Pimcore\Tool;

class Maintenance
{
    public function httpErrorLogCleanup()
    {

        // keep the history for max. 7 days (=> exactly 144h), according to the privacy policy (EU/German Law)
        // it's allowed to store the IP for 7 days for security reasons (DoS, ...)
        $limit = time() - (6 * 86400);

        $db = \Pimcore\Db::get();
        $db->deleteWhere('http_error_log', 'date < ' . $limit);
    }

    public function usageStatistics()
    {
        if (Config::getSystemConfig()->general->disableusagestatistics) {
            return;
        }

        $logFile = PIMCORE_LOG_DIRECTORY . '/usagelog.log';
        if (is_file($logFile) && filesize($logFile) > 200000) {
            $data = gzencode(file_get_contents($logFile));
            $response = Tool::getHttpData('https://liveupdate.pimcore.org/usage-statistics', [], ['data' => $data]);
            if (strpos($response, 'true') !== false) {
                rename($logFile, $logFile . '-archive-' . date('m-d-Y-H-i'));
                Logger::debug('Usage statistics were transmitted and logfile was archived');
            } else {
                Logger::debug('Unable to send usage statistics');
            }
        }
    }

    public function cleanupLogFiles()
    {
        // we don't use the RotatingFileHandler of Monolog, since rotating asynchronously is recommended + compression
        $logFiles = glob(PIMCORE_LOG_DIRECTORY . '/*.log');

        foreach ($logFiles as $log) {
            if (file_exists($log) && filesize($log) > 200000000) {
                // archive log (will be cleaned up by maintenance)
                rename($log, $log . '-archive-' . date('m-d-Y-H-i'));
            }
        }

        // archive and cleanup logs
        $files = glob(PIMCORE_LOG_DIRECTORY . '/*.log-archive-*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (filemtime($file) < (time() - (86400 * 30))) { // we keep the logs for 30 days
                    unlink($file);
                } elseif (!preg_match("/\.gz$/", $file)) {
                    gzcompressfile($file);
                    unlink($file);
                }
            }
        }
    }

    public function checkErrorLogsDb()
    {
        $db = \Pimcore\Db::get();
        $conf = Config::getSystemConfig();
        $config = $conf->applicationlog;

        if ($config->mail_notification->send_log_summary) {
            $receivers = preg_split('/,|;/', $config->mail_notification->mail_receiver);

            array_walk($receivers, function (&$value) {
                $value = trim($value);
            });

            $logLevel = (int)$config->mail_notification->filter_priority;

            $query = 'SELECT * FROM '. \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . " WHERE maintenanceChecked IS NULL AND priority <= $logLevel order by id desc";

            $rows = $db->fetchAll($query);
            $limit = 100;
            $rowsProcessed = 0;

            $rowCount = count($rows);
            if ($rowCount) {
                while ($rowsProcessed < $rowCount) {
                    $entries = [];

                    if ($rowCount <= $limit) {
                        $entries = $rows;
                    } else {
                        for ($i = $rowsProcessed; $i < $rowCount && count($entries) < $limit; $i++) {
                            $entries[] = $rows[$i];
                        }
                    }

                    $rowsProcessed += count($entries);

                    $html = var_export($entries, true);
                    $html = "<pre>$html</pre>";
                    $mail = new \Pimcore\Mail();
                    $mail->setIgnoreDebugMode(true);
                    $mail->setBodyHtml($html);
                    $mail->addTo($receivers);
                    $mail->setSubject('Error Log ' . \Pimcore\Tool::getHostUrl());
                    $mail->send();
                }
            }
        }

        // flag them as checked, regardless if email notifications are enabled or not
        // otherwise, when activating email notifications, you'll receive all log-messages from the past and not
        // since the point when you enabled the notifications
        $db->query('UPDATE ' . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . ' set maintenanceChecked = 1');
    }

    public function archiveLogEntries()
    {
        $conf = Config::getSystemConfig();
        $config = $conf->applicationlog;

        $db = \Pimcore\Db::get();

        $date = new \DateTime('now');
        $tablename = \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_' . $date->format('m') . '_' . $date->format('Y');

        if ($config->archive_alternative_database) {
            $tablename = $config->archive_alternative_database . '.' . $tablename;
        }

        $archive_treshold = intval($config->archive_treshold) ?: 30;

        $db->query('CREATE TABLE IF NOT EXISTS ' . $tablename . " (
                       id BIGINT(20) NOT NULL,
                       `pid` INT(11) NULL DEFAULT NULL,
                       `timestamp` DATETIME NOT NULL,
                       message VARCHAR(1024),
                       `priority` ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
                       fileobject VARCHAR(1024),
                       info VARCHAR(1024),
                       component VARCHAR(255),
                       source VARCHAR(255) NULL DEFAULT NULL,
                       relatedobject BIGINT(20),
                       relatedobjecttype ENUM('object', 'document', 'asset'),
                       maintenanceChecked TINYINT(4)
                    ) ENGINE = ARCHIVE ROW_FORMAT = DEFAULT;");

        $timestamp = time();

        $db->query('INSERT INTO ' . $tablename . ' SELECT * FROM ' .  \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . ' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME(' . $timestamp . '), INTERVAL ' . $archive_treshold . ' DAY);');
        $db->query('DELETE FROM ' .  \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . ' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME(' . $timestamp . '), INTERVAL ' . $archive_treshold . ' DAY);');
    }
}
