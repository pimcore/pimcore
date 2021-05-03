<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Carbon\Carbon;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\Element\AbstractElement;

/**
 * Abstract base class for order pimcore objects
 */
abstract class AbstractOrder extends Concrete
{
    const ORDER_STATE_COMMITTED = 'committed';
    const ORDER_STATE_CANCELLED = 'cancelled';
    const ORDER_STATE_PAYMENT_PENDING = 'paymentPending';
    const ORDER_STATE_PAYMENT_INIT = 'paymentInit';
    const ORDER_STATE_PAYMENT_AUTHORIZED = 'paymentAuthorized';
    const ORDER_STATE_ABORTED = 'aborted';
    const ORDER_PAYMENT_STATE_ABORTED_BUT_RESPONSE = 'abortedButResponseReceived';

    /**
     * @return string|null
     */
    abstract public function getOrdernumber(): ?string;

    /**
     * @param string|null $ordernumber
     */
    abstract public function setOrdernumber(?string $ordernumber);

    /**
     * @return string|null
     */
    abstract public function getSubTotalPrice(): ?string;

    /**
     * @param string|null $subTotalPrice
     */
    abstract public function setSubTotalPrice(?string $subTotalPrice);

    /**
     * @return string|null
     */
    abstract public function getSubTotalNetPrice(): ?string;

    /**
     * @param string|null $subTotalPrice
     */
    abstract public function setSubTotalNetPrice(?string $subTotalPrice);

    /**
     * @return string|null
     */
    abstract public function getTotalPrice(): ?string;

    /**
     * @param string|null $totalPrice
     */
    abstract public function setTotalPrice(?string $totalPrice);

    /**
     * @return string|null
     */
    abstract public function getTotalNetPrice(): ?string;

    /**
     * @param string|null $totalPrice
     */
    abstract public function setTotalNetPrice(?string $totalPrice);

    /**
     * @return array
     */
    abstract public function getTaxInfo(): array;

    /**
     * @param array|null $taxInfo
     */
    abstract public function setTaxInfo(?array $taxInfo);

    /**
     * @return Carbon|null
     */
    abstract public function getOrderdate(): ?Carbon;

    /**
     * @param Carbon|null $orderdate
     */
    abstract public function setOrderdate(?Carbon $orderdate);

    /**
     * @return AbstractOrderItem[]
     */
    abstract public function getItems(): array;

    /**
     * @param AbstractOrderItem[] $items
     */
    abstract public function setItems(?array $items);

    /**
     * @return AbstractOrderItem[]
     */
    abstract public function getGiftItems(): array;

    /**
     * @param AbstractOrderItem[] $giftItems
     */
    abstract public function setGiftItems(?array $giftItems);

    /**
     * @return AbstractElement|null
     */
    abstract public function getCustomer(): ?AbstractElement;

    /**
     * @param AbstractElement|null $customer
     */
    abstract public function setCustomer(?AbstractElement $customer);

    /**
     * @return Fieldcollection|null
     */
    abstract public function getPriceModifications();

    /**
     * @param Fieldcollection|null $priceModifications
     */
    abstract public function setPriceModifications(?Fieldcollection $priceModifications);

    /**
     * @return string|null
     */
    abstract public function getOrderState(): ?string;

    /**
     * @param string|null $orderState
     *
     * @return $this
     */
    abstract public function setOrderState(?string $orderState);

    /**
     * @return string|null
     */
    abstract public function getCartId(): ?string;

    /**
     * @param string|null $cartId
     *
     * @return void
     */
    abstract public function setCartId(?string $cartId);

    /**
     * @return Fieldcollection|null
     */
    abstract public function getPaymentInfo();

    /**
     * @param Fieldcollection|null $paymentInfo
     */
    abstract public function setPaymentInfo(?\Pimcore\Model\DataObject\Fieldcollection $paymentInfo);

    /**
     * @return \Pimcore\Model\DataObject\Objectbrick|null
     */
    abstract public function getPaymentProvider(): ?\Pimcore\Model\DataObject\Objectbrick;

    /**
     * returns latest payment info entry
     *
     * @return AbstractPaymentInformation
     */
    public function getLastPaymentInfo(): ?AbstractPaymentInformation
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
     * @return string|null
     */
    abstract public function getComment(): ?string;

    /**
     * @param string|null $comment
     *
     * @return $this
     */
    abstract public function setComment(?string $comment);

    /**
     * @return string|null
     */
    abstract public function getCustomerEMail(): ?string;

    /**
     * @param string|null $customerEMail
     *
     * @return $this
     */
    abstract public function setCustomerEMail(?string $customerEMail);

    /**
     * @return string|null
     */
    abstract public function getCustomerCountry(): ?string;

