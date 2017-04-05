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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;

interface IPriceInfo extends \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo
{
    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo           $priceInfo
     * @param IEnvironment $environment
     */
    public function __construct(\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo $priceInfo, IEnvironment $environment);

    /**
     * @param IRule $rule
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo
     */
    public function addRule(IRule $rule);

    /**
     * returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     * @return IRule[]
     */
    public function getRules($forceRecalc = false);

    /**
     * @param float $amount
     *
     * @return IPriceInfo
     */
    public function setAmount($amount);

    /**
     * @return mixed
     */
    public function getAmount();

    /**
     * @return IPrice
     */
    public function getOriginalPrice();

    /**
     * @return IPrice
     */
    public function getOriginalTotalPrice();

    /**
     * @return IEnvironment
     */
    public function getEnvironment();

    /**
     * @param IEnvironment $environment
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo
     */
    public function setEnvironment(IEnvironment $environment);

    /**
     * @return bool
     */
    public function hasDiscount();

    /**
     * @return IPrice
     */
    public function getDiscount();

    /**
     * @return IPrice
     */
    public function getTotalDiscount();

    /**
     * get discount in percent
     * @return float
     */
    public function getDiscountPercent();

    /**
     * @return bool
     */
    public function hasRulesApplied();
}
