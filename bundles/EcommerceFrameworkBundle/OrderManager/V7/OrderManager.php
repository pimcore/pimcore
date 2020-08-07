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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\OrderUpdateNotPossibleException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\VoucherServiceInterface;
use Pimcore\Event\Ecommerce\OrderManagerEvents;
use Pimcore\Event\Model\Ecommerce\OrderManagerEvent;
use Pimcore\Event\Model\Ecommerce\OrderManagerItemEvent;
use Pimcore\File;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderManager extends \Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager implements OrderManagerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        EnvironmentInterface $environment,
        OrderAgentFactoryInterface $orderAgentFactory,
        VoucherServiceInterface $voucherService,
        EventDispatcherInterface $eventDispatcher,
        array $options = []
    ) {
        $this->eventDispatcher = $eventDispatcher;

        $this->environment = $environment;
        $this->orderAgentFactory = $orderAgentFactory;
        $this->voucherService = $voucherService;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));
    }

    /**
     * @param CartInterface $cart
     *
     * @return AbstractOrder
     *
     * @throws \Exception
     * @throws UnsupportedException
     *
     */
    public function getOrCreateOrderFromCart(CartInterface $cart)
    {
        $order = $this->getOrderFromCart($cart);

        $event = new OrderManagerEvent($cart, $order, $this);
        $this->eventDispatcher->dispatch(OrderManagerEvents::PRE_GET_OR_CREATE_ORDER_FROM_CART, $event);
        $order = $event->getOrder();

        // no order found, create new one
        if (empty($order)) {
            $tempOrdernumber = $this->createOrderNumber();

            $order = $this->getNewOrderObject();

            $order->setParent($this->getOrderParentFolder());
            $order->setCreationDate(time());
            $order->setKey(File::getValidFilename($tempOrdernumber));
            $order->setPublished(true);

            $order->setOrdernumber($tempOrdernumber);
            $order->setOrderdate(new \DateTime());

            $cartId = $this->createCartId($cart);
            if (strlen($cartId) > 190) {
                throw new \Exception('CartId cannot be longer than 190 characters');
            }

            $order->setCartId($cartId);
        }

        // check if pending payment. if one, do not update order from cart

        $cartIsLockedDueToPayments = $this->cartHasPendingPayments($cart);
        $orderNeedsUpdate = $this->orderNeedsUpdate($cart, $order);

        $event = new OrderManagerEvent($cart, $order, $this, [
            'cartIsLockedDueToPayments' => $cartIsLockedDueToPayments,
            'orderNeedsUpdate' => $orderNeedsUpdate,
        ]);
        $this->eventDispatcher->dispatch(OrderManagerEvents::PRE_UPDATE_ORDER, $event);

        $cartIsLockedDueToPayments = $event->getArgument('cartIsLockedDueToPayments');
        $orderNeedsUpdate = $event->getArgument('orderNeedsUpdate');

        if ($orderNeedsUpdate && $cartIsLockedDueToPayments) {
            throw new OrderUpdateNotPossibleException('Order cannot be updated from cart due to pending payments. Cancel payment or recreate order.');
        }

        if (!$orderNeedsUpdate) {
            return $order;
        }

        // update order from cart
        $order->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getGrossAmount()->asString());
        $order->setTotalNetPrice($cart->getPriceCalculator()->getGrandTotal()->getNetAmount()->asString());
        $order->setSubTotalPrice($cart->getPriceCalculator()->getSubTotal()->getAmount()->asString());
        $order->setSubTotalNetPrice($cart->getPriceCalculator()->getSubTotal()->getNetAmount()->asString());
        $order->setTaxInfo($this->buildTaxArray($cart->getPriceCalculator()->getGrandTotal()->getTaxEntries()));

        $modificationItems = new Fieldcollection();
        foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
            $modificationItem = new Fieldcollection\Data\OrderPriceModifications();
            $modificationItem->setName($modification->getDescription() ? $modification->getDescription() : $name);
            $modificationItem->setAmount($modification->getGrossAmount()->asString());
            $modificationItem->setNetAmount($modification->getNetAmount()->asString());

            if ($modification instanceof ModificatedPrice && $rule = $modification->getRule()) {
                $modificationItem->setPricingRuleId($rule->getId());
            } else {
                $modificationItem->setPricingRuleId(null);
            }

            $modificationItems->add($modificationItem);
        }

        $order->setPriceModifications($modificationItems);
        $order->setCartHash($this->calculateCartHash($cart));

        $order = $this->setCurrentCustomerToOrder($order);

        // set order currency
        $currency = $cart->getPriceCalculator()->getGrandTotal()->getCurrency();
        $order->setCurrency($currency->getShortName());

        $order->save(['versionNote' => 'OrderManager::getOrCreateOrderFromCart - save order to add items.']);

        // for each cart item and cart sub item create corresponding order items
        $orderItems = $this->applyOrderItems($cart->getItems(), $order);
        $order->setItems($orderItems);

        $this->applyVoucherTokens($order, $cart);

        // for each gift item create corresponding order item
        $orderGiftItems = $this->applyOrderItems($cart->getGiftItems(), $order, true);
        $order->setGiftItems($orderGiftItems);

        $order = $this->applyCustomCheckoutDataToOrder($cart, $order);
        $order->save(['versionNote' => 'OrderManager::getOrCreateOrderFromCart - final save.']);

        $this->cleanupZombieOrderItems($order);

        $this->eventDispatcher->dispatch(OrderManagerEvents::POST_UPDATE_ORDER, new OrderManagerEvent($cart, $order, $this));

        return $order;
    }

    /**
     * @param CartInterface $cart
     * @param AbstractOrder $order
     *
     * @return bool
     *
     * @throws UnsupportedException
     */
    public function orderNeedsUpdate(CartInterface $cart, AbstractOrder $order): bool
    {
        return $this->calculateCartHash($cart) !== $order->getCartHash();
    }

    /**
     * @param CartInterface $cart
     *
     * @return int
     */
    protected function calculateCartHash(CartInterface $cart): int
    {
        $hashString = '';

        $hashString .= $cart->getPriceCalculator()->getGrandTotal()->getAmount()->asString();
        $hashString .= $cart->getItemCount();
        $hashString .= $cart->getItemAmount();
        $hashString .= count($cart->getGiftItems());
        $hashString .= implode($cart->getVoucherTokenCodes());

        return crc32($hashString);
    }

    /**
     * @param CartInterface $cart
     *
     * @return null|AbstractOrder
     *
     * @throws \Exception
     */
    public function getOrderFromCart(CartInterface $cart)
    {
        $cartId = $this->createCartId($cart);

        $orderList = $this->buildOrderList();
        $orderList->setCondition('cartId = ? AND IFNULL(successorOrder__id , "") = ""', [$cartId]);

        /** @var AbstractOrder[] $orders */
        $orders = $orderList->load();
        if (count($orders) > 1) {
            throw new \Exception("No unique order found for $cartId.");
        }

        if (count($orders) === 1) {
            return $orders[0];
        }

        return null;
    }

    /**
     * @param CartInterface $cart
     *
     * @return AbstractOrder
     */
    public function recreateOrder(CartInterface $cart): AbstractOrder
    {
        $sourceOrder = $this->getOrderFromCart($cart);

        if ($sourceOrder) {
            //create new order object
            $tempOrdernumber = $this->createOrderNumber();
            $order = $this->getNewOrderObject();

            $order->setParent($sourceOrder->getParent());
            $order->setCreationDate(time());
            $order->setKey(File::getValidFilename($tempOrdernumber));
            $order->setPublished(true);

            $order->setOrdernumber($tempOrdernumber);
            $order->setOrderdate(new \DateTime());
            $order->setCartId($sourceOrder->getCartId());

            $order->save(['versionNote' => 'OrderManager::recreateOrder.']);

            $sourceOrder->setSuccessorOrder($order);
            $sourceOrder->save(['versionNote' => 'OrderManager::recreateOrder - save successor order.']);
        }

        return $this->getOrCreateOrderFromCart($cart);
    }

    /**
     * @param AbstractOrder $sourceOrder
     *
     * @return AbstractOrder
     */
    public function recreateOrderBasedOnSourceOrder(AbstractOrder $sourceOrder): AbstractOrder
    {
        $tempOrdernumber = $this->createOrderNumber();
        $order = clone $sourceOrder;

        $order->setId(null);
        $order->setParent($sourceOrder->getParent());
        $order->setCreationDate(time());
        $order->setKey(File::getValidFilename($tempOrdernumber));
        $order->setPublished(true);

        $order->setOrdernumber($tempOrdernumber);
        $order->setOrderdate(new \DateTime());
        $order->setCartId($sourceOrder->getCartId());

        $order->save(['versionNote' => 'OrderManager::recreateOrderBasedOnSourceOrder - initial save.']);

        $sourceOrder->setSuccessorOrder($order);
        $sourceOrder->save(['versionNote' => 'OrderManager::recreateOrderBasedOnSourceOrder - save successor order.']);

        $order->setItems($this->cloneItems($sourceOrder->getItems(), $order));
        $order->setGiftItems($this->cloneItems($sourceOrder->getGiftItems(), $order));
        $order->save(['versionNote' => 'OrderManager::recreateOrderBasedOnSourceOrder - final save.']);

        return $order;
    }

    /**
     * @param array $sourceItems
     * @param AbstractOrder $newOrder
     *
     * @return array
     */
    protected function cloneItems(array $sourceItems, AbstractOrder $newOrder): array
    {
        $items = [];
        foreach ($sourceItems as $sourceItem) {
            $newItem = clone $sourceItem;
            $newItem->setId(null);

            $newItem->setParent($newOrder);
            $newItem->setSubItems($this->cloneItems($sourceItem->getSubItems(), $newOrder));
            $newItem->save();

            $items[] = $newItem;
        }

        return $items;
    }

    /**
     * @param CartInterface $cart
     *
     * @return bool
     */
    public function cartHasPendingPayments(CartInterface $cart): bool
    {
        $order = $this->getOrderFromCart($cart);
        if ($order) {
            if ($order->getOrderState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING) {
                return true;
            }

            $orderAgent = $this->createOrderAgent($order);
            $paymentInfo = $orderAgent->getCurrentPendingPaymentInfo();

            if ($paymentInfo) {
                if ($paymentInfo->getPaymentState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param CartItemInterface $item
     * @param AbstractObject $parent
     * @param bool $isGiftItem
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem
     *
     * @throws \Exception
     */
    protected function createOrderItem(CartItemInterface $item, $parent, $isGiftItem = false)
    {
        $orderItem = parent::createOrderItem($item, $parent, $isGiftItem);

        $event = new OrderManagerItemEvent($item, $isGiftItem, $orderItem);
        $this->eventDispatcher->dispatch(OrderManagerEvents::POST_CREATE_ORDER_ITEM, $event);

        return $event->getOrderItem();
    }

    protected function buildOrderItemKey(CartItemInterface $item, bool $isGiftItem = false)
    {
        $itemKey = parent::buildOrderItemKey($item, $isGiftItem);

        $event = new OrderManagerItemEvent($item, $isGiftItem, null, ['itemKey' => $itemKey]);
        $this->eventDispatcher->dispatch(OrderManagerEvents::BUILD_ORDER_ITEM_KEY, $event);

        return $event->getArgument('itemKey');
    }
}