    /**
     * @param string|null $customerCountry
     *
     * @return $this
     */
    abstract public function setCustomerCountry(?string $customerCountry);

    /**
     * @return string|null
     */
    abstract public function getCustomerCity(): ?string;

    /**
     * @param string|null $customerCity
     *
     * @return $this
     */
    abstract public function setCustomerCity(?string $customerCity);

    /**
     * @return string|null
     */
    abstract public function getCustomerZip(): ?string;

    /**
     * @param string|null $customerZip
     *
     * @return $this
     */
    abstract public function setCustomerZip(?string $customerZip);

    /**
     * @return string|null
     */
    abstract public function getCustomerStreet(): ?string;

    /**
     * @param string|null $customerStreet
     *
     * @return $this
     */
    abstract public function setCustomerStreet(?string $customerStreet);

    /**
     * @return string|null
     */
    abstract public function getCustomerCompany(): ?string;

    /**
     * @param string|null $customerCompany
     *
     * @return $this
     */
    abstract public function setCustomerCompany(?string $customerCompany);

    /**
     * @return string|null
     */
    abstract public function getCustomerFirstname(): ?string;

    /**
     * @param string|null $customerFirstname
     *
     * @return $this
     */
    abstract public function setCustomerFirstname(?string $customerFirstname);

    /**
     * @return string
     */
    abstract public function getCustomerLastname(): ?string;

    /**
     * @param string|null $customerLastname
     *
     * @return $this
     */
    abstract public function setCustomerLastname(?string $customerLastname);

    /**
     * @return string|null
     */
    abstract public function getDeliveryCountry(): ?string;

    /**
     * @param string|null $deliveryCountry
     *
     * @return $this
     */
    abstract public function setDeliveryCountry(?string $deliveryCountry);

    /**
     * @return string|null
     */
    abstract public function getDeliveryCity(): ?string;

    /**
     * @param string|null $deliveryCity
     *
     * @return $this
     */
    abstract public function setDeliveryCity(?string $deliveryCity);

    /**
     * @return string|null
     */
    abstract public function getDeliveryZip(): ?string;

    /**
     * @param string|null $deliveryZip
     *
     * @return $this
     */
    abstract public function setDeliveryZip(?string $deliveryZip);

    /**
     * @return string|null
     */
    abstract public function getDeliveryStreet(): ?string;

    /**
     * @param string|null $deliveryStreet
     *
     * @return $this
     */
    abstract public function setDeliveryStreet(?string $deliveryStreet);

    /**
     * @return string|null
     */
    abstract public function getDeliveryCompany(): ?string;

    /**
     * @param string|null $deliveryCompany
     *
     * @return $this
     */
    abstract public function setDeliveryCompany(?string $deliveryCompany);

    /**
     * @return string|null
     */
    abstract public function getDeliveryFirstname(): ?string;

    /**
     * @param string|null $deliveryFirstname
     *
     * @return $this
     */
    abstract public function setDeliveryFirstname(?string $deliveryFirstname);

    /**
     * @return string|null
     */
    abstract public function getDeliveryLastname(): ?string;

    /**
     * @param string|null $deliveryLastname
     *
     * @return $this
     */
    abstract public function setDeliveryLastname(?string $deliveryLastname);

    /**
     * @return bool
     */
    public function hasDeliveryAddress(): bool
    {
        return
            ($this->getDeliveryFirstname() != '' || $this->getDeliveryLastname())
            && $this->getDeliveryStreet()
            && $this->getDeliveryCity()
            && $this->getDeliveryZip()
        ;
    }

    /**
     * @param string|null $currency
     *
     * @return $this
     */
    abstract public function setCurrency(?string $currency);

    /**
     * @return string|null
     */
    abstract public function getCurrency(): ?string;

    /**
     * Get voucherTokens - Voucher Tokens
     *
     * @return array
     */
    abstract public function getVoucherTokens(): array;

    /**
     * Set voucherTokens - Voucher Tokens
     *
     * @param \Pimcore\Model\DataObject\OnlineShopVoucherToken[]|null $voucherTokens
     *
     * @return OnlineShopOrder
     */
    abstract public function setVoucherTokens(?array $voucherTokens);

    /**
     * Get cartHash - Cart Hash
     *
     * @return int|null
     */
    abstract public function getCartHash(): ?int;

    /**
     * Set cartHash - Cart Hash
     *
     * @param int|null $cartHash
     *
     * @return $this
     */
    abstract public function setCartHash(?int $cartHash);

    /**
     * Set successorOrder - Successor Order
     *
     * @param \Pimcore\Model\DataObject\OnlineShopOrder $successorOrder
     *
     * @return \Pimcore\Model\DataObject\OnlineShopOrder
     */
    abstract public function setSuccessorOrder(?\Pimcore\Model\Element\AbstractElement $successorOrder);
}
