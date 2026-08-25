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
use function in_arrayi;

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
        $archiveTableName = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX.'_'.$date->format('Y').'_'.$date->format('m');
        $tablename = $archiveTableName;
        $quotedTablename = $db->quoteIdentifier($archiveTableName);

        if (!empty($this->config['applicationlog']['archive_alternative_database'])) {
            $quotedDatabase = $db->quoteIdentifier($this->config['applicationlog']['archive_alternative_database']);
            $tablename = $quotedDatabase.'.'.$tablename;
            $quotedTablename = $quotedDatabase.'.'.$quotedTablename;
        }

        $archive_threshold = (int) ($this->config['applicationlog']['archive_treshold'] ?? 30);

        $timestamp = time();
        $sql = 'SELECT %s FROM '.ApplicationLoggerDb::TABLE_NAME.' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '.$archive_threshold.' DAY)';

        if ($db->fetchOne(sprintf($sql, 'COUNT(*)')) > 0) {

            if (!$db->createSchemaManager()->tableExists($tablename)) {
                $storageEngine = $this->config['applicationlog']['archive_db_table_storage_engine'];
                if (!$storageEngine) {
                    // auto-detect if no storage engine is defined in config
                    $engines = $db->fetchFirstColumn(
                        'SELECT Engine FROM information_schema.ENGINES WHERE Support IN (\'YES\',\'DEFAULT\')'
                    );
                    $storageEngine = match (true) {
                        in_arrayi('archive', $engines) => 'ARCHIVE',
                        in_arrayi('aria', $engines) => 'Aria',
                        in_arrayi('myisam', $engines) => 'MyISAM',
                        default => 'InnoDB',
                    };
                }

                $db->executeQuery('CREATE TABLE ' . $tablename . " (
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
                    ) ENGINE = " . $storageEngine . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci ROW_FORMAT = DEFAULT;');
            }
            $db->executeStatement($this->getArchiveStatement($quotedTablename, $timestamp, $archive_threshold));

            $fileObjectPaths = $db->fetchFirstColumn(sprintf($sql, 'fileobject'));

            $db->executeStatement('DELETE FROM '.ApplicationLoggerDb::TABLE_NAME.' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '.$archive_threshold.' DAY);');

            $this->logger->debug('Deleting referenced FileObjects of application_logs which are older than '.$archive_threshold.' days');

            // the file objects are removed only after the rows are gone from the source table, so
            // that a failing storage never keeps already archived rows around for the next run
            foreach ($fileObjectPaths as $filePath) {
                if ($filePath !== null && $storage->fileExists($filePath)) {
                    $storage->delete($filePath);
                }
            }
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

    /**
     * Archives every log entry older than the threshold that is not in the archive table yet.
     *
     * Skipping the entries that are already there is what keeps a re-run harmless: whenever an
     * earlier run failed between archiving the entries and deleting them from the source table,
     * those entries are picked up again by the next run. Nothing would reject them - the archive
     * table has no key on `id`, and the ARCHIVE storage engine used by default cannot have one -
     * so they would be appended once more on every following run.
     */
    private function getArchiveStatement(string $quotedArchiveTable, int $timestamp, int $archiveThreshold): string
    {
        $quotedSourceTable = $this->db->quoteIdentifier(ApplicationLoggerDb::TABLE_NAME);

        $olderThanThreshold = '`log`.`timestamp` < DATE_SUB(FROM_UNIXTIME('.$timestamp.'), INTERVAL '
            .$archiveThreshold.' DAY)';

        return 'INSERT INTO '.$quotedArchiveTable.' SELECT `log`.* FROM '.$quotedSourceTable.' `log`'
            .' LEFT JOIN '.$quotedArchiveTable.' `archived` ON `archived`.`id` = `log`.`id`'
            .' WHERE '.$olderThanThreshold.' AND `archived`.`id` IS NULL';
    }
}
