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

namespace OnlineShop\Framework\PricingManager;

interface IPriceInfo extends \OnlineShop\Framework\PriceSystem\IPriceInfo
{
    /**
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo           $priceInfo
     * @param IEnvironment $environment
     */
    public function __construct(\OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo, IEnvironment $environment);

    /**
     * @param \OnlineShop\Framework\PricingManager\IRule $rule
     *
     * @return \OnlineShop\Framework\PriceSystem\IPriceInfo
     */
    public function addRule(\OnlineShop\Framework\PricingManager\IRule $rule);

    /**
     * returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     * @return \OnlineShop\Framework\PricingManager\IRule[]
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
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getOriginalPrice();

    /**
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getOriginalTotalPrice();

    /**
     * @return IEnvironment
     */
    public function getEnvironment();

    /**
     * @param IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PriceSystem\IPriceInfo
     */
    public function setEnvironment(IEnvironment $environment);

    /**
     * @return bool
     */
    public function hasDiscount();

    /**
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getDiscount();

    /**
     * @return \OnlineShop\Framework\PriceSystem\IPrice
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