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
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\OrderUpdateNotPossibleException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\File;
use Pimcore\Model\DataObject\Fieldcollection;

class OrderManager extends \Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager implements OrderManagerInterface
{

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
            $order->setCartId($this->createCartId($cart));
        }

        // check if pending payment. if one, do not update order from cart

        $cartIsLockedDueToPayments = $this->cartHasPendingPayments($cart);
        $orderNeedsUpdate = $this->orderNeedsUpdate($cart, $order);

        if($orderNeedsUpdate && $cartIsLockedDueToPayments) {
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
            $modificationItems->add($modificationItem);
        }

        $order->setPriceModifications($modificationItems);
        $order->setCartModificationTimestamp($cart->getModificationDate()->getTimestamp());

        $order = $this->setCurrentCustomerToOrder($order);

        // set order currency
        $currency = $cart->getPriceCalculator()->getGrandTotal()->getCurrency();
        $order->setCurrency($currency->getShortName());

        $order->save();

        // for each cart item and cart sub item create corresponding order items
        $orderItems = $this->applyOrderItems($cart->getItems(), $order);
        $order->setItems($orderItems);

        $this->applyVoucherTokens($order, $cart);

        // for each gift item create corresponding order item
        $orderGiftItems = $this->applyOrderItems($cart->getGiftItems(), $order, true);
        $order->setGiftItems($orderGiftItems);

        $order = $this->applyCustomCheckoutDataToOrder($cart, $order);
        $order->save();

        $this->cleanupZombieOrderItems($order);

        return $order;
    }

    /**
     * @param CartInterface $cart
     * @param AbstractOrder $order
     * @return bool
     * @throws UnsupportedException
     */
    public function orderNeedsUpdate(CartInterface $cart, AbstractOrder $order): bool {
        return $cart->getModificationDate()->getTimestamp() !== $order->getCartModificationTimestamp();
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
     * @param AbstractOrder $sourceOrder
     * @return AbstractOrder
     */
    public function recreateOrder(CartInterface $cart): AbstractOrder
    {
        $sourceOrder = $this->getOrderFromCart($cart);

        if($sourceOrder) {
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

            $order->save();

            $sourceOrder->setSuccessorOrder($order);
            $sourceOrder->save();
        }


        return $this->getOrCreateOrderFromCart($cart);
    }

    /**
     * @param AbstractOrder $sourceOrder
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

        $order->save();

        $sourceOrder->setSuccessorOrder($order);
        $sourceOrder->save();

        $order->setItems($this->cloneItems($sourceOrder->getItems(), $order));
        $order->setGiftItems($this->cloneItems($sourceOrder->getGiftItems(), $order));
        $order->save();

        return $order;
    }

    /**
     * @param array $sourceItems
     * @param AbstractOrder $newOrder
     * @return array
     */
    protected function cloneItems(array $sourceItems, AbstractOrder $newOrder): array {
        $items = [];
        foreach($sourceItems as $sourceItem) {

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
     * @return bool
     */
    public function cartHasPendingPayments(CartInterface $cart): bool
    {
        $order = $this->getOrderFromCart($cart);
        if($order) {
            if($order->getOrderState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING) {
                return true;
            }

            $orderAgent = $this->createOrderAgent($order);
            $paymentInfo = $orderAgent->getCurrentPendingPaymentInfo();

            if($paymentInfo) {
                if($paymentInfo->getPaymentState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING) {
                    return true;
                }
            }
        }

        return false;
    }
}