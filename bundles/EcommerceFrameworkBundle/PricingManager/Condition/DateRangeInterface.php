<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;

interface DateRangeInterface extends ConditionInterface
{
    /**
     * @param \DateTime $date
     *
     * @return DateRangeInterface
     */
    public function setStarting(\DateTime $date);

    /**
     * @param \DateTime $date
     *
     * @return DateRangeInterface
     */
    public function setEnding(\DateTime $date);

    /**
     * @return \DateTime|null
     */
    public function getStarting();

    /**
     * @return \DateTime|null
     */
    public function getEnding();
}
