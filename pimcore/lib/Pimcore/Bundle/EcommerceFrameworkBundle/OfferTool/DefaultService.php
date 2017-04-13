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

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;

class DefaultService implements IService
{
    public function __construct($offerClass, $offerItemClass, $parentFolderPath)
    {
        $this->offerClass = $offerClass;
        $this->offerItemClass = $offerItemClass;
        $this->parentFolderPath = strftime($parentFolderPath, time());
        \Pimcore\Model\Object\Service::createFolderByPath($this->parentFolderPath);
    }

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem[] $excludeItems
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer
     */
    public function createNewOfferFromCart(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart, array $excludeItems = [])
    {
        $tempOfferNumber = uniqid('offer_');
        $offer = $this->getNewOfferObject($tempOfferNumber);
        $offer->setOfferNumber($tempOfferNumber);
        $offer->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getAmount());
        $offer->setCartId($cart->getId());
        $offer->save();

        $excludedItemKeys = $this->getExcludedItemKeys($excludeItems);

        $offerItems = [];
        $i = 0;
        foreach ($cart->getItems() as $item) {
            $i++;

            if (!$excludedItemKeys[$item->getItemKey()]) {
                $offerItem = $this->createOfferItem($item, $offer);
                $offerItem->save();

                $offerItems[] = $offerItem;
            }
        }

        $offer->setItems($offerItems);
        $offer->save();

