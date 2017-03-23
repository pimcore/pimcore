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

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ICheckout;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ICheckoutComplete;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ICheckoutStep;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\IProductActionAdd;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\IProductActionRemove;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\IProductImpression;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\IProductView;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ProductAction;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ProductImpression;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\Tracker;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\Transaction;
use Pimcore\Google\Analytics;

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

        $parameterBag['productData'] = $this->transformProductAction($item);

        unset($parameterBag['productData']['price']);
        unset($parameterBag['productData']['quantity']);

        $result = $this->renderer->render($this->getViewScript('product_view'), $parameterBag);
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

        $parameterBag['productData'] = $this->transformProductImpression($item);

        $result = $this->renderer->render($this->getViewScript('product_impression'), $parameterBag);
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

        $parameterBag['productData'] = $this->transformProductAction($item);
        $parameterBag['action'] = $action;

        $result = $this->renderer->render($this->getViewScript('product_action'), $parameterBag);
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

        $parameterBag['items'] = $items;
        $parameterBag['calls'] = $this->buildCheckoutCalls($items);

        $parameterBag['actionData'] = ["step" => 1];

        $result = $this->renderer->render($this->getViewScript('checkout'), $parameterBag);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICheckoutStep $step
     * @param ICart $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICheckoutStep $step, ICart $cart, $stepNumber = null, $checkoutOption = null)
    {

        $items = $this->getTrackingItemBuilder()->buildCheckoutItemsByCart($cart);

        $parameterBag['items'] = $items;
        $parameterBag['calls'] = [];
        if (!is_null($stepNumber) || !is_null($checkoutOption)) {
            $actionData = ["step" => $stepNumber];

            if (!is_null($checkoutOption)) {
                $actionData["option"] = $checkoutOption;
            }

            $parameterBag['actionData'] = $actionData;
        }


        $result = $this->renderer->render($this->getViewScript('checkout'), $parameterBag);

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

        $parameterBag['transaction'] = $this->transformTransaction($transaction);
        $parameterBag['items'] = $items;
        $parameterBag['calls'] = $this->buildCheckoutCompleteCalls($transaction, $items);


        $result = $this->renderer->render($this->getViewScript('checkout_complete'), $parameterBag);
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
