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

namespace OnlineShop\Framework\CartManager;
use OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator;
use OnlineShop\Framework\PriceSystem\IModificatedPrice;

/**
 * Interface ICartPriceCalculator
 */
interface ICartPriceCalculator {

    public function __construct($config, ICart $cart);

    /**
     * calculates cart sums and saves results
     *
     * @return void
     */
    public function calculate();

    /**
     * reset calculations
     *
     * @return void
     */
    public function reset();


    /**
     * returns sub total of cart
     *
     * @return \OnlineShop\Framework\PriceSystem\IPrice $price
     */
    public function getSubTotal();

    /**
     * returns all price modifications which apply for this cart
     *
     * @return IModificatedPrice[] $priceModification
     */
    public function getPriceModifications();

    /**
     * returns grand total of cart
     *
     * @return \OnlineShop\Framework\PriceSystem\IPrice $price
     */
    public function getGrandTotal();

    /**
     * manually add a modificator to this cart. by default they are loaded from the configuration
     *
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function addModificator(ICartPriceModificator $modificator);

    /**
     * returns all modificators
     *
     * @return \OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator[]
     */
    public function getModificators();

    /**
     * manually remove a modificator from this cart.
     *
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function removeModificator(ICartPriceModificator $modificator);
}
