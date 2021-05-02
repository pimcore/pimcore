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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Model\DataObject\Concrete;

/**
 * Abstract base class for offer item pimcore objects
 */
abstract class AbstractOfferItem extends Concrete
{
    /**
     * @return AbstractOfferToolProduct
     */
    abstract public function getProduct(): ?\Pimcore\Model\Element\AbstractElement;

    /**
     * @param \Pimcore\Model\Element\AbstractElement|null $product
     */
    abstract public function setProduct(?\Pimcore\Model\Element\AbstractElement $product);

    /**
     * @return string|null
     */
    abstract public function getProductNumber(): ?string;

    /**
     * @param string|null $productNumber
     *
     * @return mixed
     */
    abstract public function setProductNumber(?string $productNumber);

    /**
     * @return string|null
     */
    abstract public function getProductName(): ?string;

    /**
     * @param string $productName
     *
     * @throws UnsupportedException
     */
    abstract public function setProductName(?string $productName);

    /**
     * @return float|null
     *
     * @throws UnsupportedException
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
    abstract public function getOriginalTotalPrice(): ?string;

    /**
     * @param string|null $originalTotalPrice
     *
     * @return mixed
     */
    abstract public function setOriginalTotalPrice(?string $originalTotalPrice);

    /**
     * @return string|null
     */
    abstract public function getFinalTotalPrice(): ?string;

    /**
     * @param string|null $finalTotalPrice
     *
     * @return mixed
     */
    abstract public function setFinalTotalPrice(?string $finalTotalPrice);

    /**
     * @return string|null
     */
    abstract public function getDiscount(): ?string;

    /**
     * @param string|null $discount
     *
     * @return mixed
     */
    abstract public function setDiscount(?string $discount);

    /**
     * @return string|null
     */
    abstract public function getDiscountType(): ?string;

    /**
     * @param string|null $discountType
     *
     * @return mixed
     */
    abstract public function setDiscountType(?string $discountType);

    /**
     * @return array
     */
    abstract public function getSubItems(): array;

    /**
     * @param array|null $subItems
     *
     * @return mixed
     */
    abstract public function setSubItems(?array $subItems);

    /**
     * @return string|null
     */
    abstract public function getComment(): ?string;

    /**
     * @param string|null $comment
     *
     * @return mixed
     */
    abstract public function setComment(?string $comment);

    /**
     * @return string|null
     */
    abstract public function getCartItemKey(): ?string;

    /**
     * @param string|null $cartItemKey
     *
     * @return mixed
     */
    abstract public function setCartItemKey(?string $cartItemKey);
}
