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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Db;

use Pimcore\Logger;

class Profiler extends \Zend_Db_Profiler
{
    /**
     * The original label for this profiler.
     * @var string
     */
    protected $_label = null;

    /**
     * The label template for this profiler
     * @var string
     */
    protected $_label_template = '%label% (%totalCount% @ %totalDuration% sec)';

    /**
     * The message envelope holding the profiling summary
     * @var \Zend_Wildfire_Plugin_FirePhp_TableMessage
     */
    protected $_message = null;

    /**
     * The total time taken for all profiled queries.
     * @var float
     */
    protected $_totalElapsedTime = 0;

    /**
     * @var int
     */
    protected $_totalQueries = 0;

    /**
     * @var resource
     */
    protected $logFile;

    /**
     * @var int
     */
    protected $connectionId;

    /**
     * @var array
     */
    protected $queries = [];

    /**
     * @param null $label
     */
    public function __construct($label = null)
    {
        $this->_label = $label;
        if (!$this->_label) {
            $this->_label = "Pimcore\\Db\\Profiler";
        }
    }

    /**
     * Enable or disable the profiler.  If $enable is false, the profiler
     * is disabled and will not log any queries sent to it.
     *
     * @param  boolean $enable
     * @return \Zend_Db_Profiler Provides a fluent interface
     */
    public function setEnabled($enable)
    {
        parent::setEnabled($enable);

        return $this;
    }

    /**
     * Intercept the query end and log the profiling data.
     *
     * @param  integer $queryId
     * @throws \Zend_Db_Profiler_Exception
     */
    public function queryEnd($queryId)
    {
        $state = parent::queryEnd($queryId);

        if (!$this->getEnabled() || $state == self::IGNORED) {
            return;
        }

        $profile = $this->getQueryProfile($queryId);
        $this->_totalElapsedTime += $profile->getElapsedSecs();
        $this->_totalQueries++;

        $logEntry = $profile->getQuery() . " | " . implode(",", $profile->getQueryParams());
        Logger::debug($logEntry, [
            "connection" => $this->getConnectionId(),
            "queryNum" => $this->_totalQueries,
            "time" => (string)round($profile->getElapsedSecs(), 5)
        ]);

        $this->queries[] = [
            "time" => $profile->getElapsedSecs(),
            "query" => $profile->getQuery() . " | " . implode(",", $profile->getQueryParams())
        ];
    }

    /**
     *
     */
    public function __destruct()
    {
        if (is_resource($this->logFile)) {

            // write the total time at the end
            $message = "\n\n\n--------------------\n";
            $message .= "Total Elapsed Time: ". (string)round($this->_totalElapsedTime, 5) . "\n";
            $message .= "Total Queries: " . $this->_totalQueries . "\n";
            $message .= "Top Queries: \n";

            uasort($this->queries, function ($x, $y) {
                $a = $x["time"];
                $b = $y["time"];

                if ($a == $b) {
                    return 0;
                }

                return ($b < $a) ? -1 : 1;
            });

            $count = 0;
            foreach ($this->queries as $key => $value) {
                $count++;
                if ($count > 5) {
                    break;
                }

                $message .= "#" . $key . ":  " . (string)round($value["time"], 5) . " | " . $value["query"] . "\n";
            }
            $message .= "\n";

            $message .= "\n--------------------\n\n";


            fwrite($this->logFile, $message);

            fclose($this->logFile);
        }
    }

    /**
     * Update the label of the message holding the profile info.
     */
    protected function updateMessageLabel()
    {
        if (!$this->_message) {
            return;
        }
        $this->_message->setLabel(str_replace(['%label%',
                                                    '%totalCount%',
                                                    '%totalDuration%'],
                                              [$this->_label,
                                                    $this->getTotalNumQueries(),
                                                    (string)round($this->_totalElapsedTime, 5)],
                                              $this->_label_template));
    }

    /**
     * @param int $connectionId
     */
    public function setConnectionId($connectionId)
    {
        $this->connectionId = $connectionId;
    }

    /**
     * @return int
     */
    public function getConnectionId()
    {
        return $this->connectionId;
    }
}
