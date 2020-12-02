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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;

/**
 * Abstract base class for order pimcore objects
 */
abstract class AbstractOrder extends Concrete
{
    public const ORDER_STATE_COMMITTED = 'committed';
    public const ORDER_STATE_CANCELLED = 'cancelled';
    public const ORDER_STATE_PAYMENT_PENDING = 'paymentPending';
    public const ORDER_STATE_PAYMENT_INIT = 'paymentInit';
    public const ORDER_STATE_PAYMENT_AUTHORIZED = 'paymentAuthorized';
    public const ORDER_STATE_ABORTED = 'aborted';
    public const ORDER_PAYMENT_STATE_ABORTED_BUT_RESPONSE = 'abortedButResponseReceived';

    /**
     * @return string
     */
    abstract public function getOrdernumber();

    /**
     * @param string $ordernumber
     */
    abstract public function setOrdernumber($ordernumber);

    /**
     * @return float
     */
    abstract public function getSubTotalPrice();

    /**
     * @param float $subTotalPrice
     */
    abstract public function setSubTotalPrice($subTotalPrice);

    /**
     * @return float
     */
    abstract public function getSubTotalNetPrice();

    /**
     * @param float $subTotalPrice
     */
    abstract public function setSubTotalNetPrice($subTotalPrice);

    /**
     * @return float
     */
    abstract public function getTotalPrice();

    /**
     * @param float $totalPrice
     */
    abstract public function setTotalPrice($totalPrice);

    /**
     * @return float
     */
    abstract public function getTotalNetPrice();

    /**
     * @param float $totalPrice
     */
    abstract public function setTotalNetPrice($totalPrice);

    /**
     * @return array
     */
    abstract public function getTaxInfo();

    /**
     * @param array $taxInfo
     */
    abstract public function setTaxInfo($taxInfo);

    /**
     * @return \DateTime
     */
    abstract public function getOrderdate();

    /**
     * @param \DateTime $orderdate
     */
    abstract public function setOrderdate($orderdate);

    /**
     * @return AbstractOrderItem[]
     */
    abstract public function getItems();

    /**
     * @param AbstractOrderItem[] $items
     */
    abstract public function setItems($items);

    /**
     * @return AbstractOrderItem[]
     */
    abstract public function getGiftItems();

    /**
     * @param AbstractOrderItem[] $giftItems
     */
    abstract public function setGiftItems($giftItems);

    /**
     * @return \Pimcore\Model\DataObject\Customer
     */
    abstract public function getCustomer();

    /**
     * @param \Pimcore\Model\DataObject\Customer $customer
     */
    abstract public function setCustomer($customer);

    /**
     * @return Fieldcollection
     */
    abstract public function getPriceModifications();

    /**
     * @param Fieldcollection $priceModifications
     */
    abstract public function setPriceModifications($priceModifications);

    /**
     * @return string
     */
    abstract public function getOrderState();

    /**
     * @param string $orderState
     *
     * @return $this
     */
    abstract public function setOrderState($orderState);

    /**
     * @return int
     */
    abstract public function getCartId();

    /**
     * @param int $cartId
     *
     * @return void
     */
    abstract public function setCartId($cartId);

    /**
     * @return Fieldcollection
     */
    abstract public function getPaymentInfo();

    /**
     * @param Fieldcollection $paymentInfo
     */
    abstract public function setPaymentInfo($paymentInfo);

    /**
     * returns latest payment info entry
     *
     * @return AbstractPaymentInformation
     */
    public function getLastPaymentInfo()
    {
        if ($this->getPaymentInfo()) {
            $items = $this->getPaymentInfo()->getItems();

            $item = end($items);

            if ($item instanceof AbstractPaymentInformation) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    abstract public function getComment();

    /**
     * @param string $comment
     *
     * @return $this
     */
    abstract public function setComment($comment);

    /**
     * @return string
     */
    abstract public function getCustomerEMail();

    /**
     * @param string $customerEMail
     *
     * @return $this
     */
    abstract public function setCustomerEMail($customerEMail);

    /**
     * @return string
     */
    abstract public function getCustomerCountry();

    /**
     * @param string $customerCountry
     *
     * @return $this
     */
    abstract public function setCustomerCountry($customerCountry);

    /**
     * @return string
     */
    abstract public function getCustomerCity();

    /**
     * @param string $customerCity
     *
     * @return $this
     */
    abstract public function setCustomerCity($customerCity);

    /**
     * @return string
     */
    abstract public function getCustomerZip();

    /**
     * @param string $customerZip
     *
     * @return $this
     */
    abstract public function setCustomerZip($customerZip);

    /**
     * @return string
     */
    abstract public function getCustomerStreet();

    /**
     * @param string $customerStreet
     *
     * @return $this
     */
    abstract public function setCustomerStreet($customerStreet);

    /**
     * @return string
     */
    abstract public function getCustomerCompany();

    /**
     * @param string $customerCompany
     *
     * @return $this
     */
    abstract public function setCustomerCompany($customerCompany);

    /**
     * @return string
     */
    abstract public function getCustomerFirstname();

    /**
     * @param string $customerFirstname
     *
     * @return $this
     */
    abstract public function setCustomerFirstname($customerFirstname);

    /**
     * @return string
     */
    abstract public function getCustomerLastname();

    /**
     * @param string $customerLastname
     *
     * @return $this
     */
    abstract public function setCustomerLastname($customerLastname);

    /**
     * @return string
     */
    abstract public function getDeliveryCountry();

    /**
     * @param string $deliveryCountry
     *
     * @return $this
     */
    abstract public function setDeliveryCountry($deliveryCountry);

    /**
     * @return string
     */
    abstract public function getDeliveryCity();

    /**
     * @param string $deliveryCity
     *
     * @return $this
     */
    abstract public function setDeliveryCity($deliveryCity);

    /**
     * @return string
     */
    abstract public function getDeliveryZip();

    /**
     * @param string $deliveryZip
     *
     * @return $this
     */
    abstract public function setDeliveryZip($deliveryZip);

    /**
     * @return string
     */
    abstract public function getDeliveryStreet();

    /**
     * @param string $deliveryStreet
     *
     * @return $this
     */
    abstract public function setDeliveryStreet($deliveryStreet);

    /**
     * @return string
     */
    abstract public function getDeliveryCompany();

    /**
     * @param string $deliveryCompany
     *
     * @return $this
     */
    abstract public function setDeliveryCompany($deliveryCompany);

    /**
     * @return string
     */
    abstract public function getDeliveryFirstname();

    /**
     * @param string $deliveryFirstname
     *
     * @return $this
     */
    abstract public function setDeliveryFirstname($deliveryFirstname);

    /**
     * @return string
     */
    abstract public function getDeliveryLastname();

    /**
     * @param string $deliveryLastname
     *
     * @return $this
     */
    abstract public function setDeliveryLastname($deliveryLastname);

    /**
     * @return bool
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
    abstract public function setCurrency($currency);

    /**
     * @return string
     */
    abstract public function getCurrency();

    /**
     * Get voucherTokens - Voucher Tokens
     *
     * @return array
     */
    abstract public function getVoucherTokens();

    /**
     * Set voucherTokens - Voucher Tokens
     *
     * @param array $voucherTokens
     *
     * @return \Pimcore\Model\DataObject\OnlineShopOrder
     */
    abstract public function setVoucherTokens($voucherTokens);

    /**
     * Get cartHash - Cart Hash
     *
     * @return float|null
     */
    abstract public function getCartHash();
}
