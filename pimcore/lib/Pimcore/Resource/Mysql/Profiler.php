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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */


/** Zend_Db_Profiler */
require_once 'Zend/Db/Profiler.php';


/**
 * Writes DB events as log messages to the Firebug Console via FirePHP.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Profiler
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Pimcore_Resource_Mysql_Profiler extends Zend_Db_Profiler
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
     * @var Zend_Wildfire_Plugin_FirePhp_TableMessage
     */
    protected $_message = null;

    /**
     * The total time taken for all profiled queries.
     * @var float
     */
    protected $_totalElapsedTime = 0;

    /**
     * Constructor
     *
     * @param string $label OPTIONAL Label for the profiling info.
     * @return void
     */
    public function __construct($label = null)
    {
        $this->_label = $label;
        if(!$this->_label) {
            $this->_label = 'Pimcore_Resource_Mysql_Profiler';
        }
    }

    /**
     * Enable or disable the profiler.  If $enable is false, the profiler
     * is disabled and will not log any queries sent to it.
     *
     * @param  boolean $enable
     * @return Zend_Db_Profiler Provides a fluent interface
     */
    public function setEnabled($enable)
    {
        parent::setEnabled($enable);

        /*if ($this->getEnabled()) {

            if (!$this->_message) {
                $this->_message = new Zend_Wildfire_Plugin_FirePhp_TableMessage($this->_label);
                $this->_message->setBuffered(true);
                $this->_message->setHeader(array('Time','Event','Parameters'));
                $this->_message->setDestroy(true);
                $this->_message->setOption('includeLineNumbers', false);
                Zend_Wildfire_Plugin_FirePhp::getInstance()->send($this->_message);
            }

        } else {

            if ($this->_message) {
                $this->_message->setDestroy(true);
                $this->_message = null;
            }

        }*/

        return $this;
    }

    /**
     * Intercept the query end and log the profiling data.
     *
     * @param  integer $queryId
     * @throws Zend_Db_Profiler_Exception
     * @return void
     */
    public function queryEnd($queryId)
    {
        $state = parent::queryEnd($queryId);

        if (!$this->getEnabled() || $state == self::IGNORED) {
            return;
        }
        
        /*
        $this->_message->setDestroy(false);
        */
        
        $profile = $this->getQueryProfile($queryId);
        $this->_totalElapsedTime += $profile->getElapsedSecs();
        Logger::debug("DB Query: " . (string)round($profile->getElapsedSecs(),5) . " | " . $profile->getQuery() . " | ");
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
}
