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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerInterface;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Component\EventDispatcher\Event;

class OrderManagerEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var AbstractOrder
     */
    protected $order;

    /**
     * @var OrderManagerInterface
     */
    protected $orderManager;

    /**
     * OrderManagerEvent constructor.
     *
     * @param CartInterface $cart
     * @param AbstractOrder $order
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

    /**
     * @return CartInterface
     */
    public function getCart(): CartInterface
    {
        return $this->cart;
    }

    /**
     * @param CartInterface $cart
     */
    public function setCart(CartInterface $cart): void
    {
        $this->cart = $cart;
    }

    /**
     * @return AbstractOrder|null
     */
    public function getOrder(): ?AbstractOrder
    {
        return $this->order;
    }

    /**
     * @param AbstractOrder $order
     */
    public function setOrder(AbstractOrder $order): void
    {
        $this->order = $order;
    }

    /**
     * @return OrderManagerInterface
     */
    public function getOrderManager(): OrderManagerInterface
    {
        return $this->orderManager;
    }

    /**
     * @param OrderManagerInterface $orderManager
     */
    public function setOrderManager(OrderManagerInterface $orderManager): void
    {
        $this->orderManager = $orderManager;
    }
}
