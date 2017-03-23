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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Condition;

interface IDateRange extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\ICondition
{
    /**
     * @param \DateTime $date
     *
     * @return IDateRange
     */
    public function setStarting(\DateTime $date);

    /**
     * @param \DateTime $date
     *
     * @return IDateRange
     */
    public function setEnding(\DateTime $date);

    /**
     * @return \DateTime
     */
    public function getStarting();

    /**
     * @return \DateTime
     */
    public function getEnding();
}