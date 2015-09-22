<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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