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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class OrderManagerItemEvent extends Event
{
    use ArgumentsAwareTrait;

    protected CartItemInterface $cartItem;

    /**+
     * @var bool
     */
    protected bool $isGiftItem;

    protected ?AbstractOrderItem $orderItem = null;

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

    public function getCartItem(): CartItemInterface
    {
        return $this->cartItem;
    }

    public function setCartItem(CartItemInterface $cartItem): void
    {
        $this->cartItem = $cartItem;
    }

    public function isGiftItem(): bool
    {
        return $this->isGiftItem;
    }

    public function setIsGiftItem(bool $isGiftItem): void
    {
        $this->isGiftItem = $isGiftItem;
    }

    public function getOrderItem(): ?AbstractOrderItem
    {
        return $this->orderItem;
    }

    public function setOrderItem(AbstractOrderItem $orderItem): void
    {
        $this->orderItem = $orderItem;
    }
}
