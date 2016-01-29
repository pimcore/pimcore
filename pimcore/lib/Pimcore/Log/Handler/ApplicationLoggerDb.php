<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Log\Handler;

use Pimcore\Db as Database;
use Monolog\Handler\AbstractProcessingHandler;
use Pimcore\Tool;

class ApplicationLoggerDb extends AbstractProcessingHandler
{

    /**
     *
     */
    const TABLE_NAME = "application_logs";

    /**
     *
     */
    const TABLE_ARCHIVE_PREFIX = "application_logs_archive";

    /**
     * ApplicationLoggerDb constructor.
     * @param string $level
     * @param bool|true $bubble
     */
    public function __construct($level = "debug", $bubble = true)
    {

        // Zend_Log compatibility
        $zendLoggerPsr3Mapping = \Logger::getZendLoggerPsr3Mapping();
        if (isset($zendLoggerPsr3Mapping[$level])) {
            $level = $zendLoggerPsr3Mapping[$level];
        }

        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     */
    public function write(array $record)
    {
        // put into db
        $db = Database::get();

        $data = [
            'pid' => getmypid(),
            'priority' => strtolower($record["level_name"]),
            'message' => $record["message"],
            'timestamp' => $record["datetime"]->format("Y-m-d H:i:s"),
            'fileobject' => $record["context"]["fileObject"],
            'relatedobject' => $record["context"]["relatedObject"],
            'relatedobjecttype' => $record["context"]["relatedObjectType"],
            'component' => $record["context"]["component"],
            'source' => $record["context"]["source"]
        ];

        $db->insert(self::TABLE_NAME, $data);
    }

    /**
     * @deprecated
     * @param $level
     */
    public function setFilterPriority($level)
    {
        // legacy ZF method
        $zendLoggerPsr3Mapping = \Logger::getZendLoggerPsr3Mapping();
        if (isset($zendLoggerPsr3Mapping[$level])) {
            $level = $zendLoggerPsr3Mapping[$level];
            $this->setLevel($level);
        }
    }

    /**
     * @static
     * @return string[]
     */
    public static function getComponents()
    {
        $db = Database::get();

        $components = $db->fetchCol("SELECT component FROM " . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . " WHERE NOT ISNULL(component) GROUP BY component;");
        return $components;
    }

    /**
     * @static
     * @return string[]
     */
    public static function getPriorities()
    {
        $priorities = array();
        $priorityNames = array(
            "debug" => "DEBUG",
            "info" => "INFO",
            "notice" => "NOTICE",
            "warning" => "WARN",
            "error" => "ERR",
            "critical" => "CRIT",
            "alert" => "ALERT",
            "emergency" => "EMERG"
        );

        $db = Database::get();

        $priorityNumbers = $db->fetchCol("SELECT priority FROM " . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . " WHERE NOT ISNULL(priority) GROUP BY priority;");
        foreach ($priorityNumbers as $priorityNumber) {
            $priorities[$priorityNumber] = $priorityNames[$priorityNumber];
        }

        return $priorities;
    }
}
