<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\OfferTool;

/**
 * Abstract base class for offer pimcore objects
 */
class AbstractOffer extends \Pimcore\Model\Object\Concrete {

    /**
     * @return string
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getOffernumber() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getOffernumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $offernumber
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setOffernumber($offernumber) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setOffernumber is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getTotalPrice() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param float $totalPrice
     */
    public function setTotalPriceBeforeDiscount($totalPrice) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setTotalPriceBeforeDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getTotalPriceBeforeDiscount() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getTotalPriceBeforeDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param float $totalPrice
     */
    public function setTotalPrice($totalPrice) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setTotalPrice is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getDiscount() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param float $discount
     */
    public function setDiscount($discount) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getDiscountType() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getDiscountType is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param string $discountType
     */
    public function setDiscountType($discountType) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setDiscountType is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return \Zend_Date
     */
    public function getDateCreated() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getDateCreated is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param \Zend_Date $dateCreated
     */
    public function setDateCreated($dateCreated) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setDateCreated is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return \Zend_Date
     */
    public function getDateValidUntil() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getDateValidUntil is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param \Zend_Date $dateValidUntil
     */
    public function setDateValidUntil($dateValidUntil) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setDateValidUntil is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return AbstractOfferItem[]
     */
    public function getItems() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getItems is not implemented for " . get_class($this));
    }

    /**
     * @param AbstractOfferItem[] $items
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setItems($items) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setItems is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return AbstractOfferItem[]
     */
    public function getCustomItems() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getCustomItems is not implemented for " . get_class($this));
    }

    /**
     * @param AbstractOfferItem[] $customItems
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setCustomItems($customItems) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setCustomItems is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return mixed
     */
    public function getCustomer() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getCustomer is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param mixed $customer
     */
    public function setCustomer($customer) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setCustomer is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getCartId() {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("getCartId is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     * @param string $cartId
     */
    public function setCartId($cartId) {
        throw new \OnlineShop_Framework_Exception_UnsupportedException("setCartId is not implemented for " . get_class($this));
    }


    /**
     * @param AbstractOfferToolProduct $product
     * @param int $amount
     * @return AbstractOfferItem
     */
    public function addCustomItemFromProduct(AbstractOfferToolProduct $product, $amount = 1) {
        $item = $this->getCustomItemByProduct($product);
        if(empty($item)) {

            $service = \OnlineShop_Framework_Factory::getInstance()->getOfferToolService();
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