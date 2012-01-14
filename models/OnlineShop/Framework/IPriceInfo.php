<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 11.01.12
 * Time: 11:49
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_IPriceInfo {
    const MIN_PRICE = "min";

    public function getPrice();

    public function isMinPrice();

    public function getQuantity();

    /**
     * @param int|string $quantity
     * numeric quantity or constant OnlineShop_Framework_IPriceInfo::MIN_PRICE
     */
    public function setQuantity($quantity);

    /**
     * @param \OnlineShop_Framework_IPriceSystem $priceSystem
     */
    public function setPriceSystem($priceSystem);


}