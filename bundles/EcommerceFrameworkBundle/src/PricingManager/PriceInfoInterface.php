<?php

declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface as PriceSystemPriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

interface PriceInfoInterface extends PriceSystemPriceInfoInterface
{
    public function __construct(PriceSystemPriceInfoInterface $priceInfo, EnvironmentInterface $environment);

    /**
     * @return $this
     */
    public function addRule(RuleInterface $rule): static;

    /**
     * Returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     *
     * @return RuleInterface[]
     */
    public function getRules(bool $forceRecalc = false): array;

    /**
     * @return $this
     */
    public function setAmount(Decimal $amount): static;

    public function getAmount(): Decimal;

    public function getOriginalPrice(): PriceInterface;

    public function getOriginalTotalPrice(): PriceInterface;

    public function getEnvironment(): EnvironmentInterface;

    /**
     * @return $this
     */
    public function setEnvironment(EnvironmentInterface $environment): static;

    public function hasDiscount(): bool;

    public function getDiscount(): PriceInterface;

    public function getTotalDiscount(): PriceInterface;

    /**
     * Get discount in percent
     *
     * @return float
     */
    public function getDiscountPercent(): float;

    public function hasRulesApplied(): bool;
}
