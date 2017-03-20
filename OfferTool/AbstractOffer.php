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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool;

/**
 * Abstract base class for offer pimcore objects
 */
class AbstractOffer extends \Pimcore\Model\Object\Concrete {

    /**
     * @return string
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function getOffernumber() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getOffernumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $offernumber
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setOffernumber($offernumber) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setOffernumber is not implemented for " . get_class($this));
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
    public function setTotalPriceBeforeDiscount($totalPrice) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setTotalPriceBeforeDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getTotalPriceBeforeDiscount() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getTotalPriceBeforeDiscount is not implemented for " . get_class($this));
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
    public function getDiscount() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $discount
     */
    public function setDiscount($discount) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getDiscountType() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getDiscountType is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param string $discountType
     */
    public function setDiscountType($discountType) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setDiscountType is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return \DateTime
     */
    public function getDateCreated() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getDateCreated is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param \DateTime $dateCreated
     */
    public function setDateCreated($dateCreated) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setDateCreated is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return \DateTime
     */
    public function getDateValidUntil() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getDateValidUntil is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param \DateTime $dateValidUntil
     */
    public function setDateValidUntil($dateValidUntil) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setDateValidUntil is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return AbstractOfferItem[]
     */
    public function getItems() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getItems is not implemented for " . get_class($this));
    }

    /**
     * @param AbstractOfferItem[] $items
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setItems($items) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setItems is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return AbstractOfferItem[]
     */
    public function getCustomItems() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getCustomItems is not implemented for " . get_class($this));
    }

    /**
     * @param AbstractOfferItem[] $customItems
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setCustomItems($customItems) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setCustomItems is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
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
     * @return string
     */
    public function getCartId() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getCartId is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param string $cartId
     */
    public function setCartId($cartId) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setCartId is not implemented for " . get_class($this));
    }


    /**
     * @param AbstractOfferToolProduct $product
     * @param int $amount
     * @return AbstractOfferItem
     */
    public function addCustomItemFromProduct(AbstractOfferToolProduct $product, $amount = 1) {
        $item = $this->getCustomItemByProduct($product);
        if(empty($item)) {

            $service = \OnlineShop\Framework\Factory::getInstance()->getOfferToolService();
            $item = $service->getNewOfferItemObject();
            $item->setParent($this);
            $item->setPublished(true);
            $item->setCartItemKey($product->getId());
            $item->setKey("custom_" . $product->getId());

            $item->setAmount($amount);
            $item->setProduct($product);
            if($product) {
                $item->setProductName($product->getOSName());
                $item->setProductNumber($product->getOSProductNumber());
            }

            $price = 0;
            if($product->getOSPriceInfo($amount)->getTotalPrice()) {
                $price = $product->getOSPriceInfo($amount)->getTotalPrice()->getAmount();
            }

            $item->setOriginalTotalPrice($price);
            $item->setFinalTotalPrice($price);
        } else {
            $item->setAmount($item->getAmount() + $amount);

            $price = 0;
            if($product->getOSPriceInfo($item->getAmount())->getTotalPrice()) {
                $price = $product->getOSPriceInfo($item->getAmount())->getTotalPrice()->getAmount();
            }

            $item->setOriginalTotalPrice($price);
            $item->setFinalTotalPrice($price);

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
     * @return AbstractOfferItem
     */
    public function getCustomItemsByGroup($groupName) {
        $groupedItems = array();
        foreach($this->getCustomItems() as $item) {
            $product = $item->getProduct();
            if($product) {
                $groupedItems[$product->getProductGroup()] = $item;
            }
        }
        return $groupedItems[$groupName];
    }


    /**
     * @param AbstractOfferToolProduct $product
     * @return null|AbstractOfferItem
     */
    public function getCustomItemByProduct(AbstractOfferToolProduct $product) {
        $items = $this->getCustomItems();
        foreach($items as $item) {
            if($item->getProduct()->getId() == $product->getId()) {
                return $item;
            }
        }
        return null;
    }

}