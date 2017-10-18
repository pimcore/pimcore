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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutStep as CheckoutManagerICheckoutStep;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckout;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckoutComplete;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckoutStep;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductActionAdd;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductActionRemove;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductImpression;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductView;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductAction;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductImpression;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Transaction;
use Pimcore\Google\Analytics;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnhancedEcommerce extends Tracker implements
    IProductView,
    IProductImpression,
    IProductActionAdd,
    IProductActionRemove,
    ICheckout,
    ICheckoutStep,
    ICheckoutComplete
{
    /**
     * Dependencies to include before any tracking actions
     *
     * @var array
     */
    protected $dependencies = ['ec'];

    /**
     * @var bool
     */
    protected $dependenciesIncluded = false;

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'template_prefix' => 'PimcoreEcommerceFrameworkBundle:Tracking/analytics/enhanced'
        ]);
    }

    /**
     * Track product view
     *
     * @param IProduct $product
     */
    public function trackProductView(IProduct $product)
    {
        $this->ensureDependencies();

        $item = $this->trackingItemBuilder->buildProductViewItem($product);

        $parameters = [];
        $parameters['productData'] = $this->transformProductAction($item);

        unset($parameters['productData']['price']);
        unset($parameters['productData']['quantity']);

        $result = $this->renderTemplate('product_view', $parameters);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track product view
     *
     * @param IProduct $product
     */
    public function trackProductImpression(IProduct $product)
    {
        $this->ensureDependencies();

        $item = $this->trackingItemBuilder->buildProductImpressionItem($product);

        $parameters = [
            'productData' => $this->transformProductImpression($item)
        ];

        $result = $this->renderTemplate('product_impression', $parameters);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track product action add
     *
     * @param IProduct $product
     * @param int|float $quantity
     */
    public function trackProductActionAdd(IProduct $product, $quantity = 1)
    {
        $this->ensureDependencies();

        $this->trackProductAction($product, 'add', $quantity);
    }

    /**
     * Track product remove from cart
     *
     * @param IProduct $product
     * @param int|float $quantity
     */
    public function trackProductActionRemove(IProduct $product, $quantity = 1)
    {
        $this->ensureDependencies();

        $this->trackProductAction($product, 'remove', $quantity);
    }

    /**
     * @param $product
     * @param $action
     * @param int|float $quantity
     */
    protected function trackProductAction($product, $action, $quantity = 1)
    {
        $item = $this->trackingItemBuilder->buildProductActionItem($product);
        $item->setQuantity($quantity);

        $parameters = [];
        $parameters['productData'] = $this->transformProductAction($item);
        $parameters['action'] = $action;

        $result = $this->renderTemplate('product_action', $parameters);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track start checkout with first step
     *
     * @param ICart $cart
     */
    public function trackCheckout(ICart $cart)
    {
        $this->ensureDependencies();

        $items = $this->trackingItemBuilder->buildCheckoutItemsByCart($cart);

        $parameters = [];
        $parameters['items'] = $items;
        $parameters['calls'] = $this->buildCheckoutCalls($items);
        $parameters['actionData'] = [
            'step' => 1
        ];

        $result = $this->renderTemplate('checkout', $parameters);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param CheckoutManagerICheckoutStep $step
     * @param ICart $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(CheckoutManagerICheckoutStep $step, ICart $cart, $stepNumber = null, $checkoutOption = null)
    {
        $this->ensureDependencies();

        $items = $this->trackingItemBuilder->buildCheckoutItemsByCart($cart);

        $parameters = [];
        $parameters['items'] = $items;
        $parameters['calls'] = [];

        if (!is_null($stepNumber) || !is_null($checkoutOption)) {
            $actionData = ['step' => $stepNumber];

            if (!is_null($checkoutOption)) {
                $actionData['option'] = $checkoutOption;
            }

            $parameters['actionData'] = $actionData;
        }

        $result = $this->renderTemplate('checkout', $parameters);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * Track checkout complete
     *
     * @param AbstractOrder $order
     */
    public function trackCheckoutComplete(AbstractOrder $order)
    {
        $this->ensureDependencies();

        $transaction = $this->trackingItemBuilder->buildCheckoutTransaction($order);
        $items = $this->trackingItemBuilder->buildCheckoutItems($order);

        $parameters = [];
        $parameters['transaction'] = $this->transformTransaction($transaction);
        $parameters['items'] = $items;
        $parameters['calls'] = $this->buildCheckoutCompleteCalls($transaction, $items);

        $result = $this->renderTemplate('checkout_complete', $parameters);

        Analytics::addAdditionalCode($result, 'beforePageview');
    }

    /**
     * @param Transaction $transaction
     * @param ProductAction[] $items
     *
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
     *
     * @param Transaction $transaction
     *
     * @return array
     */
    protected function transformTransaction(Transaction $transaction)
    {
        return [
            'id'         => $transaction->getId(),                           // order ID - required
            'affilation' => $transaction->getAffiliation() ?: '',            // affiliation or store name
            'revenue'    => round($transaction->getTotal(), 2),     // total - required
            'tax'        => round($transaction->getTax(), 2),       // tax
            'coupon'     => $transaction->getCoupon(), // voucher code - optional
            'shipping'   => round($transaction->getShipping(), 2),  // shipping
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
     *
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
     * @param ProductImpression $item
     *
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

    /**
     * Makes sure dependencies are included once before any call
     */
    protected function ensureDependencies()
    {
        if ($this->dependenciesIncluded || empty($this->dependencies)) {
            return;
        }

        $result = $this->renderTemplate('dependencies', [
            'dependencies' => $this->dependencies
        ]);

        Analytics::addAdditionalCode($result, 'beforePageview');

        $this->dependenciesIncluded = true;
    }
}
