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
use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IModificatedPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;

interface ICartPriceCalculator
{
    /**
     * @param IEnvironment $environment
     * @param ICart $cart
     * @param ICartPriceModificator[] $modificators
     */
    public function __construct(IEnvironment $environment, ICart $cart, array $modificators = []);

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
    public function calculate();

    /**
     * Reset calculations
     *
     * @return void
     */
    public function reset();

    /**
     * Returns sub total of cart
     *
     * @return IPrice $price
     */
    public function getSubTotal(): IPrice;

    /**
     * Returns grand total of cart
     *
     * @return IPrice $price
     */
    public function getGrandTotal(): IPrice;

    /**
     * Returns all price modifications which apply for this cart
     *
     * @return IModificatedPrice[] $priceModification
     */
    public function getPriceModifications(): array;

    /**
     * Manually add a modificator to this cart. By default they are loaded from the configuration.
     *
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function addModificator(ICartPriceModificator $modificator);

    /**
     * Manually remove a modificator from this cart.
     *
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function removeModificator(ICartPriceModificator $modificator);

    /**
     * Returns all modificators
     *
     * @return ICartPriceModificator[]
     */
    public function getModificators(): array;
}
