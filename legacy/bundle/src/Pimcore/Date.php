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

namespace Pimcore;

class Date extends \Zend_Date
{

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
    public function __wakeup()
    {
        $this->setLocale(null);
    }
}
