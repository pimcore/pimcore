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
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Model\DataObject\Concrete;

/**
 * Abstract base class for offer pimcore objects
 */
abstract class AbstractOffer extends Concrete
{
    abstract public function getOffernumber(): ?string;

    /**
     * @return $this
     */
    abstract public function setOffernumber(?string $offernumber): static;

    abstract public function getTotalPrice(): ?string;

    /**
     * @return $this
     */
    abstract public function setTotalPriceBeforeDiscount(?string $totalPriceBeforeDiscount): static;

    abstract public function getTotalPriceBeforeDiscount(): ?string;

    /**
     * @return $this
     */
    abstract public function setTotalPrice(?string $totalPrice): static;

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

    abstract public function getDateCreated(): ?\Carbon\Carbon;

    /**
     * @return $this
     */
    abstract public function setDateCreated(?\Carbon\Carbon $dateCreated): static;

    abstract public function getDateValidUntil(): ?\Carbon\Carbon;

    /**
     * @return $this
     */
    abstract public function setDateValidUntil(?\Carbon\Carbon $dateValidUntil): static;

    /**
     * @return AbstractOfferItem[]
     */
    abstract public function getItems(): array;

    /**
     * @param AbstractOfferItem[] $items
     *
     * @return $this
     */
    abstract public function setItems(?array $items): static;

    /**
     * @return AbstractOfferItem[]
     */
    abstract public function getCustomItems(): array;

    /**
     * @param AbstractOfferItem[] $customItems
     *
     * @return $this
     */
    abstract public function setCustomItems(?array $customItems): static;

    /**
     * @throws UnsupportedException
     *
     * @return mixed
     */
    public function getCustomer(): mixed
    {
        throw new UnsupportedException('getCustomer is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param mixed $customer
     *
     * @return $this
     */
    public function setCustomer(mixed $customer): static
    {
        throw new UnsupportedException('setCustomer is not implemented for ' . get_class($this));
    }

    abstract public function getCartId(): ?string;

    /**
     * @return $this
     */
    abstract public function setCartId(?string $cartId): static;

    public function addCustomItemFromProduct(AbstractOfferToolProduct $product, int $amount = 1): ?AbstractOfferItem
    {
        $item = $this->getCustomItemByProduct($product);
        if (empty($item)) {
            $service = Factory::getInstance()->getOfferToolService();
            $item = $service->getNewOfferItemObject();
            $item->setParent($this);
            $item->setPublished(true);
            $item->setCartItemKey((string) $product->getId());
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

            $price = $product->getOSPriceInfo((int) $item->getAmount())->getTotalPrice()->getAmount();

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

    public function getCustomItemsByGroup(string $groupName): ?AbstractOfferItem
    {
        $groupedItems = [];
        foreach ($this->getCustomItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $groupedItems[$product->getProductGroup()] = $item;
            }
        }

        return $groupedItems[$groupName] ?? null;
    }

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
