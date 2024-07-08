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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Maintenance;

use Carbon\Carbon;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Config;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Tool\Storage;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class LogArchiveTask implements TaskInterface
{
    private Connection $db;

    private Config $config;

    private LoggerInterface $logger;

    public function __construct(Connection $db, Config $config, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $db = $this->db;
        $storage = Storage::get('application_log');

        $date = new DateTime('now');
        $tablename = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX.'_'.$date->format('Y').'_'.$date->format('m');

        if (!empty($this->config['applicationlog']['archive_alternative_database'])) {
            $tablename = $db->quoteIdentifier($this->config['applicationlog']['archive_alternative_database']).'.'.$tablename;
        }

        $archive_threshold = (int) ($this->config['applicationlog']['archive_treshold'] ?? 30);

        $timestamp = time();
        $sql = 'SELECT %s FROM '.ApplicationLoggerDb::TABLE_NAME.' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '.$archive_threshold.' DAY)';

        if ($db->fetchOne(sprintf($sql, 'COUNT(*)')) > 0) {
            $db->executeQuery('CREATE TABLE IF NOT EXISTS '.$tablename." (
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
                       maintenanceChecked TINYINT(1)
                    ) ENGINE = ARCHIVE ROW_FORMAT = DEFAULT;");

            $db->executeQuery('INSERT INTO '.$tablename.' '.sprintf($sql, '*'));

            $this->logger->debug('Deleting referenced FileObjects of application_logs which are older than '.$archive_threshold.' days');

            $fileObjectPaths = $db->fetchAllAssociative(sprintf($sql, 'fileobject'));
            foreach ($fileObjectPaths as $objectPath) {
                $filePath = $objectPath['fileobject'];
                if ($filePath !== null) {
                    if ($storage->fileExists($filePath)) {
                        $storage->delete($filePath);
                    }
                }
            }

            $db->executeQuery('DELETE FROM '.ApplicationLoggerDb::TABLE_NAME.' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '.$archive_threshold.' DAY);');
        }

        $archiveTables = $db->fetchFirstColumn(
            'SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = ?
                AND table_name LIKE ?',
            [
                $this->config['applicationlog']['archive_alternative_database'] ?: $db->getDatabase(),
                ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX.'_%',
            ]
        );
        foreach ($archiveTables as $archiveTable) {
            if (preg_match('/^'.ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX.'_(\d{4})_(\d{2})$/', $archiveTable, $matches)) {
                $deleteArchiveLogDate = Carbon::createFromFormat('Y/m', $matches[1].'/'.$matches[2]);
                if ($deleteArchiveLogDate->add(new DateInterval('P'.($this->config['applicationlog']['delete_archive_threshold'] ?? 6).'M')) < new DateTimeImmutable()) {
                    $db->executeStatement('DROP TABLE IF EXISTS `'.($this->config['applicationlog']['archive_alternative_database'] ?: $db->getDatabase()).'`.'.$archiveTable);

                    $folderName = $deleteArchiveLogDate->format('Y/m');

                    if ($storage->directoryExists($folderName)) {
                        $storage->deleteDirectory($folderName);
                    }
                }
            }
        }
    }
}
