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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;

interface CartPriceCalculatorInterface
{
    /**
     * @param EnvironmentInterface $environment
     * @param CartInterface $cart
     * @param ICartPriceModificator[] $modificators
     */
    public function __construct(EnvironmentInterface $environment, CartInterface $cart, array $modificators = []);

    /**
     * (Re-)initialize standard price modificators, e.g. after removing an item from a cart
     * within the same request, such as an AJAX-call.
     */
    public function initModificators();

    /**
     * Calculates cart sums and saves results
     *
     * @return void
     */
    public function calculate($ignorePricingRules = false);

    /**
     * Reset calculations
     *
     * @return void
     */
    public function reset();

    /**
     * Returns sub total of cart
     *
     * @return PriceInterface $price
     */
    public function getSubTotal(): PriceInterface;

    /**
     * Returns grand total of cart
     *
     * @return PriceInterface $price
     */
    public function getGrandTotal(): PriceInterface;

    /**
     * Returns all price modifications which apply for this cart
     *
     * @return ModificatedPriceInterface[] $priceModification
     */
    public function getPriceModifications(): array;

    /**
     * Manually add a modificator to this cart. By default they are loaded from the configuration.
     *
     * @param ICartPriceModificator $modificator
     *
     * @return CartPriceCalculatorInterface
     */
    public function addModificator(ICartPriceModificator $modificator);

    /**
     * Manually remove a modificator from this cart.
     *
     * @param ICartPriceModificator $modificator
     *
     * @return CartPriceCalculatorInterface
     */
    public function removeModificator(ICartPriceModificator $modificator);

    /**
     * Returns all modificators
     *
     * @return ICartPriceModificator[]
     */
    public function getModificators(): array;
}

class_alias(CartPriceCalculatorInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartPriceCalculator');
