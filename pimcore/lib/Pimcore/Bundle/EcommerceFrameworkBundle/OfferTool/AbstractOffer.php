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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

/**
 * Abstract base class for offer pimcore objects
 */
class AbstractOffer extends \Pimcore\Model\Object\Concrete
{
    /**
     * @return string
     *
     * @throws UnsupportedException
     */
    public function getOffernumber()
    {
        throw new UnsupportedException('getOffernumber is not implemented for ' . get_class($this));
    }

    /**
     * @param string $offernumber
     *
     * @throws UnsupportedException
     */
    public function setOffernumber($offernumber)
    {
        throw new UnsupportedException('setOffernumber is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string|float|int
     */
    public function getTotalPrice()
    {
        throw new UnsupportedException('getTotalPrice is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string|float|int $totalPrice
     */
    public function setTotalPriceBeforeDiscount($totalPrice)
    {
        throw new UnsupportedException('setTotalPriceBeforeDiscount is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string|float|int
     */
    public function getTotalPriceBeforeDiscount()
    {
        throw new UnsupportedException('getTotalPriceBeforeDiscount is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string|float|int $totalPrice
     */
    public function setTotalPrice($totalPrice)
    {
        throw new UnsupportedException('setTotalPrice is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return float
     */
    public function getDiscount()
    {
        throw new UnsupportedException('getDiscount is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param float $discount
     */
    public function setDiscount($discount)
    {
        throw new UnsupportedException('setDiscount is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getDiscountType()
    {
        throw new UnsupportedException('getDiscountType is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string $discountType
     */
    public function setDiscountType($discountType)
    {
        throw new UnsupportedException('setDiscountType is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        throw new UnsupportedException('getDateCreated is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param \DateTime $dateCreated
     */
    public function setDateCreated($dateCreated)
    {
        throw new UnsupportedException('setDateCreated is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return \DateTime
     */
    public function getDateValidUntil()
    {
        throw new UnsupportedException('getDateValidUntil is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param \DateTime $dateValidUntil
     */
    public function setDateValidUntil($dateValidUntil)
    {
        throw new UnsupportedException('setDateValidUntil is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return AbstractOfferItem[]
     */
    public function getItems()
    {
        throw new UnsupportedException('getItems is not implemented for ' . get_class($this));
    }

    /**
     * @param AbstractOfferItem[] $items
     *
     * @throws UnsupportedException
     */
    public function setItems($items)
    {
        throw new UnsupportedException('setItems is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return AbstractOfferItem[]
     */
    public function getCustomItems()
    {
        throw new UnsupportedException('getCustomItems is not implemented for ' . get_class($this));
    }

    /**
     * @param AbstractOfferItem[] $customItems
     *
     * @throws UnsupportedException
     */
    public function setCustomItems($customItems)
    {
        throw new UnsupportedException('setCustomItems is not implemented for ' . get_class($this));
    }

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
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getCartId()
    {
        throw new UnsupportedException('getCartId is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string $cartId
     */
    public function setCartId($cartId)
    {
        throw new UnsupportedException('setCartId is not implemented for ' . get_class($this));
    }

    /**
     * @param AbstractOfferToolProduct $product
     * @param int $amount
     *
     * @return AbstractOfferItem
     */
    public function addCustomItemFromProduct(AbstractOfferToolProduct $product, $amount = 1)
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
            if ($product) {
                $item->setProductName($product->getOSName());
                $item->setProductNumber($product->getOSProductNumber());
            }

            $price = Decimal::zero();
            if ($product->getOSPriceInfo($amount)->getTotalPrice()) {
                $price = $product->getOSPriceInfo($amount)->getTotalPrice()->getAmount();
            }

            $item->setOriginalTotalPrice($price->asString());
            $item->setFinalTotalPrice($price->asString());
        } else {
            $item->setAmount($item->getAmount() + $amount);

            $price = Decimal::zero();
            if ($product->getOSPriceInfo($item->getAmount())->getTotalPrice()) {
                $price = $product->getOSPriceInfo($item->getAmount())->getTotalPrice()->getAmount();
            }

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
    public function getCustomItemsByGroup($groupName)
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
    public function getCustomItemByProduct(AbstractOfferToolProduct $product)
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