        return $offer;
    }

    protected function getExcludedItemKeys($excludeItems)
    {
        $excludedItemKeys = [];
        if ($excludeItems) {
            foreach ($excludeItems as $item) {
                $excludedItemKeys[$item->getItemKey()] = $item->getItemKey();
            }
        }

        return $excludedItemKeys;
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer
     *
     * @throws \Exception
     */
    protected function getNewOfferObject($tempOfferNumber)
    {
        if (!class_exists($this->offerClass)) {
            throw new \Exception('Offer Class' . $this->offerClass . ' does not exist.');
        }
        $offer = new $this->offerClass();

        /**
         * @var $offer \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer
         */
        $offer->setParent(\Pimcore\Model\Object\Folder::getByPath($this->parentFolderPath));
        $offer->setCreationDate(time());
        $offer->setKey($tempOfferNumber);
        $offer->setPublished(true);
        $offer->setDateCreated(new \DateTime());

        return $offer;
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOfferItem
     *
     * @throws \Exception
     */
    public function getNewOfferItemObject()
    {
        if (!class_exists($this->offerItemClass)) {
            throw new \Exception('OfferItem Class' . $this->offerItemClass . ' does not exist.');
        }

        return new $this->offerItemClass();
    }

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem $item
     * @param $parent
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOfferItem
     */
    protected function createOfferItem(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem $item, $parent)
    {
        $offerItem = $this->getNewOfferItemObject();
        $offerItem->setParent($parent);
        $offerItem->setPublished(true);
        $offerItem->setCartItemKey($item->getItemKey());
        $offerItem->setKey($item->getProduct()->getId() . '_' . $item->getItemKey());

        $offerItem->setAmount($item->getCount());
        $offerItem->setProduct($item->getProduct());
        if ($item->getProduct()) {
            $offerItem->setProductName($item->getProduct()->getOSName());
            $offerItem->setProductNumber($item->getProduct()->getOSProductNumber());
        }

        $offerItem->setComment($item->getComment());

        $price = 0;
        if ($item->getTotalPrice()) {
            $price = $item->getTotalPrice()->getAmount();
        }

        $price = $this->priceTransformationHook($price);

        $offerItem->setOriginalTotalPrice($price);
        $offerItem->setFinalTotalPrice($price);

        $offerItem->save();

        $subItems = $item->getSubItems();
        if (!empty($subItems)) {
            $offerSubItems = [];

            foreach ($subItems as $subItem) {
                $offerSubItem = $this->createOfferItem($subItem, $offerItem);
                $offerSubItem->save();
                $offerSubItems[] = $offerSubItem;
            }

            $offerItem->setSubItems($offerSubItems);
            $offerItem->save();
        }

        return $offerItem;
    }

    protected function updateOfferItem(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem $cartItem, \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOfferItem $offerItem)
    {
        $offerItem->setAmount($cartItem->getCount());
        $offerItem->setProduct($cartItem->getProduct());
        if ($offerItem->getProduct()) {
            $offerItem->setProductName($cartItem->getProduct()->getOSName());
            $offerItem->setProductNumber($cartItem->getProduct()->getOSProductNumber());
        }

        $offerItem->setComment($cartItem->getComment());

        $price = 0;
        if ($cartItem->getTotalPrice()) {
            $price = $cartItem->getTotalPrice()->getAmount();
        }

        $price = $this->priceTransformationHook($price);

        if ((string)$price != (string)$offerItem->getOriginalTotalPrice()) {
            $offerItem->setOriginalTotalPrice($price);
            $offerItem->setFinalTotalPrice($price);
        }

        //Delete all subitems and add them as new items
        $offerSubItems = $offerItem->getSubItems();
        foreach ($offerSubItems as $i) {
            $i->delete();
        }

        $subItems = $cartItem->getSubItems();
        if (!empty($subItems)) {
            $offerSubItems = [];

            foreach ($subItems as $subItem) {
                $offerSubItem = $this->createOfferItem($subItem, $offerItem);
                $offerSubItem->save();
                $offerSubItems[] = $offerSubItem;
            }

            $offerItem->setSubItems($offerSubItems);
        }

        $offerItem->save();

        return $offerItem;
    }

    /**
     * transforms price before set to the offer tool item.
     * can be used e.g. for adding vat, ...
     *
     * @param $price
     *
     * @return mixed
     */
    protected function priceTransformationHook($price)
    {
        return $price;
    }

    protected function setCurrentCustomer(\Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer $offer)
    {
        $env = Factory::getInstance()->getEnvironment();

        if (@class_exists('Object_Customer')) {
            $customer = \Pimcore\Model\Object\Customer::getById($env->getCurrentUserId());
            $offer->setCustomer($customer);
        }

        return $offer;
    }

    public function updateOfferFromCart(\Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer $offer, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart, array $excludeItems = [], $save = true)
    {
        $excludedItemKeys = $this->getExcludedItemKeys($excludeItems);

        if ($cart->getId() != $offer->getCartId()) {
            throw new \Exception('Cart does not match to the offer given, update is not possible');
        }

        //Update existing offer items
        $offerItems = $offer->getItems();
        $newOfferItems = [];
        foreach ($offerItems as $offerItem) {
            $cartItem = $cart->getItem($offerItem->getCartItemKey());
            if ($cartItem && !$excludedItemKeys[$offerItem->getCartItemKey()]) {
                $newOfferItems[$offerItem->getCartItemKey()] = $this->updateOfferItem($cartItem, $offerItem);
            }
        }

        //Add non existing cart items to offer
        $cartItems = $cart->getItems();
        foreach ($cartItems as $cartItem) {
            if (!$newOfferItems[$cartItem->getItemKey()] && !$excludedItemKeys[$cartItem->getItemKey()]) {
                $offerItem = $this->createOfferItem($cartItem, $offer);
                $newOfferItems[$offerItem->getCartItemKey()] = $offerItem;
            }
        }

        //Delete offer items which are not needed any more
        foreach ($offerItems as $offerItem) {
            if (!$newOfferItems[$offerItem->getCartItemKey()]) {
                $offerItem->delete();
            }
        }

        $offer->setItems($newOfferItems);

        //Update total price
        $offer = $this->updateTotalPriceOfOffer($offer);

        if ($save) {
            $offer->save();
        }

        return $offer;
    }

    public function updateTotalPriceOfOffer(\Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer $offer)
    {
        $totalPrice = 0;

        foreach ($offer->getItems() as $item) {
            $totalPrice += $item->getFinalTotalPrice();
        }

        foreach ($offer->getCustomItems() as $item) {
            $totalPrice += $item->getFinalTotalPrice();
        }

        if ($offer->getDiscountType() == IService::DISCOUNT_TYPE_PERCENT) {
            $discount = $totalPrice * $offer->getDiscount() / 100;
        } else {
            $discount = $offer->getDiscount();
        }

        $offer->setTotalPriceBeforeDiscount($totalPrice);
        $offer->setTotalPrice($totalPrice - $discount);

        return $offer;
    }

    public function getOffersForCart(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart)
    {
        $offerListClass = $this->offerClass . '_List';
        $list = new $offerListClass();
        $list->setCondition('cartId = ?', [$cart->getId()]);

        return $list->load();
    }

    public function createCustomOfferToolItem($product, $offer)
    {
    }
}
