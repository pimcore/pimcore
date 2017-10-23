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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\IShipping;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;

/**
 * Takes an object (e.g. a product, an order) and transforms it into a
 * normalized tracking object (e.g. a ProductAction or a Transaction).
 */
class TrackingItemBuilder implements ITrackingItemBuilder
{
    /**
     * Build a product impression object
     *
     * @param IProduct|ElementInterface $product
     *
     * @return ProductImpression
     */
    public function buildProductImpressionItem(IProduct $product)
    {
        $item = new ProductImpression();
        $item
            ->setId($product->getId())
            ->setName($this->normalizeName($product->getOSName()))
            ->setCategories($this->getProductCategories($product));

        // set price if product is ready to check out
        if ($product instanceof ICheckoutable) {
            $item->setPrice($product->getOSPrice()->getAmount()->asString());
        }

        return $item;
    }

    /**
     * Build a product view object
     *
     * @param IProduct|ElementInterface $product
     *
     * @return ProductAction
     */
    public function buildProductViewItem(IProduct $product)
    {
        return $this->buildProductActionItem($product);
    }

    /**
     * Build a product action item
     *
     * @param IProduct $product
     * @param int $quantity
     *
     * @return ProductAction
     */
    public function buildProductActionItem(IProduct $product, $quantity = 1)
    {
        $item = new ProductAction();
        $item
            ->setId($product->getId())
            ->setName($this->normalizeName($product->getOSName()))
            ->setCategories($this->getProductCategories($product))
            ->setQuantity($quantity);

        // set price if product is ready to check out
        if ($product instanceof ICheckoutable) {
            $item->setPrice($product->getOSPrice()->getAmount()->asString());
        }

        return $item;
    }

    /**
     * Build a checkout transaction object
     *
     * @param AbstractOrder $order
     *
     * @return Transaction
     */
    public function buildCheckoutTransaction(AbstractOrder $order)
    {
        $transaction = new Transaction();
        $transaction
            ->setId($order->getOrdernumber())
            ->setTotal($order->getTotalPrice())
            ->setShipping($this->getOrderShipping($order))
            ->setTax($this->getOrderTax($order));

        return $transaction;
    }

    /**
     * Build checkout items
     *
     * @param AbstractOrder $order
     *
     * @return ProductAction[]
     */
    public function buildCheckoutItems(AbstractOrder $order)
    {
        $items = [];

        if (!$order->getItems()) {
            return $items;
        }

        foreach ($order->getItems() as $orderItem) {
            $items[] = $this->buildCheckoutItem($order, $orderItem);
        }

        return $items;
    }

    /**
     * Build checkout items
     *
     * @param ICart $cart
     *
     * @return ProductAction[]
     */
    public function buildCheckoutItemsByCart(ICart $cart)
    {
        $items = [];

        if (!$cart->getItems()) {
            return $items;
        }

        foreach ($cart->getItems() as $cartItem) {
            /** @var IProduct $product */
            $product = $cartItem->getProduct();
            if (!$product) {
                continue;
            }

            $items[] = $this->buildCheckoutItemByCartItem($cartItem);
        }

        return $items;
    }

    /**
     * Build a checkout item object
     *
     * @param AbstractOrder $order
     * @param AbstractOrderItem $orderItem
     *
     * @return ProductAction
     */
    public function buildCheckoutItem(AbstractOrder $order, AbstractOrderItem $orderItem)
    {
        /** @var IProduct $product */
        $product = $orderItem->getProduct();

        $item = new ProductAction();
        $item
            ->setId($orderItem->getProductNumber())
            ->setTransactionId($order->getOrdernumber())
            ->setName($this->normalizeName($orderItem->getProductName()))
            ->setCategories($this->getProductCategories($product))
            ->setPrice($orderItem->getTotalPrice() / $orderItem->getAmount())
            ->setQuantity($orderItem->getAmount());

        return $item;
    }

    /**
     * Build a checkout item object by cart Item
     *
     * @param ICartItem $cartItem
     *
     * @return ProductAction
     */
    public function buildCheckoutItemByCartItem(ICartItem $cartItem)
    {
        /** @var IProduct|AbstractObject $product */
        $product = $cartItem->getProduct();

        $item = new ProductAction();
        $item
            ->setId($product->getId())
            ->setName($this->normalizeName($product->getOSName()))
            ->setCategories($this->getProductCategories($product))
            ->setPrice($cartItem->getTotalPrice() / $cartItem->getCount())
            ->setQuantity($cartItem->getCount());

        return $item;
    }

    /**
     * Get a product's categories
     *
     * @param IProduct $product
     * @param bool $first
     *
     * @return array|string
     */
    protected function getProductCategories(IProduct $product, $first = false)
    {
        $categories = [];
        if ($product && method_exists($product, 'getCategories')) {
            if ($product->getCategories()) {
                foreach ($product->getCategories() as $category) {
                    if ($category && method_exists($category, 'getName')) {
                        $categories[] = $category->getName();
                    }
                }
            }
        }

        if (count($categories) > 0 && $first) {
            return $categories[0];
        }

        return $categories;
    }

    /**
     * Get order shipping
     *
     * @param AbstractOrder $order
     *
     * @return float
     */
    protected function getOrderShipping(AbstractOrder $order)
    {
        $shipping = Decimal::zero();

        // calculate shipping
        $modifications = $order->getPriceModifications();
        if ($modifications) {
            foreach ($modifications as $modification) {
                if ($modification instanceof IShipping) {
                    $shipping = $shipping->add($modification->getCharge());
                }
            }
        }

        return $shipping->asNumeric();
    }

    /**
     * Get order tax
     *
     * @param AbstractOrder $order
     *
     * @return float
     */
    protected function getOrderTax(AbstractOrder $order)
    {
        $tax = Decimal::zero();
        foreach ($order->getTaxInfo() as $taxInfo) {
            $tax = $tax->add(Decimal::create($taxInfo[2]));
        }

        return $tax->asNumeric();
    }

    /**
     * Normalize name for tracking JS
     *
     * @param string $name
     *
     * @return string
     */
    protected function normalizeName($name)
    {
        return str_replace(["\n"], [' '], $name);
    }
}
