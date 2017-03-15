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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\Tracker\Analytics;

use OnlineShop\Framework\CartManager\ICart;
use OnlineShop\Framework\Tracking\IProductActionRemove;
use OnlineShop\Framework\Tracking\Tracker;
use OnlineShop\Framework\Model\AbstractOrder;
use OnlineShop\Framework\Model\IProduct;
use OnlineShop\Framework\Tracking\ICheckout;
use OnlineShop\Framework\Tracking\ICheckoutAction;
use OnlineShop\Framework\Tracking\ICheckoutComplete;
use OnlineShop\Framework\Tracking\ICheckoutStep;
use OnlineShop\Framework\Tracking\IProductActionAdd;
use OnlineShop\Framework\Tracking\IProductImpression;
use OnlineShop\Framework\Tracking\IProductView;
use OnlineShop\Framework\Tracking\ProductAction;
use OnlineShop\Framework\Tracking\ProductImpression;
use OnlineShop\Framework\Tracking\Transaction;
use Pimcore\Google\Analytics;
use Pimcore\Model\Object\Concrete;

class EnhancedEcommerce extends Tracker implements IProductView, IProductImpression, IProductActionAdd, IProductActionRemove,
    ICheckout, ICheckoutStep, ICheckoutComplete
{
    /**
     * @return string
     */
    protected function getViewScriptPrefix()
    {
        return 'analytics/enhanced';
    }

    /**
     * Array of google dependencies to include before any tracking actions.
     * @var array
     */
    protected $dependencies = ['ec'];
    
    /**
     * Track product view
     *
     * @param IProduct $product
     */
    public function trackProductView(IProduct $product)
    {
        $item = $this->getTrackingItemBuilder()->buildProductViewItem($product);

        $view = $this->buildView();
        $view->productData = $this->transformProductAction($item);

        unset($view->productData['price']);
        unset($view->productData['quantity']);

        $result = $view->render($this->getViewScript('product_view'));
        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track product view
     *
     * @param IProduct $product
     */
    public function trackProductImpression(IProduct $product)
    {
        $item = $this->getTrackingItemBuilder()->buildProductImpressionItem($product);

        $view = $this->buildView();
        $view->productData = $this->transformProductImpression($item);

        $result = $view->render($this->getViewScript('product_impression'));
        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param IProduct $product
     * @param int $quantity
     */
    public function trackProductActionAdd(IProduct $product, $quantity = 1)
    {
        $this->trackProductAction($product, 'add', $quantity);
    }

    public function trackProductActionRemove(IProduct $product, $quantity = 1)
    {
        $this->trackProductAction($product, 'remove', $quantity);
    }

    /**
     * @param $product
     * @param $action
     * @param int $quantity
     */
    protected function trackProductAction($product, $action, $quantity = 1)
    {
        $item = $this->getTrackingItemBuilder()->buildProductActionItem($product);
        $item->setQuantity($quantity);

        $view = $this->buildView();
        $view->productData = $this->transformProductAction($item);


        $view->action = $action;

        $result = $view->render($this->getViewScript('product_action'));
        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track start checkout with first step
     *
     * @param ICart $cart
     */
    public function trackCheckout(ICart $cart)
    {
        $items = $this->getTrackingItemBuilder()->buildCheckoutItemsByCart($cart);

        $view = $this->buildView();
        $view->items = $items;
        $view->calls = $this->buildCheckoutCalls($items);

        $view->actionData = ["step" => 1];

        $result = $view->render($this->getViewScript('checkout'));

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param \OnlineShop\Framework\CheckoutManager\ICheckoutStep $step
     * @param ICart $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(\OnlineShop\Framework\CheckoutManager\ICheckoutStep $step, ICart $cart, $stepNumber = null, $checkoutOption = null)
    {

        $items = $this->getTrackingItemBuilder()->buildCheckoutItemsByCart($cart);

        $view = $this->buildView();
        $view->items = $items;
        $view->calls = [];
        if (!is_null($stepNumber) || !is_null($checkoutOption)) {
            $actionData = ["step" => $stepNumber];

            if (!is_null($checkoutOption)) {
                $actionData["option"] = $checkoutOption;
            }

            $view->actionData = $actionData;
        }


        $result = $view->render($this->getViewScript('checkout'));

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track checkout complete
     *
     * @param AbstractOrder $order
     */
    public function trackCheckoutComplete(AbstractOrder $order)
    {
        $transaction = $this->getTrackingItemBuilder()->buildCheckoutTransaction($order);
        $items = $this->getTrackingItemBuilder()->buildCheckoutItems($order);

        $view = $this->buildView();
        $view->transaction = $this->transformTransaction($transaction);
        $view->items = $items;
        $view->calls = $this->buildCheckoutCompleteCalls($transaction, $items);


        $result = $view->render($this->getViewScript('checkout_complete'));
        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param Transaction $transaction
     * @param ProductAction[] $items
     * @return mixed
     */
    protected function buildCheckoutCompleteCalls(Transaction $transaction, array $items)
    {

        $calls = [];
        foreach ($items as $item) {
            $calls[] = $this->transformProductAction($item);
        }

        return $calls;
    }

    /**
     * Transform transaction into classic analytics data array
     *
     * @note city, state, country were dropped as they were optional and never used
     * @param Transaction $transaction
     * @return array
     */
    protected function transformTransaction(Transaction $transaction)
    {
        return [
            'id' => $transaction->getId(),                  // order ID - required
            'affilation' => $transaction->getAffiliation() ?: '',   // affiliation or store name
            'revenue' => round($transaction->getTotal(), 2),     // total - required
            'tax' => round($transaction->getTax(), 2),       // tax
            'shipping' => round($transaction->getShipping(), 2),   // shipping
        ];
    }


    protected function buildCheckoutCalls(array $items)
    {
        $calls = [];

        foreach ($items as $item) {
            $calls[] = $this->transformProductAction($item);
        }

        return $calls;
    }

    /**
     * Transform product action into enhanced data object
     *
     * @param ProductAction $item
     * @return array
     */
    protected function transformProductAction(ProductAction $item)
    {
        return $this->filterNullValues([
            'id' => $item->getId(),
            'name' => $item->getName(),
            'category' => $item->getCategory(),
            'brand' => $item->getBrand(),
            'variant' => $item->getVariant(),
            'price' => round($item->getPrice(), 2),
            'quantity' => $item->getQuantity() ?: 1,
            'position' => $item->getPosition(),
            'coupon' => $item->getCoupon()
        ]);
    }

    /**
     * Transform product action into enhanced data object
     *
     * @param ProductAction $item
     * @return array
     */
    protected function transformProductImpression(ProductImpression $item)
    {
        return $this->filterNullValues([
            'id' => $item->getId(),
            'name' => $item->getName(),
            'category' => $item->getCategory(),
            'brand' => $item->getBrand(),
            'variant' => $item->getVariant(),
            'price' => round($item->getPrice(), 2),
            'list' => $item->getList(),
            'position' => $item->getPosition()
        ]);
    }
}
