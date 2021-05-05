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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Maintenance\Tasks;

use DateInterval;
use DateTimeImmutable;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Pimcore\Maintenance\TaskInterface;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 */
class LogArchiveTask implements TaskInterface
{
    /**
     * @var Db\ConnectionInterface
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
     * @var LockInterface
     */
    private $lock;

    /**
     * @param Db\ConnectionInterface $db
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(Db\ConnectionInterface $db, Config $config, LoggerInterface $logger, LockFactory $lockFactory)
    {
        $this->db = $db;
        $this->config = $config;
        $this->logger = $logger;
        $this->lock = $lockFactory->createLock(self::class, 86400);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $db = $this->db;

        $date = new \DateTime('now');
        $tablename = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX.'_'.$date->format('m').'_'.$date->format('Y');

        if (!empty($this->config['applicationlog']['archive_alternative_database'])) {
            $tablename = $db->quoteIdentifier($this->config['applicationlog']['archive_alternative_database']).'.'.$tablename;
        }

        $archive_threshold = (int) ($this->config['applicationlog']['archive_treshold'] ?? 30);

        $timestamp = time();
        $sql = 'SELECT %s FROM '.ApplicationLoggerDb::TABLE_NAME.' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '.$archive_threshold.' DAY)';

        if ($db->fetchOne(sprintf($sql, 'COUNT(*)')) > 0) {
            $db->query('CREATE TABLE IF NOT EXISTS '.$tablename." (
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

            $db->query('INSERT INTO '.$tablename.' '.sprintf($sql, '*'));
            $db->query('DELETE FROM '.ApplicationLoggerDb::TABLE_NAME.' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '.$archive_threshold.' DAY);');
        }

        if (date('H') <= 4 && $this->lock->acquire()) {
            // execution should be only sometime between 0:00 and 4:59 -> less load expected
            $this->logger->debug('Deleting referenced FileObjects of application_logs which are older than '. $archive_threshold.' days');
            $fileIterator = new \DirectoryIterator(PIMCORE_LOG_FILEOBJECT_DIRECTORY);

            $oldestAllowedTimestamp = time() - $archive_threshold * 86400;
            $fileIterator = new \CallbackFilterIterator(
                $fileIterator,
                static function (\SplFileInfo $fileInfo) use ($oldestAllowedTimestamp) {
                    return $fileInfo->getMTime() < $oldestAllowedTimestamp;
                }
            );

            /** @var SplFileInfo $fileInfo */
            foreach ($fileIterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    @unlink($fileInfo->getPathname());
                }
            }
        } else {
            $this->logger->debug('Skip cleaning up referenced FileObjects of application_logs, was done within the last 24 hours');
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
                $db->exec('DROP TABLE IF EXISTS ' . ($this->config['applicationlog']['archive_alternative_database'] ?: $db->getDatabase()) . '.' . $applicationLogArchiveTable);
            }

            $deleteArchiveLogDate = $deleteArchiveLogDate->sub(new DateInterval('P1M'));
        } while ($archiveTableExists);
    }
}
