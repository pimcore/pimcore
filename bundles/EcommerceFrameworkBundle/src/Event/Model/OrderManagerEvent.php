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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7\OrderManagerInterface;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class OrderManagerEvent extends Event
{
    use ArgumentsAwareTrait;

    protected CartInterface $cart;

    protected ?AbstractOrder $order = null;

    protected OrderManagerInterface $orderManager;

    /**
     * OrderManagerEvent constructor.
     *
     * @param CartInterface $cart
     * @param AbstractOrder|null $order
     * @param OrderManagerInterface $orderManager
     * @param array $arguments
     */
    public function __construct(CartInterface $cart, ?AbstractOrder $order, OrderManagerInterface $orderManager, array $arguments = [])
    {
        $this->cart = $cart;
        $this->order = $order;
        $this->orderManager = $orderManager;
        $this->arguments = $arguments;
    }

    public function getCart(): CartInterface
    {
        return $this->cart;
    }

    public function setCart(CartInterface $cart): void
    {
        $this->cart = $cart;
    }

    public function getOrder(): ?AbstractOrder
    {
        return $this->order;
    }

    public function setOrder(AbstractOrder $order): void
    {
        $this->order = $order;
    }

    public function getOrderManager(): OrderManagerInterface
    {
        return $this->orderManager;
    }

    public function setOrderManager(OrderManagerInterface $orderManager): void
    {
        $this->orderManager = $orderManager;
    }
}
