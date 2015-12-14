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

namespace OnlineShop\Framework\PricingManager\Condition;

interface IDateRange extends \OnlineShop\Framework\PricingManager\ICondition
{
    /**
     * @param \Zend_Date $date
     *
     * @return IDateRange
     */
    public function setStarting(\Zend_Date $date);

    /**
     * @param \Zend_Date $date
     *
     * @return IDateRange
     */
    public function setEnding(\Zend_Date $date);

    /**
     * @return \Zend_Date
     */
    public function getStarting();

    /**
     * @return \Zend_Date
     */
    public function getEnding();
}