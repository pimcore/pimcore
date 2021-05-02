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

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\Element\AbstractElement;

/**
 * Abstract base class for order item pimcore objects
 */
abstract class AbstractOrderItem extends Concrete
{
    /**
     * @return AbstractElement
     */
    abstract public function getProduct(): ?AbstractElement;

    /**
     * @param AbstractElement $product
     */
    abstract public function setProduct(?AbstractElement $product);

    /**
     * @return string|null
     */
    abstract public function getProductNumber(): ?string;

    /**
     * @param string|null $productNumber
     */
    abstract public function setProductNumber(?string $productNumber);

    /**
     * @return string|null
     */
    abstract public function getProductName(): ?string;

    /**
     * @param string|null $productName
     */
    abstract public function setProductName(?string $productName);

    /**
     * @return float|null
     */
    abstract public function getAmount(): ?float;

    /**
     * @param float|null $amount
     *
     * @return mixed
     */
    abstract public function setAmount(?float $amount);

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
     * @param string|null $totalNetPrice
     */
    abstract public function setTotalNetPrice(?string $totalNetPrice);

    /**
     * @return array
     */
    abstract public function getTaxInfo(): array;

    /**
     * @param array $taxInfo
     */
    abstract public function setTaxInfo(?array $taxInfo);

    /**
     * @return AbstractOrderItem[]
     */
    abstract public function getSubItems(): array;

    /**
     * @param AbstractOrderItem[] $subItems
     */
    abstract public function setSubItems(?array $subItems);

    /**
     * @return Fieldcollection
     */
    abstract public function getPricingRules();

    /**
     * @param Fieldcollection $pricingRules
     *
     * @return $this
     */
    abstract public function setPricingRules(?Fieldcollection $pricingRules);

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
    abstract public function getComment(): ?string;

    /**
     * @param string|null $comment
     *
     * @return $this
     */
    abstract public function setComment(?string $comment);

    /**
     * is the order item cancel able
     *
     * @return bool
     */
    public function isCancelAble(): bool
    {
        return !$this->isCanceled();
    }

    /**
     * is the order item edit able
     *
     * @return bool
     */
    public function isEditAble(): bool
    {
        return !$this->isCanceled();
    }

    /**
     * ist eine rückerstattung erlaubt
     *
     * @return bool
     */
    public function isComplaintAble(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->getOrderState() == AbstractOrder::ORDER_STATE_CANCELLED;
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder(): ?AbstractOrder
    {
        $possibleOrderObject = $this;
        while ($possibleOrderObject && !$possibleOrderObject instanceof AbstractOrder) {
            $possibleOrderObject = $possibleOrderObject->getParent();
        }

        if ($possibleOrderObject instanceof AbstractOrder) {
            return $possibleOrderObject;
        }

        return null;
    }
}
