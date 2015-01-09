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

namespace Pimcore;

class Date extends \Zend_Date {

    /**
     *
     */
    const MYSQL_DATETIME = 'YYYY-MM-dd HH:mm:ss';

    /**
     *
     */
    const MYSQL_DATE     = 'YYYY-MM-dd';

    /**
     * @throws \Zend_Date_Exception
     */
    public function __wakeup () {
        $this->setLocale(null);
    }
}
