<?php

/**
 * Abstract base class for order pimcore objects
 */
class OnlineShop_Framework_AbstractOrder extends \Pimcore\Model\Object\AbstractObject {

    const ORDER_STATE_COMMITTED = "committed";
    const ORDER_STATE_CANCELLED = "cancelled";
    const ORDER_STATE_PAYMENT_PENDING = "paymentPending";
    const ORDER_STATE_PAYMENT_AUTHORIZED = "paymentAuthorized";
    const ORDER_STATE_ABORTED = "aborted";

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
     * committed
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

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getPriceModifications() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getPriceModifications is not implemented for " . get_class($this));
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection $priceModifications
     * @return void
     */
    public function setPriceModifications ($priceModifications) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setPriceModifications is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getOrderState() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOrderState is not implemented for " . get_class($this));
    }

    /**
     * @param string $orderState
     * @return void
     */
    public function setOrderState ($orderState) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setOrderState is not implemented for " . get_class($this));
    }


    /**
     * @return string
     */
    public function getCartId() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getCartId is not implemented for " . get_class($this));
    }

    /**
     * @param string $cartId
     * @return void
     */
    public function setCartId($cartId) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setCartId is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getPaymentInfo() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getPaymentInfo is not implemented for " . get_class($this));
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection $paymentInfo
     * @return void
     */
    public function setPaymentInfo ($paymentInfo) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setPaymentInfo is not implemented for " . get_class($this));
    }



}
