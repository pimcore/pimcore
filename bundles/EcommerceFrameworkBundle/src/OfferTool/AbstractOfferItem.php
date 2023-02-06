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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Model\DataObject\Concrete;

/**
 * Abstract base class for offer item pimcore objects
 */
abstract class AbstractOfferItem extends Concrete
{
    /**
     * @return AbstractOfferToolProduct|null
     */
    abstract public function getProduct(): ?\Pimcore\Model\Element\AbstractElement;

    /**
     * @return $this
     */
    abstract public function setProduct(?\Pimcore\Model\Element\AbstractElement $product): static;

    abstract public function getProductNumber(): ?string;

    /**
     * @return $this
     */
    abstract public function setProductNumber(?string $productNumber): static;

    abstract public function getProductName(): ?string;

    /**
     * @param string|null $productName
     *
     * @return $this
     *
     * @throws UnsupportedException
     */
    abstract public function setProductName(?string $productName): static;

    /**
     * @return float|null
     *
     * @throws UnsupportedException
     */
    abstract public function getAmount(): ?float;

    /**
     * @return $this
     */
    abstract public function setAmount(?float $amount): static;

    abstract public function getOriginalTotalPrice(): ?string;

    /**
     * @return $this
     */
    abstract public function setOriginalTotalPrice(?string $originalTotalPrice): static;

    abstract public function getFinalTotalPrice(): ?string;

    /**
     * @return $this
     */
    abstract public function setFinalTotalPrice(?string $finalTotalPrice): static;

    abstract public function getDiscount(): ?string;

    /**
     * @return $this
     */
    abstract public function setDiscount(?string $discount): static;

    abstract public function getDiscountType(): ?string;

    /**
     * @return $this
     */
    abstract public function setDiscountType(?string $discountType): static;

    abstract public function getSubItems(): array;

    /**
     * @return $this
     */
    abstract public function setSubItems(?array $subItems): static;

    abstract public function getComment(): ?string;

    /**
     * @return $this
     */
    abstract public function setComment(?string $comment): static;

    abstract public function getCartItemKey(): ?string;

    /**
     * @return $this
     */
    abstract public function setCartItemKey(?string $cartItemKey): static;
}
