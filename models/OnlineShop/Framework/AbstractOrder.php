<?php

/**
 * Abstract base class for order pimcore objects
 */
class OnlineShop_Framework_AbstractOrder extends Object_Concrete {

    /**
     * @return string
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getOrdernumber() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOrdernumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $ordernumber
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setOrdernumber($ordernumber) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setOrdernumber is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getTotalPrice() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param float $totalPrice
     */
    public function setTotalPrice($totalPrice) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return Zend_Date
     */
    public function getOrderdate() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOrderdate is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param Zend_Date $orderdate
     */
    public function setOrderdate($orderdate) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setOrderdate is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return OnlineShop_Framework_AbstractOrderItem[]
     */
    public function getItems() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getItems is not implemented for " . get_class($this));
    }

    /**
     * @param OnlineShop_Framework_AbstractOrderItem[] $items
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setItems($items) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setItems is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return mixed
     */
    public function getCustomer() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getCustomer is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param mixed $customer
     */
    public function setCustomer($customer) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setCustomer is not implemented for " . get_class($this));
    }

}
