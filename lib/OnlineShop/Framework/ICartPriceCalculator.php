<?php

/**
 * Interface OnlineShop_Framework_ICartPriceCalculator
 */
interface OnlineShop_Framework_ICartPriceCalculator {

    public function __construct($config, OnlineShop_Framework_ICart $cart);

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
     * @return OnlineShop_Framework_IPrice $price
     */
    public function getSubTotal();

    /**
     * returns all price modifications which apply for this cart
     *
     * @return OnlineShop_Framework_IModificatedPrice[] $priceModification
     */
    public function getPriceModifications();

    /**
     * returns grand total of cart
     *
     * @return OnlineShop_Framework_IPrice $price
     */
    public function getGrandTotal();

    /**
     * manually add a modificator to this cart. by default they are loaded from the configuration
     *
     * @param OnlineShop_Framework_ICartPriceModificator $modificator
     *
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function addModificator(OnlineShop_Framework_ICartPriceModificator $modificator);

    /**
     * returns all modificators
     *
     * @return OnlineShop_Framework_ICartPriceModificator[]
     */
    public function getModificators();

    /**
     * manually remove a modificator from this cart.
     *
     * @param OnlineShop_Framework_ICartPriceModificator $modificator
     *
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function removeModificator(OnlineShop_Framework_ICartPriceModificator $modificator);
}
