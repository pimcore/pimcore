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

namespace Pimcore\Event\Model\Ecommerce;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Component\EventDispatcher\Event;

class OrderManagerItemEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var CartItemInterface
     */
    protected $cartItem;

    /**+
     * @var bool
     */
    protected $isGiftItem;

    /**
     * @var AbstractOrderItem
     */
    protected $orderItem;

    /**
     * OrderManagerItemEvent constructor.
     *
     * @param CartItemInterface $cartItem
     * @param bool $isGiftItem
     * @param AbstractOrderItem|null $orderItem
     * @param array $arguments
     */
    public function __construct(CartItemInterface $cartItem, bool $isGiftItem, ?AbstractOrderItem $orderItem, array $arguments = [])
    {
        $this->cartItem = $cartItem;
        $this->isGiftItem = $isGiftItem;
        $this->orderItem = $orderItem;
        $this->arguments = $arguments;
    }

    /**
     * @return CartItemInterface
     */
    public function getCartItem(): CartItemInterface
    {
        return $this->cartItem;
    }

    /**
     * @param CartItemInterface $cartItem
     */
    public function setCartItem(CartItemInterface $cartItem): void
    {
        $this->cartItem = $cartItem;
    }

    /**
     * @return bool
     */
    public function isGiftItem(): bool
    {
        return $this->isGiftItem;
    }

    /**
     * @param bool $isGiftItem
     */
    public function setIsGiftItem(bool $isGiftItem): void
    {
        $this->isGiftItem = $isGiftItem;
    }

    /**
     * @return AbstractOrderItem|null
     */
    public function getOrderItem(): ?AbstractOrderItem
    {
        return $this->orderItem;
    }

    /**
     * @param AbstractOrderItem $orderItem
     */
    public function setOrderItem(AbstractOrderItem $orderItem): void
    {
        $this->orderItem = $orderItem;
    }
}
