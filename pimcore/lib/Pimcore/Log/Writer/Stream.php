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
namespace Pimcore\Log\Writer;

use \Zend_Log;

class Stream extends \Zend_Log_Writer_Stream {

    public function __construct($streamOrUrl, $mode = null,$filterPriority = \Zend_Log::ERR){
        parent::__construct($streamOrUrl, $mode = null);
        $this->setFilterPriority($filterPriority);
    }

    public function setFilterPriority($filterPriority) {
        $unsetKeys = array();

        foreach($this->_filters as $key => $filter) {
            if($filter instanceof \Zend_Log_Filter_Priority) {
                $unsetKeys[] = $key;
            }
        }

        foreach($unsetKeys as $key) {
            unset($this->_filters[$key]);
        }

        $filter = new \Zend_Log_Filter_Priority($filterPriority);
        $this->addFilter($filter);
    }
}