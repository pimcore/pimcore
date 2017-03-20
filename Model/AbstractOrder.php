<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model;

/**
 * Abstract base class for order pimcore objects
 */
class AbstractOrder extends \Pimcore\Model\Object\Concrete {

    const ORDER_STATE_COMMITTED = "committed";
    const ORDER_STATE_CANCELLED = "cancelled";
    const ORDER_STATE_PAYMENT_PENDING = "paymentPending";
    const ORDER_STATE_PAYMENT_AUTHORIZED = "paymentAuthorized";
    const ORDER_STATE_ABORTED = "aborted";

    /**
     * @return string
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function getOrdernumber() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getOrdernumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $ordernumber
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setOrdernumber($ordernumber) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setOrdernumber is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getSubTotalPrice() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getSubTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $subTotalPrice
     */
    public function setSubTotalPrice($subTotalPrice) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setSubTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getSubTotalNetPrice() {
        //prevent throwing an exception for backward compatibility
        \Logger::err("getSubTotalNetPrice not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $subTotalPrice
     */
    public function setSubTotalNetPrice($subTotalPrice) {
        //prevent throwing an exception for backward compatibility
        \Logger::err("setSubTotalNetPrice not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getTotalPrice() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $totalPrice
     */
    public function setTotalPrice($totalPrice) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getTotalNetPrice() {
        //prevent throwing an exception for backward compatibility
        \Logger::err("getTotalNetPrice not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $totalPrice
     */
    public function setTotalNetPrice($totalPrice) {
        //prevent throwing an exception for backward compatibility
        \Logger::err("setTotalNetPrice not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return array
     */
    public function getTaxInfo() {
        //prevent throwing an exception for backward compatibility
        \Logger::err("getTaxInfo not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param array $taxInfo
     */
    public function setTaxInfo($taxInfo) {
        //prevent throwing an exception for backward compatibility
        \Logger::err("setTaxInfo not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return \DateTime
     */
    public function getOrderdate() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getOrderdate is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param \DateTime $orderdate
     */
    public function setOrderdate($orderdate) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setOrderdate is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return \OnlineShop\Framework\Model\AbstractOrderItem[]
     */
    public function getItems() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getItems is not implemented for " . get_class($this));
    }

    /**
     * @param \OnlineShop\Framework\Model\AbstractOrderItem[] $items
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setItems($items) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setItems is not implemented for " . get_class($this));
    }

    public function getGiftItems() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getGiftItems is not implemented for " . get_class($this));
    }

    public function setGiftItems($giftItems) {
        //prevent throwing an exception for backward compatibility
        \Logger::err("setGiftItems not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * committed
     * @return mixed
     */
    public function getCustomer() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getCustomer is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param mixed $customer
     */
    public function setCustomer($customer) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setCustomer is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getPriceModifications() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getPriceModifications is not implemented for " . get_class($this));
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection $priceModifications
     * @return void
     */
    public function setPriceModifications ($priceModifications) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setPriceModifications is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getOrderState() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getOrderState is not implemented for " . get_class($this));
    }

    /**
     * @param string $orderState
     * @return $this
     */
    public function setOrderState ($orderState) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setOrderState is not implemented for " . get_class($this));
    }


    /**
     * @return string
     */
    public function getCartId() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getCartId is not implemented for " . get_class($this));
    }

    /**
     * @param string $cartId
     * @return void
     */
    public function setCartId($cartId) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setCartId is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getPaymentInfo() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getPaymentInfo is not implemented for " . get_class($this));
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection $paymentInfo
     * @return void
     */
    public function setPaymentInfo ($paymentInfo) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setPaymentInfo is not implemented for " . get_class($this));
    }

    /**
     * returns latest payment info entry
     *
     * @return \OnlineShop\Framework\Model\AbstractPaymentInformation
     */
    public function getLastPaymentInfo() {
        if($this->getPaymentInfo()) {
            $items = $this->getPaymentInfo()->getItems();
            return end($items);
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $comment
     *
     * @return $this
     */
    public function setComment($comment)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }


    /**
     * @return mixed
     */
    public function getCustomerEMail()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerEMail
     *
     * @return $this
     */
    public function setCustomerEMail($customerEMail)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerCountry()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerCountry
     *
     * @return $this
     */
    public function setCustomerCountry($customerCountry)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerCity()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerCity
     *
     * @return $this
     */
    public function setCustomerCity($customerCity)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerZip()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerZip
     *
     * @return $this
     */
    public function setCustomerZip($customerZip)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerStreet()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerStreet
     *
     * @return $this
     */
    public function setCustomerStreet($customerStreet)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getCustomerCompany()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerCompany
     *
     * @return $this
     */
    public function setCustomerCompany($customerCompany)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getCustomerFirstname()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerFirstame
     *
     * @return $this
     */
    public function setCustomerFirstname($customerFirstname)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }


    /**
     * @return string
     */
    public function getCustomerLastname()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $customerLastame
     *
     * @return $this
     */
    public function setCustomerLastname($customerLastname)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryEMail()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryEMail
     *
     * @return $this
     */
    public function setDeliveryEMail($deliveryEMail)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryCountry()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryCountry
     *
     * @return $this
     */
    public function setDeliveryCountry($deliveryCountry)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryCity()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryCity
     *
     * @return $this
     */
    public function setDeliveryCity($deliveryCity)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryZip()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryZip
     *
     * @return $this
     */
    public function setDeliveryZip($deliveryZip)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryStreet()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryStreet
     *
     * @return $this
     */
    public function setDeliveryStreet($deliveryStreet)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return mixed
     */
    public function getDeliveryCompany()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryCompany
     *
     * @return $this
     */
    public function setDeliveryCompany($deliveryCompany)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getDeliveryFirstname()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryFirstname
     *
     * @return $this
     */
    public function setDeliveryFirstname($deliveryFirstname)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getDeliveryLastname()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $deliveryLastname
     *
     * @return $this
     */
    public function setDeliveryLastname($deliveryLastname)
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }


    /**
     * @return bool
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function hasDeliveryAddress()
    {
        return
            ($this->getDeliveryFirstname() != '' || $this->getDeliveryLastname())
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
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setPaymentInfo is not implemented for " . get_class($this));
//        return new \Zend_Currency($this->getOrder()->getCurrency(), $this->factory->getEnvironment()->getCurrencyLocale());
    }

    /**
     * Get voucherTokens - Voucher Tokens
     * @return array
     */
    public function getVoucherTokens () {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getVoucherTokens is not implemented for " . get_class($this));
    }

    /**
     * Set voucherTokens - Voucher Tokens
     * @param array $voucherTokens
     * @return \Pimcore\Model\Object\OnlineShopOrder
     */
    public function setVoucherTokens ($voucherTokens) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setVoucherTokens is not implemented for " . get_class($this));
    }

}
