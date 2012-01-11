<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 10.01.12
 * Time: 15:32
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_PriceWrapper {

    /** @var OnlineShop_Framework_IPrice */
    private $price;

    private $extendedPriceInfo;


    public function setExtendedPriceInfo($extendedPriceInfo) {
        $this->extendedPriceInfo = $extendedPriceInfo;
    }

    public function getExtendedPriceInfo() {
        return $this->extendedPriceInfo;
    }

    /**
     * @param \OnlineShop_Framework_IPrice $price
     */
    public function setPrice($price) {
        $this->price = $price;
    }

    /**
     * @return \OnlineShop_Framework_IPrice
     */
    public function getPrice() {
        return $this->price;
    }
}
