<?php

class OnlineShop_Framework_AbstractSetProductEntry {

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

    public function getId() {
        return $this->getProduct()->getId();
    }
}
