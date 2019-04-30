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
use Pimcore\Db\Connection;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Pimcore\Maintenance\TaskInterface;

final class CheckErrorLogsDbTask implements TaskInterface
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @param Connection   $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $conf = Config::getSystemConfig();
        $config = $conf->applicationlog;

        if ($config->mail_notification->send_log_summary) {
            $receivers = preg_split('/,|;/', $config->mail_notification->mail_receiver);

            array_walk($receivers, 'trim');

            $logLevel = (int)$config->mail_notification->filter_priority;

            $query = 'SELECT * FROM '. ApplicationLoggerDb::TABLE_NAME . " WHERE maintenanceChecked IS NULL AND priority <= $logLevel order by id desc";

            $rows = $this->db->fetchAll($query);
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
        $this->db->query('UPDATE ' . ApplicationLoggerDb::TABLE_NAME . ' set maintenanceChecked = 1');
    }
}
