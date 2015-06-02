<?php

/**
 * Abstract base class for order pimcore objects
 */
class OnlineShop_Framework_AbstractOrder extends \Pimcore\Model\Object\Concrete {

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
     * @return $this
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

    /**
     * @return mixed
     */
    public function getComment()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $comment
     *
     * @return $this
     */
    public function setComment($comment)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }


    /**
     * @return mixed
     */
    public function getCustomerEMail()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerEMail
     *
     * @return $this
     */
    public function setCustomerEMail($customerEMail)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerCountry()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerCountry
     *
     * @return $this
     */
    public function setCustomerCountry($customerCountry)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerCity()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerCity
     *
     * @return $this
     */
    public function setCustomerCity($customerCity)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerZip()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerZip
     *
     * @return $this
     */
    public function setCustomerZip($customerZip)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerStreet()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerStreet
     *
     * @return $this
     */
    public function setCustomerStreet($customerStreet)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerCompany()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerCompany
     *
     * @return $this
     */
    public function setCustomerCompany($customerCompany)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerName
     *
     * @return $this
     */
    public function setCustomerName($customerName)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryEMail()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryEMail
     *
     * @return $this
     */
    public function setDeliveryEMail($deliveryEMail)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryCountry()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryCountry
     *
     * @return $this
     */
    public function setDeliveryCountry($deliveryCountry)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryCity()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryCity
     *
     * @return $this
     */
    public function setDeliveryCity($deliveryCity)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryZip()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryZip
     *
     * @return $this
     */
    public function setDeliveryZip($deliveryZip)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryStreet()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryStreet
     *
     * @return $this
     */
    public function setDeliveryStreet($deliveryStreet)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryCompany()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryCompany
     *
     * @return $this
     */
    public function setDeliveryCompany($deliveryCompany)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getDeliveryName()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryName
     *
     * @return $this
     */
    public function setDeliveryName($deliveryName)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }


    /**
     * @return bool
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function hasDeliveryAddress()
    {
        return
            $this->getDeliveryName() != ''
            && $this->getDeliveryStreet()
            && $this->getDeliveryCity()
            && $this->getDeliveryZip()
        ;
    }


    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setPaymentInfo is not implemented for " . get_class($this));
//        return new \Zend_Currency($this->getOrder()->getCurrency(), $this->factory->getEnvironment()->getCurrencyLocale());
    }

    /**
     * Get voucherTokens - Voucher Tokens
     * @return array
     */
    public function getVoucherTokens () {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getVoucherTokens is not implemented for " . get_class($this));
    }

    /**
     * Set voucherTokens - Voucher Tokens
     * @param array $voucherTokens
     * @return \Pimcore\Model\Object\OnlineShopOrder
     */
    public function setVoucherTokens ($voucherTokens) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setVoucherTokens is not implemented for " . get_class($this));
    }

}
