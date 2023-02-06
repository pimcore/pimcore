<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
    abstract public function getProduct(): ?AbstractElement;

    /**
     * @return $this
     */
    abstract public function setProduct(?AbstractElement $product): static;

    abstract public function getProductNumber(): ?string;

    /**
     * @return $this
     */
    abstract public function setProductNumber(?string $productNumber): static;

    abstract public function getProductName(): ?string;

    /**
     * @return $this
     */
    abstract public function setProductName(?string $productName): static;

    abstract public function getAmount(): ?float;

    /**
     * @return $this
     */
    abstract public function setAmount(?float $amount): static;

    abstract public function getTotalPrice(): ?string;

    /**
     * @return $this
     */
    abstract public function setTotalPrice(?string $totalPrice): static;

    abstract public function getTotalNetPrice(): ?string;

    /**
     * @return $this
     */
    abstract public function setTotalNetPrice(?string $totalNetPrice): static;

    abstract public function getTaxInfo(): array;

    /**
     * @return $this
     */
    abstract public function setTaxInfo(?array $taxInfo): static;

    /**
     * @return AbstractOrderItem[]
     */
    abstract public function getSubItems(): array;

    /**
     * @param AbstractOrderItem[] $subItems
     *
     * @return $this
     */
    abstract public function setSubItems(?array $subItems): static;

    abstract public function getPricingRules(): ?Fieldcollection;

    /**
     * @return $this
     */
    abstract public function setPricingRules(?Fieldcollection $pricingRules): static;

    abstract public function getOrderState(): ?string;

    /**
     * @return $this
     */
    abstract public function setOrderState(?string $orderState): static;

    abstract public function getComment(): ?string;

    /**
     * @return $this
     */
    abstract public function setComment(?string $comment): static;

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

    public function isCanceled(): bool
    {
        return $this->getOrderState() == AbstractOrder::ORDER_STATE_CANCELLED;
    }

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
