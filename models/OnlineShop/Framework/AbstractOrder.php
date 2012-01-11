<?php

class OnlineShop_Framework_AbstractOrder extends Object_Concrete {

    /**
     * @return string
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getOrdernumber() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOrdernumber is not implemented for " . get_class($this));
    }

    /**
     * @param strimg $ordernumber
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setOrdernumber($ordernumber) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setOrdernumber is not implemented for " . get_class($this));
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
     * @return Zend_Date
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getOrderdate() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOrderdate is not implemented for " . get_class($this));
    }

    /**
     * @param Zend_Date $orderdate
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setOrderdate($orderdate) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setOrderdate is not implemented for " . get_class($this));
    }

    /**
     * @return OnlineShop_Framework_AbstractOrderItem[]
     * @throws OnlineShop_Framework_Exception_UnsupportedException
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
     * @return CustomerDb_Customer
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getCustomer() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getCustomer is not implemented for " . get_class($this));
    }

    /**
     * @param CustomerDb_Customer $customer
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setCustomer($customer) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setCustomer is not implemented for " . get_class($this));
    }


}
