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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ShippingInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;

/**
 * Takes an object (e.g. a product, an order) and transforms it into a
 * normalized tracking object (e.g. a ProductAction or a Transaction).
 */
class TrackingItemBuilder implements TrackingItemBuilderInterface
{
    /**
     * Build a product impression object
     *
     * @param ProductInterface&Concrete $product
     * @param string $list
     *
     * @return ProductImpression
     */
    public function buildProductImpressionItem(ProductInterface $product, string $list = 'default')
    {
        $item = new ProductImpression();
        $this->initProductAttributes($item, $product);

        $item
            ->setId($product->getId())
            ->setName($this->normalizeName($product->getOSName()))
            ->setCategories($this->getProductCategories($product))
            ->setList($list)
        ;

        // set price if product is ready to check out
        if ($product instanceof CheckoutableInterface) {
            $item->setPrice($product->getOSPrice()->getAmount()->asNumeric());
        }

        return $item;
    }

    /**
     * Build a product view object
     *
     * @param ProductInterface $product
     *
     * @return ProductAction
     */
    public function buildProductViewItem(ProductInterface $product)
    {
        return $this->buildProductActionItem($product);
    }

    /**
     * Init common product action attributes and add additional application-specific product action attributes.
     *
     * @param AbstractProductData $item the tracking item that is going to be serialized later on.
     * @param ProductInterface $product
     */
    protected function initProductAttributes(AbstractProductData $item, ProductInterface $product)
    {
        $item
            ->setId($product->getOSProductNumber())
            ->setName($this->normalizeName($product->getOSName()))
            ->setCategories($this->getProductCategories($product))
            ->setBrand($this->getProductBrand($product))
        ;

        //
        //Add additional data to tracking items of type "product".
        //Example: $item->addAdditionalAttribute("ean", "test-EAN");
        //
    }

    /**
     * Build a product action item
     *
     * @param ProductInterface $product
     * @param int $quantity
     *
     * @return ProductAction
     */
    public function buildProductActionItem(ProductInterface $product, $quantity = 1)
    {
        $item = new ProductAction();
        $item->setQuantity($quantity);

        $this->initProductAttributes($item, $product);

        // set price if product is ready to check out
        if ($product instanceof CheckoutableInterface) {
            $item->setPrice($product->getOSPrice()->getAmount()->asNumeric());
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
            ->setTotal(Decimal::create($order->getTotalPrice())->asNumeric())
            ->setSubTotal(Decimal::create($order->getSubTotalPrice())->asNumeric())
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
     * @param CartInterface $cart
     *
     * @return ProductAction[]
     */
    public function buildCheckoutItemsByCart(CartInterface $cart)
    {
        $items = [];

        if (!$cart->getItems()) {
            return $items;
        }

        foreach ($cart->getItems() as $cartItem) {
            /** @var ProductInterface $product */
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
        /** @var ProductInterface $product */
        $product = $orderItem->getProduct();

        $item = new ProductAction();
        $item
            ->setTransactionId($order->getOrdernumber())
            ->setPrice(Decimal::create($orderItem->getTotalPrice())->div($orderItem->getAmount())->asNumeric())
            ->setQuantity($orderItem->getAmount());

        $this->initProductAttributes($item, $product);

        return $item;
    }

    /**
     * Build a checkout item object by cart Item
     *
     * @param CartItemInterface $cartItem
     *
     * @return ProductAction
     */
    public function buildCheckoutItemByCartItem(CartItemInterface $cartItem)
    {
        /** @var ProductInterface|AbstractObject $product */
        $product = $cartItem->getProduct();

        $item = new ProductAction();
        $item->setPrice($cartItem->getTotalPrice()->getAmount()->div($cartItem->getCount())->asNumeric())
            ->setQuantity($cartItem->getCount());

        $this->initProductAttributes($item, $product);

        return $item;
    }

    /**
     * Get a product's categories
     *
     * @param ProductInterface $product
     * @param bool $first
     *
     * @return array|string
     */
    protected function getProductCategories(ProductInterface $product, $first = false)
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
     * Get a product's brand
     *
     * @param ProductInterface $product
     *
     * @return null|string
     */
    protected function getProductBrand(ProductInterface $product)
    {
        $brandName = null;
        if ($product && method_exists($product, 'getBrand')) {
            if ($brand = $product->getBrand()) {
                if ($brand && method_exists($brand, 'getName')) {
                    $brandName = $brand->getName();
                }
            }
        }

        return $brandName;
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
                if ($modification instanceof ShippingInterface) {
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
