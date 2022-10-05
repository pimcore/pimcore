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

namespace Pimcore\Log\Handler;

use Doctrine\DBAL\Connection;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Pimcore\Db;
use Psr\Log\LogLevel;

/**
 * @phpstan-import-type Level from \Monolog\Logger
 * @phpstan-import-type LevelName from \Monolog\Logger
 */
class ApplicationLoggerDb extends AbstractProcessingHandler
{
    const TABLE_NAME = 'application_logs';

    const TABLE_ARCHIVE_PREFIX = 'application_logs_archive';

    /**
     * @var Connection
     */
    private $db;

    /**
     * @param Connection $db
     * @param int|string $level
     * @param bool $bubble
     *
     * @phpstan-param Level|LevelName|LogLevel::* $level
     */
    public function __construct(Connection $db, $level = Level::Debug, $bubble = true)
    {
        $this->db = $db;
        parent::__construct($level, $bubble);
    }

    public function write(LogRecord $record): void
    {
        $data = [
            'pid' => getmypid(),
            'priority' => strtolower($record->level->name),
            'message' => $record->message,
            'timestamp' => $record->datetime->format('Y-m-d H:i:s'),
            'component' => $record->context['component'] ?? $record->channel,
            'fileobject' => $record->context['fileObject'] ?? null,
            'relatedobject' => $record->context['relatedObject'] ?? null,
            'relatedobjecttype' => $record->context['relatedObjectType'] ?? null,
            'source' => $record->context['source'] ?? null,
        ];

        $this->db->insert(self::TABLE_NAME, $data);
    }

    /**
     * @return string[]
     */
    public static function getComponents()
    {
        $db = Db::get();

        $components = $db->fetchFirstColumn('SELECT component FROM ' . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . ' WHERE NOT ISNULL(component) GROUP BY component;');

        return $components;
    }

    /**
     * @return string[]
     */
    public static function getPriorities()
    {
        $priorities = [];
        $priorityNames = [
            'debug' => 'DEBUG',
            'info' => 'INFO',
            'notice' => 'NOTICE',
            'warning' => 'WARN',
            'error' => 'ERR',
            'critical' => 'CRIT',
            'alert' => 'ALERT',
            'emergency' => 'EMERG',
        ];

        $db = Db::get();

        $priorityNumbers = $db->fetchFirstColumn('SELECT priority FROM ' . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . ' WHERE NOT ISNULL(priority) GROUP BY priority;');
        foreach ($priorityNumbers as $priorityNumber) {
            $priorities[$priorityNumber] = $priorityNames[$priorityNumber];
        }

        return $priorities;
    }
}
