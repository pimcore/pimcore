<?php

/**
 * Class for product entry of a set product - container for product and quantity
 */
class OnlineShop_Framework_AbstractSetProductEntry {

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var OnlineShop_Framework_AbstractProduct
     */
    private $product;

    public function __construct(OnlineShop_Framework_AbstractProduct $product, $quantity = 1) {
        $this->product = $product;
        $this->quantity = $quantity;
    }


    /**
     * @param OnlineShop_Framework_AbstractProduct $product
     * @return void
     */
    public function setProduct(OnlineShop_Framework_AbstractProduct $product) {
        $this->product = $product;
    }

    /**
     * @return OnlineShop_Framework_AbstractProduct
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @param  int $quantity
     * @return void
     */
    public function setQuantity($quantity) {
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * returns id of set product
     *
     * @return int
     */
    public function getId() {
        if($this->getProduct()) {
            return $this->getProduct()->getId();
        }
        return null;
    }
}
