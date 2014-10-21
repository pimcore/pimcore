<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Db;

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
    protected $queries = array();

    /**
     * @param null $label
     */
    public function __construct($label = null)
    {
        $this->_label = $label;
        if(!$this->_label) {
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
     * @return void
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

        $logEntry = "Process: " . $this->getConnectionId() . " | DB Query (#" . $this->_totalQueries . "): " . (string)round($profile->getElapsedSecs(),5) . " | " . $profile->getQuery() . " | " . implode(",",$profile->getQueryParams());
        \Logger::debug($logEntry);

        $this->queries[] = array(
            "time" => $profile->getElapsedSecs(),
            "query" => $profile->getQuery() . " | " . implode(",",$profile->getQueryParams())
        );
    }

    /**
     * 
     */
    public function __destruct() {
        if(is_resource($this->logFile)) {

            // write the total time at the end
            $message = "\n\n\n--------------------\n";
            $message .= "Total Elapsed Time: ". (string)round($this->_totalElapsedTime,5) . "\n";
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
                if($count > 5) {
                    break;
                }

                $message .= "#" . $key . ":  " . (string)round($value["time"],5) . " | " . $value["query"] . "\n";
            }
            $message .= "\n";

            $message .= "\n--------------------\n\n";


            fwrite($this->logFile, $message);

            fclose($this->logFile);    
        }
    }

    /**
     * Update the label of the message holding the profile info.
     *
     * @return void
     */
    protected function updateMessageLabel()
    {
        if (!$this->_message) {
            return;
        }
        $this->_message->setLabel(str_replace(array('%label%',
                                                    '%totalCount%',
                                                    '%totalDuration%'),
                                              array($this->_label,
                                                    $this->getTotalNumQueries(),
                                                    (string)round($this->_totalElapsedTime,5)),
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
