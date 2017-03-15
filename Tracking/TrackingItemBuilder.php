<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking;

use OnlineShop\Framework\CartManager\AbstractCartItem;
use OnlineShop\Framework\CartManager\CartPriceModificator\IShipping;
use OnlineShop\Framework\CartManager\ICart;
use OnlineShop\Framework\Model\AbstractOrder;
use OnlineShop\Framework\Model\AbstractOrderItem;
use OnlineShop\Framework\Model\ICheckoutable;
use OnlineShop\Framework\Model\IProduct;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Object\Concrete;

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
            $item->setPrice($product->getOSPrice()->getAmount());
        }

        return $item;
    }

    /**
     * Build a product view object
     *
     * @param IProduct|ElementInterface $product
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
     * @return ProductAction
     */
    public function buildProductActionItem(IProduct $product, $quantity = 1) {
        $item = new ProductAction();
        $item
            ->setId($product->getId())
            ->setName($this->normalizeName($product->getOSName()))
            ->setCategories($this->getProductCategories($product))
            ->setQuantity($quantity);

        // set price if product is ready to check out
        if ($product instanceof ICheckoutable) {
            $item->setPrice($product->getOSPrice()->getAmount());
        }

        return $item;
    }

    /**
     * Build a checkout transaction object
     *
     * @param AbstractOrder $order
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
     * @return ProductAction[]
     */
    public function buildCheckoutItems(AbstractOrder $order)
    {
        $items = [];
        if ($order->getItems()) {
            foreach ($order->getItems() as $orderItem) {
                $items[] = $this->buildCheckoutItem($order, $orderItem);
            }
        }

        return $items;
    }

    /**
     * Build checkout items
     *
     * @param ICart $cart
     * @return ProductAction[]
     */
    public function buildCheckoutItemsByCart(ICart $cart)
    {
        $items = [];
        if ($cart->getItems()) {
            foreach ($cart->getItems() as $cartItem) {
                if($product = $cartItem->getProduct()) {

                }
                $item = $this->buildProductActionItem($product, $cartItem->getCount());
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Build a checkout item object
     *
     * @param AbstractOrder $order
     * @param AbstractOrderItem $orderItem
     * @return ProductAction
     */
    public function buildCheckoutItem(AbstractOrder $order, AbstractOrderItem $orderItem)
    {
        $item = new ProductAction();
        $item
            ->setId($orderItem->getProductNumber())
            ->setTransactionId($order->getOrdernumber())
            ->setName($this->normalizeName($orderItem->getProductName()))
            ->setCategories($this->getProductCategories($orderItem->getProduct()))
            ->setPrice($orderItem->getTotalPrice() / $orderItem->getAmount())
            ->setQuantity($orderItem->getAmount());

        return $item;
    }

    /**
     * Build a checkout item object by cart Item
     * 
     * @param AbstractCartItem $cartItem
     * @return ProductAction
     */
    public function buildCheckoutItemByCartItem(AbstractCartItem $cartItem)
    {
        $item = new ProductAction();

        $item
            ->setId($cartItem->getProduct()->getId())
            ->setName($this->normalizeName($cartItem->getProduct()->getOSName()))
            ->setCategories($this->getProductCategories($cartItem->getProduct()))
            ->setPrice($cartItem->getTotalPrice() / $cartItem->getAmount())
            ->setQuantity($cartItem->getAmount());

        return $item;
    }

    /**
     * Get a product's categories
     *
     * @param IProduct $product
     * @param bool|false $first
     * @return array
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
     * @return float
     */
    protected function getOrderShipping(AbstractOrder $order)
    {
        $shipping = 0;

        // calculate shipping
        $modifications = $order->getPriceModifications();
        if ($modifications) {
            foreach ($modifications as $modification) {
                if ($modification instanceof IShipping) {
                    $shipping += $modification->getAmount();
                }
            }
        }

        return $shipping;
    }

    /**
     * Get order tax
     *
     * @param AbstractOrder $order
     * @return float
     */
    protected function getOrderTax(AbstractOrder $order)
    {
        return 0;    
    }

    /**
     * Normalize name for tracking JS
     *
     * @param $name
     * @return mixed
     */
    protected function normalizeName($name)
    {
        return str_replace(["\n"], [' '], $name);
    }
}
