<?php

class OnlineShop_Framework_AbstractOrderItem extends Object_Concrete {

    /**
     * @return OnlineShop_Framework_AbstractProduct
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getProduct() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getProduct is not implemented for " . get_class($this));
    }

    /**
     * @param OnlineShop_Framework_AbstractProduct $product
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setProduct($product) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setProduct is not implemented for " . get_class($this));
    }


    /**
     * @return string
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getProductNumber() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getProductNumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $productNumber
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setProductNumber($productNumber) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setProductNumber is not implemented for " . get_class($this));
    }


    /**
     * @return string
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getProductName() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getProductName is not implemented for " . get_class($this));
    }

    /**
     * @param string $productName
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setProductName($productName) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setProductName is not implemented for " . get_class($this));
    }

    /**
     * @return double
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getAmount() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getAmount is not implemented for " . get_class($this));
    }

    /**
     * @param double $amount
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setAmount($amount) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setAmount is not implemented for " . get_class($this));
    }


    /**
     * @return double
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getTotalPrice() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @param double $totalPrice
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setTotalPrice($totalPrice) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setTotalPrice is not implemented for " . get_class($this));
    }


    /**
     * @return OnlineShop_Framework_AbstractOrderItem[]
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getSubItems() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getSubItems is not implemented for " . get_class($this));
    }

    /**
     * @param OnlineShop_Framework_AbstractOrderItem[] $subItems
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setSubItems($subItems) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setSubItems is not implemented for " . get_class($this));
    }
}
