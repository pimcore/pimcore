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
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Model\DataObject\Concrete;

/**
 * Abstract base class for offer pimcore objects
 */
abstract class AbstractOffer extends Concrete
{
    /**
     * @return string|null
     */
    abstract public function getOffernumber(): ?string;

    /**
     * @param string|null $offernumber
     */
    abstract public function setOffernumber(?string $offernumber);

    /**
     * @return string|null
     */
    abstract public function getTotalPrice(): ?string;

    /**
     * @param string|null $totalPriceBeforeDiscount
     *
     * @return mixed
     */
    abstract public function setTotalPriceBeforeDiscount(?string $totalPriceBeforeDiscount);

    /**
     * @return string|null
     */
    abstract public function getTotalPriceBeforeDiscount(): ?string;

    /**
     * @param string|null $totalPrice
     *
     * @return mixed
     */
    abstract public function setTotalPrice(?string $totalPrice);

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
     * @return \Carbon\Carbon|null
     */
    abstract public function getDateCreated(): ?\Carbon\Carbon;

    /**
     * @param \Carbon\Carbon|null $dateCreated
     *
     * @return mixed
     */
    abstract public function setDateCreated(?\Carbon\Carbon $dateCreated);

    /**
     * @return \Carbon\Carbon|null
     */
    abstract public function getDateValidUntil(): ?\Carbon\Carbon;

    /**
     * @param \Carbon\Carbon|null $dateValidUntil
     *
     * @return mixed
     */
    abstract public function setDateValidUntil(?\Carbon\Carbon $dateValidUntil);

    /**
     * @return AbstractOfferItem[]
     */
    abstract public function getItems(): array;

    /**
     * @param AbstractOfferItem[] $items
     */
    abstract public function setItems(?array $items);

    /**
     * @return AbstractOfferItem[]
     */
    abstract public function getCustomItems(): array;

    /**
     * @param AbstractOfferItem[] $customItems
     */
    abstract public function setCustomItems(?array $customItems);

    /**
     * @throws UnsupportedException
     *
     * @return mixed
     */
    public function getCustomer()
    {
        throw new UnsupportedException('getCustomer is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param mixed $customer
     */
    public function setCustomer($customer)
    {
        throw new UnsupportedException('setCustomer is not implemented for ' . get_class($this));
    }

    /**
     * @return string|null
     */
    abstract public function getCartId(): ?string;

    /**
     * @param string|null $cartId
     *
     * @return mixed
     */
    abstract public function setCartId(?string $cartId);

    /**
     * @param AbstractOfferToolProduct $product
     * @param int $amount
     *
     * @return AbstractOfferItem
     */
    public function addCustomItemFromProduct(AbstractOfferToolProduct $product, $amount = 1): ?AbstractOfferItem
    {
        $item = $this->getCustomItemByProduct($product);
        if (empty($item)) {
            $service = Factory::getInstance()->getOfferToolService();
            $item = $service->getNewOfferItemObject();
            $item->setParent($this);
            $item->setPublished(true);
            $item->setCartItemKey($product->getId());
            $item->setKey('custom_' . $product->getId());

            $item->setAmount($amount);
            $item->setProduct($product);
            $item->setProductName($product->getOSName());
            $item->setProductNumber($product->getOSProductNumber());

            $price = $product->getOSPriceInfo($amount)->getTotalPrice()->getAmount();

            $item->setOriginalTotalPrice($price->asString());
            $item->setFinalTotalPrice($price->asString());
        } else {
            $item->setAmount($item->getAmount() + $amount);

            $price = $product->getOSPriceInfo($item->getAmount())->getTotalPrice()->getAmount();

            $item->setOriginalTotalPrice($price->asString());
            $item->setFinalTotalPrice($price->asString());
        }
        $item->save();

        $items = $this->getCustomItems();
        $items[] = $item;
        $this->setCustomItems($items);
        $this->save();

        return $item;
    }

    /**
     * @param string $groupName
     *
     * @return AbstractOfferItem
     */
    public function getCustomItemsByGroup($groupName): ?AbstractOfferItem
    {
        $groupedItems = [];
        foreach ($this->getCustomItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $groupedItems[$product->getProductGroup()] = $item;
            }
        }

        return $groupedItems[$groupName];
    }

    /**
     * @param AbstractOfferToolProduct $product
     *
     * @return null|AbstractOfferItem
     */
    public function getCustomItemByProduct(AbstractOfferToolProduct $product): ?AbstractOfferItem
    {
        $items = $this->getCustomItems();
        foreach ($items as $item) {
            if ($item->getProduct()->getId() == $product->getId()) {
                return $item;
            }
        }

        return null;
    }
}
