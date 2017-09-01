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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Service;

class DefaultService implements IService
{
    /**
     * @var string
     */
    protected $offerClass;

    /**
     * @var string
     */
    protected $offerItemClass;

    /**
     * @var string
     */
    protected $parentFolderPath;

    /**
     * @var Folder
     */
    protected $parentFolder;

    public function __construct(string $offerClass, string $offerItemClass, string $parentFolderPath)
    {
        if (!class_exists($offerClass)) {
            throw new \InvalidArgumentException(sprintf('Offer class "%s" does not exist.'));
        }

        if (!class_exists($offerItemClass)) {
            throw new \InvalidArgumentException(sprintf('Offer item class "%s" does not exist.'));
        }

        $this->offerClass = $offerClass;
        $this->offerItemClass = $offerItemClass;
        $this->parentFolderPath = strftime($parentFolderPath, time());
    }

    protected function getParentFolder(): Folder
    {
        $folder = Folder::getByPath($this->parentFolderPath);
        if (!$folder) {
            $folder = Service::createFolderByPath($this->parentFolderPath);
        }

        if (!$folder) {
            throw new \RuntimeException(sprintf(
                'Unable to create/load parent folder from path "%s"',
                $this->parentFolderPath
            ));
        }

        return $folder;
    }

    /**
     * @param ICart $cart
     * @param ICartItem[] $excludeItems
     *
     * @return AbstractOffer
     */
    public function createNewOfferFromCart(ICart $cart, array $excludeItems = [])
    {
        $tempOfferNumber = uniqid('offer_');
        $offer = $this->getNewOfferObject($tempOfferNumber);
        $offer->setOfferNumber($tempOfferNumber);
        $offer->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getAmount()->asString());
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
     * @return AbstractOffer
     */
    protected function getNewOfferObject($tempOfferNumber)
    {
        $offer = new $this->offerClass();

        /**
         * @var $offer AbstractOffer
         */
        $offer->setParent($this->getParentFolder());
        $offer->setCreationDate(time());
        $offer->setKey($tempOfferNumber);
        $offer->setPublished(true);
        $offer->setDateCreated(new \DateTime());

        return $offer;
    }

    /**
     * @return AbstractOfferItem
     */
    public function getNewOfferItemObject()
    {
        return new $this->offerItemClass();
    }

    /**
     * @param ICartItem $item
     * @param $parent
     *
     * @return AbstractOfferItem
     */
    protected function createOfferItem(ICartItem $item, $parent)
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

        $price = Decimal::zero();
        if ($item->getTotalPrice()) {
            $price = $item->getTotalPrice()->getAmount();
        }

        $price = $this->priceTransformationHook($price);

        $offerItem->setOriginalTotalPrice($price->asString());
        $offerItem->setFinalTotalPrice($price->asString());

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

    protected function updateOfferItem(ICartItem $cartItem, AbstractOfferItem $offerItem)
    {
        $offerItem->setAmount($cartItem->getCount());
        $offerItem->setProduct($cartItem->getProduct());
        if ($offerItem->getProduct()) {
            $offerItem->setProductName($cartItem->getProduct()->getOSName());
            $offerItem->setProductNumber($cartItem->getProduct()->getOSProductNumber());
        }

        $offerItem->setComment($cartItem->getComment());

        $price = Decimal::zero();
        if ($cartItem->getTotalPrice()) {
            $price = $cartItem->getTotalPrice()->getAmount();
        }

        $price = $this->priceTransformationHook($price);

        $originalTotalPrice = Decimal::create($offerItem->getOriginalTotalPrice());
        if (!$price->equals($originalTotalPrice)) {
            $offerItem->setOriginalTotalPrice($price->asString());
            $offerItem->setFinalTotalPrice($price->asString());
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
     * @param Decimal $price
     *
     * @return Decimal
     */
    protected function priceTransformationHook(Decimal $price): Decimal
    {
        return $price;
    }

    protected function setCurrentCustomer(AbstractOffer $offer)
    {
        $env = Factory::getInstance()->getEnvironment();

        if (@class_exists('Object_Customer')) {
            $customer = \Pimcore\Model\DataObject\Customer::getById($env->getCurrentUserId());
            $offer->setCustomer($customer);
        }

        return $offer;
    }

    public function updateOfferFromCart(AbstractOffer $offer, ICart $cart, array $excludeItems = [], $save = true)
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

    public function updateTotalPriceOfOffer(AbstractOffer $offer)
    {
        $totalPrice = Decimal::zero();

        foreach ($offer->getItems() as $item) {
            $totalPrice = $totalPrice->add(Decimal::create($item->getFinalTotalPrice()));
        }

        foreach ($offer->getCustomItems() as $item) {
            $totalPrice = $totalPrice->add(Decimal::create($item->getFinalTotalPrice()));
        }

        if ($offer->getDiscountType() === IService::DISCOUNT_TYPE_PERCENT) {
            $discount = $totalPrice->toPercentage($offer->getDiscount());
        } else {
            $discount = Decimal::create($offer->getDiscount());
        }

        $offer->setTotalPriceBeforeDiscount($totalPrice->asString());
        $offer->setTotalPrice($totalPrice->sub($discount)->asString());

        return $offer;
    }

    public function getOffersForCart(ICart $cart)
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
