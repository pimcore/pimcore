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
    protected bool $isLoading = false;

    protected ?CheckoutableInterface $product = null;

    protected ?int $productId = null;

    protected string $itemKey = '';

    protected int $count = 0;

    protected string $comment = '';

    protected string $parentItemKey = '';

    protected ?array $subItems = null;

    protected ?CartInterface $cart = null;

    protected string|int|null $cartId;

    /**
     * @var int|null unix timestamp
     */
    protected ?int $addedDateTimestamp = null;

    public function __construct()
    {
        $this->setAddedDate(new \DateTime());
    }

    public function setCount(int $count, bool $fireModified = true): void
    {
        if ($count < 0) {
            $count = 0;
        }

        if ($this->count !== $count && $this->getCart() && !$this->isLoading && $fireModified) {
            $this->getCart()->modified();
        }
        $this->count = $count;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setProduct(CheckoutableInterface $product, bool $fireModified = true): void
    {
        if ($this->productId !== $product->getId() && !$this->isLoading && $this->getCart() && $fireModified) {
            $this->getCart()->modified();
        }
        $this->product = $product;
        $this->productId = $product->getId();
    }

    public function getProduct(): CheckoutableInterface
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

    public function setCart(CartInterface $cart): void
    {
        $this->cart = $cart;
        $this->cartId = $cart->getId();
    }

    abstract public function getCart(): ?CartInterface;

    public function getCartId(): int|string|null
    {
        return $this->cartId;
    }

    public function setCartId(int|string|null $cartId): void
    {
        $this->cartId = $cartId;
    }

    public function getProductId(): ?int
    {
        if (!is_null($this->productId)) {
            return $this->productId;
        }

        return $this->getProduct()->getId();
    }

    public function setProductId(int $productId): void
    {
        if ($this->productId !== $productId && !$this->isLoading && $this->getCart()) {
            $this->getCart()->modified();
        }
        $this->productId = $productId;
        $this->product = null;
    }

    public function setParentItemKey(string $parentItemKey): void
    {
        $this->parentItemKey = $parentItemKey;
    }

    public function getParentItemKey(): string
    {
        return $this->parentItemKey;
    }

    public function setItemKey(string $itemKey): void
    {
        $this->itemKey = $itemKey;
    }

    public function getItemKey(): string
    {
        return $this->itemKey;
    }

    /**
     * @param CartItemInterface[] $subItems
     *
     * @return void
     */
    public function setSubItems(array $subItems): void
    {
        $cart = $this->getCart();
        if ($cart && !$this->isLoading) {
            $cart->modified();
        }

        foreach ($subItems as $item) {
            if ($item instanceof AbstractCartItem) {
                $item->setParentItemKey($this->getItemKey());
            }
        }
        $this->subItems = $subItems;
    }

    public function getPrice(): PriceInterface
    {
        return $this->getPriceInfo()->getPrice();
    }

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

    public function getAvailabilityInfo(): AvailabilityInterface
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
    public function getSetEntries(): array
    {
        $products = [];
        if ($this->getSubItems()) {
            foreach ($this->getSubItems() as $item) {
                $products[] = new AbstractSetProductEntry($item->getProduct(), $item->getCount());
            }
        }

        return $products;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getTotalPrice(): PriceInterface
    {
        return $this->getPriceInfo()->getTotalPrice();
    }

    /**
     * @param \DateTime|null $date
     */
    public function setAddedDate(\DateTime $date = null): void
    {
        if ($date) {
            $this->addedDateTimestamp = intval($date->format('Uu'));
        } else {
            $this->addedDateTimestamp = null;
        }
    }

    public function getAddedDate(): \DateTime
    {
        $datetime = null;
        if ($this->addedDateTimestamp) {
            $datetime = \DateTime::createFromFormat('U', (string) intval($this->addedDateTimestamp / 1000000));
        }

        return $datetime;
    }

    public function getAddedDateTimestamp(): int
    {
        return $this->addedDateTimestamp ?? 0;
    }

    public function setAddedDateTimestamp(int $time): void
    {
        $this->addedDateTimestamp = $time;
    }

    /**
     * get item name
     *
     * @return string
     */
    public function getName(): string
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
    public function setIsLoading(bool $isLoading): void
    {
        $this->isLoading = $isLoading;
    }
}
