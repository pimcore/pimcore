<?php

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

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Pimcore\Config;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Tool\Storage;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class LogArchiveTask implements TaskInterface
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Connection $db
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(Connection $db, Config $config, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $db = $this->db;
        $storage = Storage::get('application_log');

        $date = new \DateTime('now');
        $tablename = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX.'_'.$date->format('m').'_'.$date->format('Y');

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

            $this->logger->debug('Deleting referenced FileObjects of application_logs which are older than '. $archive_threshold.' days');

            $fileObjectPaths = $db->fetchAllAssociative(sprintf($sql, 'fileobject'));
            foreach ($fileObjectPaths as $objectPath) {
                $storage->delete($objectPath['fileobject']);
            }

            $db->executeQuery('DELETE FROM '.ApplicationLoggerDb::TABLE_NAME.' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '.$archive_threshold.' DAY);');
        }

        $deleteArchiveLogDate = (new DateTimeImmutable())->sub(new DateInterval('P'. ($this->config['applicationlog']['delete_archive_threshold'] ?? 6) .'M'));
        do {
            $applicationLogArchiveTable = 'application_logs_archive_' . $deleteArchiveLogDate->format('m_Y');
            $archiveTableExists = $db->fetchOne('SELECT 1
                FROM information_schema.tables
                WHERE table_schema = ?
                AND table_name = ?',
                [
                    $this->config['applicationlog']['archive_alternative_database'] ?: $db->getDatabase(),
                    $applicationLogArchiveTable,
                ]);

            if ($archiveTableExists) {
                $db->exec('DROP TABLE IF EXISTS `' . ($this->config['applicationlog']['archive_alternative_database'] ?: $db->getDatabase()) . '`.' . $applicationLogArchiveTable);
                $storage->deleteDirectory($deleteArchiveLogDate->format('Y/m'));
            }

            $deleteArchiveLogDate = $deleteArchiveLogDate->sub(new DateInterval('P1M'));
        } while ($archiveTableExists);
    }
}
