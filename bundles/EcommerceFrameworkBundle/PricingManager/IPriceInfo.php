<?php

declare(strict_types=1);

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

use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface as PriceSystemPriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

interface IPriceInfo extends PriceSystemPriceInfoInterface
{
    /**
     * @param PriceSystemPriceInfoInterface $priceInfo
     * @param IEnvironment $environment
     */
    public function __construct(PriceSystemPriceInfoInterface $priceInfo, IEnvironment $environment);

    /**
     * @param IRule $rule
     *
     * @return PriceSystemPriceInfoInterface
     */
    public function addRule(IRule $rule);

    /**
     * Returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     *
     * @return IRule[]
     */
    public function getRules(bool $forceRecalc = false): array;

    /**
     * @param Decimal $amount
     *
     * @return IPriceInfo
     */
    public function setAmount(Decimal $amount);

    /**
     * @return IPriceInfo
     */
    public function getAmount(): Decimal;

    /**
     * @return PriceInterface
     */
    public function getOriginalPrice(): PriceInterface;

    /**
     * @return PriceInterface
     */
    public function getOriginalTotalPrice(): PriceInterface;

    /**
     * @return IEnvironment
     */
    public function getEnvironment(): IEnvironment;

    /**
     * @param IEnvironment $environment
     *
     * @return IPriceInfo
     */
    public function setEnvironment(IEnvironment $environment);

    /**
     * @return bool
     */
    public function hasDiscount(): bool;

    /**
     * @return PriceInterface
     */
    public function getDiscount(): PriceInterface;

    /**
     * @return PriceInterface
     */
    public function getTotalDiscount(): PriceInterface;

    /**
     * Get discount in percent
     *
     * @return float
     */
    public function getDiscountPercent();

    /**
     * @return bool
     */
    public function hasRulesApplied(): bool;
}
