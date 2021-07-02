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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\MockProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Model\DataObject;

abstract class AbstractCartItem extends \Pimcore\Model\AbstractModel implements CartItemInterface
{
    /**
     * flag needed for preventing call modified on cart when loading cart from storage
     *
     * @var bool
     */
    protected $isLoading = false;

    /**
     * @var CheckoutableInterface|null
     */
    protected $product;

    /**
     * @var int|null
     */
    protected $productId;

    /**
     * @var string
     */
    protected $itemKey;

    protected $count;

    protected $comment;

    /**
     * @var string
     */
    protected $parentItemKey = '';

    protected $subItems = null;

    /**
     * @var CartInterface|null
     */
    protected $cart;

    protected $cartId;

    /**
     * @var int|null unix timestamp
     */
    protected $addedDateTimestamp;

    public function __construct()
    {
        $this->setAddedDate(new \DateTime());
    }

    public function setCount($count, bool $fireModified = true)
    {
        if ($this->count != $count && $this->getCart() && !$this->isLoading && $fireModified) {
            $this->getCart()->modified();
        }
        $this->count = $count;
    }

    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param CheckoutableInterface $product
     * @param bool $fireModified
     */
    public function setProduct(CheckoutableInterface $product, bool $fireModified = true)
    {
        if ((empty($product) || $this->productId != $product->getId()) && $this->getCart() && !$this->isLoading && $fireModified) {
            $this->getCart()->modified();
        }
        $this->product = $product;
        $this->productId = $product->getId();
    }

    /**
     * @return CheckoutableInterface
     */
    public function getProduct()
    {
        if ($this->product) {
            return $this->product;
        }

        $product = DataObject::getById($this->productId);

        if ($product instanceof CheckoutableInterface) {
            $this->product = $product;
        } else {
            // actual product is not available or not checkoutable (e.g. deleted in Admin)
            $product = new MockProduct();
            $product->setId($this->productId);
            $this->product = $product;
        }

        return $this->product;
    }

    /**
     * @param CartInterface $cart
     */
    public function setCart(CartInterface $cart)
    {
        $this->cart = $cart;
        $this->cartId = $cart->getId();
    }

    /**
     * @return CartInterface|null
     */
    abstract public function getCart();

    /**
     * @return int
     */
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * @param int $cartId
     */
    public function setCartId($cartId)
    {
        $this->cartId = $cartId;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        if (!is_null($this->productId)) {
            return $this->productId;
        }

        return $this->getProduct()->getId();
    }

    /**
     * @param int $productId
     */
    public function setProductId($productId)
    {
        if ($this->productId != $productId && $this->getCart() && !$this->isLoading) {
            $this->getCart()->modified();
        }
        $this->productId = $productId;
        $this->product = null;
    }

    /**
     * @param string $parentItemKey
     */
    public function setParentItemKey($parentItemKey)
    {
        $this->parentItemKey = $parentItemKey;
    }

    /**
     * @return string
     */
    public function getParentItemKey()
    {
        return $this->parentItemKey;
    }

    /**
     * @param string $itemKey
     */
    public function setItemKey($itemKey)
    {
        $this->itemKey = $itemKey;
    }

    /**
     * @return string
     */
    public function getItemKey()
    {
        return $this->itemKey;
    }

    /**
     * @param  CartItemInterface[] $subItems
     *
     * @return void
     */
    public function setSubItems($subItems)
    {
        if ($this->getCart() && !$this->isLoading) {
            $this->getCart()->modified();
        }

        foreach ($subItems as $item) {
            if ($item instanceof AbstractCartItem) {
                $item->setParentItemKey($this->getItemKey());
            }
        }
        $this->subItems = $subItems;
    }

    /**
     * @return PriceInterface
     */
    public function getPrice(): PriceInterface
    {
        return $this->getPriceInfo()->getPrice();
    }

    /**
     * @return PriceInfoInterface
     */
    public function getPriceInfo(): PriceInfoInterface
    {
        if ($this->getProduct() instanceof AbstractSetProduct) {
            $priceInfo = $this->getProduct()->getOSPriceInfo($this->getCount(), $this->getSetEntries());
        } else {
            $priceInfo = $this->getProduct()->getOSPriceInfo($this->getCount());
        }

        if ($priceInfo instanceof \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfoInterface) {
            $priceInfo->getEnvironment()->setCart($this->getCart());
            $priceInfo->getEnvironment()->setCartItem($this);
        }

        return $priceInfo;
    }

    /**
     * @return AvailabilityInterface
     */
    public function getAvailabilityInfo()
    {
        if ($this->getProduct() instanceof AbstractSetProduct) {
            return $this->getProduct()->getOSAvailabilityInfo($this->getCount(), $this->getSetEntries());
        } else {
            return $this->getProduct()->getOSAvailabilityInfo($this->getCount());
        }
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry[]
     */
    public function getSetEntries()
    {
        $products = [];
        if ($this->getSubItems()) {
            foreach ($this->getSubItems() as $item) {
                $products[] = new AbstractSetProductEntry($item->getProduct(), $item->getCount());
            }
        }

        return $products;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return PriceInterface
     */
    public function getTotalPrice(): PriceInterface
    {
        return $this->getPriceInfo()->getTotalPrice();
    }

    /**
     * @param \DateTime|null $date
     */
    public function setAddedDate(\DateTime $date = null)
    {
        if ($date) {
            $this->addedDateTimestamp = intval($date->format('Uu'));
        } else {
            $this->addedDateTimestamp = null;
        }
    }

    /**
     * @return \DateTime|null
     */
    public function getAddedDate()
    {
        $datetime = null;
        if ($this->addedDateTimestamp) {
            $datetime = \DateTime::createFromFormat('U', intval($this->addedDateTimestamp / 1000000));
        }

        return $datetime;
    }

    /**
     * @return int
     */
    public function getAddedDateTimestamp()
    {
        return $this->addedDateTimestamp;
    }

    /**
     * @param int $time
     */
    public function setAddedDateTimestamp($time)
    {
        $this->addedDateTimestamp = $time;
    }

    /**
     * get item name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getProduct()->getOSName();
    }

    /**
     * Flag needed for preventing call modified on cart when loading cart from storage
     * only for internal usage
     *
     * @param bool $isLoading
     *
     * @internal
     */
    public function setIsLoading(bool $isLoading)
    {
        $this->isLoading = $isLoading;
    }
}
