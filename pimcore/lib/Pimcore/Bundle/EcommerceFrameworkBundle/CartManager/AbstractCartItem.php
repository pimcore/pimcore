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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;

abstract class AbstractCartItem extends \Pimcore\Model\AbstractModel implements ICartItem
{
    /**
     * @var ICheckoutable
     */
    protected $product;
    protected $productId;
    protected $itemKey;
    protected $count;
    protected $comment;
    protected $parentItemKey = '';

    protected $subItems = null;

    /**
     * @var \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart
     */
    protected $cart;
    protected $cartId;

    /**
     * @var int unix timestamp
     */
    protected $addedDateTimestamp;

    public function __construct()
    {
        $this->setAddedDate(new \DateTime());
    }

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function setProduct(ICheckoutable $product)
    {
        $this->product = $product;
        $this->productId = $product->getId();
    }

    /**
     * @return ICheckoutable
     */
    public function getProduct()
    {
        if ($this->product) {
            return $this->product;
        }
        $this->product = \Pimcore\Model\Object\AbstractObject::getById($this->productId);

        return $this->product;
    }

    /**
     * @param ICart $cart
     */
    public function setCart(ICart $cart)
    {
        $this->cart = $cart;
        $this->cartId = $cart->getId();
    }

    /**
     * @return ICart
     */
    abstract public function getCart();

    /**
     * @return mixed
     */
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * @param $cartId
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
        if ($this->productId) {
            return $this->productId;
        }

        return $this->getProduct()->getId();
    }

    /**
     * @param $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * @param $parentItemKey
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
     * @param $itemKey
     */
    public function setItemKey($itemKey)
    {
        $this->itemKey = $itemKey;
    }

    /**
     * @return mixed
     */
    public function getItemKey()
    {
        return $this->itemKey;
    }

    /**
     * @param  \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem[] $subItems
     *
     * @return void
     */
    public function setSubItems($subItems)
    {
        foreach ($subItems as $item) {
            $item->setParentItemKey($this->getItemKey());
        }
        $this->subItems = $subItems;
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice
     */
    public function getPrice()
    {
        return $this->getPriceInfo()->getPrice();
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo
     */
    public function getPriceInfo()
    {
        if ($this->getProduct() instanceof AbstractSetProduct) {
            $priceInfo = $this->getProduct()->getOSPriceInfo($this->getCount(), $this->getSetEntries());
        } else {
            $priceInfo = $this->getProduct()->getOSPriceInfo($this->getCount());
        }

        if ($priceInfo instanceof \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPriceInfo) {
            $priceInfo->getEnvironment()->setCart($this->getCart());
            $priceInfo->getEnvironment()->setCartItem($this);
        }

        return $priceInfo;
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailability
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
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice
     */
    public function getTotalPrice()
    {
        return $this->getPriceInfo()->getTotalPrice();
    }

    /**
     * @param \DateTime|null $date
     */
    public function setAddedDate(\DateTime $date = null)
    {
        if ($date) {
            $this->addedDateTimestamp = $date->getTimestamp();
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
            $datetime = new \DateTime();
            $datetime->setTimestamp($this->addedDateTimestamp);
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
}
