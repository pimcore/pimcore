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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutStepInterface as CheckoutManagerCheckoutStepInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CartProductActionAddInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CartProductActionRemoveInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CheckoutCompleteInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CheckoutInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CheckoutStepInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductAction;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductImpression;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductImpressionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductViewInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackEventInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingCodeAwareInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Transaction;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Bundle\GoogleMarketingBundle\Tracker\Tracker as GoogleTracker;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnhancedEcommerce extends AbstractAnalyticsTracker implements
    ProductViewInterface,
    ProductImpressionInterface,
    CartProductActionAddInterface,
    CartProductActionRemoveInterface,
    CheckoutInterface,
    CheckoutStepInterface,
    CheckoutCompleteInterface,
    TrackEventInterface,
    TrackingCodeAwareInterface
{
    /**
     * Dependencies to include before any tracking actions
     *
     * @var array
     */
    protected array $dependencies = ['ec'];

    protected bool $dependenciesIncluded = false;

    /**
     * @var string[]
     */
    protected array $trackedCodes = [];

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'template_prefix' => '@PimcoreEcommerceFramework/Tracking/analytics/enhanced',
        ]);
    }

    /**
     * Track product view
     *
     * @param ProductInterface $product
     */
    public function trackProductView(ProductInterface $product): void
    {
        $this->ensureDependencies();

        $item = $this->trackingItemBuilder->buildProductViewItem($product);

        $parameters = [];
        $parameters['productData'] = $this->transformProductAction($item);

        unset($parameters['productData']['price']);
        unset($parameters['productData']['quantity']);

        $result = $this->renderTemplate('product_view', $parameters);
        $this->trackCode($result);
    }

    /**
     * Track product view
     *
     * @param ProductInterface $product
     * @param string $list
     */
    public function trackProductImpression(ProductInterface $product, string $list = 'default'): void
    {
        $this->ensureDependencies();

        $item = $this->trackingItemBuilder->buildProductImpressionItem($product, $list);

        $parameters = [
            'productData' => $this->transformProductImpression($item),
        ];

        $result = $this->renderTemplate('product_impression', $parameters);
        $this->trackCode($result);
    }

    /**
     * {@inheritdoc}
     */
    public function trackCartProductActionAdd(CartInterface $cart, ProductInterface $product, float|int $quantity = 1): void
    {
        $this->trackProductActionAdd($product, $quantity);
    }

    /**
     * Track product action add
     *
     * @param ProductInterface $product
     * @param float|int $quantity
     */
    public function trackProductActionAdd(ProductInterface $product, float|int $quantity = 1): void
    {
        $this->ensureDependencies();
        $this->trackProductAction($product, 'add', $quantity);
    }

    /**
     * {@inheritdoc}
     */
    public function trackCartProductActionRemove(CartInterface $cart, ProductInterface $product, float|int $quantity = 1): void
    {
        $this->trackProductActionRemove($product, $quantity);
    }

    /**
     * Track product remove from cart
     *
     * @param ProductInterface $product
     * @param float|int $quantity
     */
    public function trackProductActionRemove(ProductInterface $product, float|int $quantity = 1): void
    {
        $this->ensureDependencies();
        $this->trackProductAction($product, 'remove', $quantity);
    }

    protected function trackProductAction(ProductInterface $product, string $action, float|int $quantity = 1): void
    {
        $item = $this->trackingItemBuilder->buildProductActionItem($product);
        $item->setQuantity($quantity);

        $parameters = [];
        $parameters['productData'] = $this->transformProductAction($item);
        $parameters['action'] = $action;

        $result = $this->renderTemplate('product_action', $parameters);
        $this->trackCode($result);
    }

    /**
     * Track start checkout with first step
     *
     * @param CartInterface $cart
     */
    public function trackCheckout(CartInterface $cart): void
    {
        $this->ensureDependencies();

        $items = $this->trackingItemBuilder->buildCheckoutItemsByCart($cart);

        $parameters = [];
        $parameters['items'] = $items;
        $parameters['calls'] = $this->buildCheckoutCalls($items);
        $parameters['actionData'] = [
            'step' => 1,
        ];

        $result = $this->renderTemplate('checkout', $parameters);
        $this->trackCode($result);
    }

    /**
     * @param CheckoutManagerCheckoutStepInterface $step
     * @param CartInterface $cart
     * @param string|null $stepNumber
     * @param string|null $checkoutOption
     */
    public function trackCheckoutStep(CheckoutManagerCheckoutStepInterface $step, CartInterface $cart, string $stepNumber = null, string $checkoutOption = null): void
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
        $this->trackCode($result);
    }

    /**
     * Track checkout complete
     *
     * @param AbstractOrder $order
     */
    public function trackCheckoutComplete(AbstractOrder $order): void
    {
        $this->ensureDependencies();

        $transaction = $this->trackingItemBuilder->buildCheckoutTransaction($order);
        $items = $this->trackingItemBuilder->buildCheckoutItems($order);

        $parameters = [];
        $parameters['transaction'] = $this->transformTransaction($transaction);
        $parameters['items'] = $items;
        $parameters['calls'] = $this->buildCheckoutCompleteCalls($transaction, $items);

        $result = $this->renderTemplate('checkout_complete', $parameters);
        $this->trackCode($result);
    }

    public function trackEvent(
        string $eventCategory,
        string $eventAction,
        string $eventLabel = null,
        int $eventValue = null
    ): void {
        $parameters = [
            'eventCategory' => $eventCategory,
            'eventAction' => $eventAction,
            'eventLabel' => $eventLabel,
            'eventValue' => $eventValue,
        ];

        $result = $this->renderTemplate('track_event', $parameters);
        $this->trackCode($result);
    }

    public function getTrackedCodes(): array
    {
        return $this->trackedCodes;
    }

    public function trackCode(string $code): void
    {
        $this->trackedCodes[] = $code;
        $this->tracker->addCodePart($code, GoogleTracker::BLOCK_BEFORE_TRACK);
    }

    /**
     * @param Transaction $transaction
     * @param ProductAction[] $items
     *
     * @return array
     */
    protected function buildCheckoutCompleteCalls(Transaction $transaction, array $items): array
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
    protected function transformTransaction(Transaction $transaction): array
    {
        return array_merge([
            'id' => $transaction->getId(),                           // order ID - required
            'affiliation' => $transaction->getAffiliation() ?: '',            // affiliation or store name
            'revenue' => round($transaction->getTotal(), 2),     // total - required
            'tax' => round($transaction->getTax(), 2),       // tax
            'coupon' => $transaction->getCoupon(), // voucher code - optional
            'shipping' => round($transaction->getShipping(), 2),  // shipping
        ],
            $transaction->getAdditionalAttributes()
        );
    }

    protected function buildCheckoutCalls(array $items): array
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
    protected function transformProductAction(ProductAction $item): array
    {
        return $this->filterNullValues(
            array_merge([
                'id' => $item->getId(),
                'name' => $item->getName(),
                'category' => $item->getCategory(),
                'brand' => $item->getBrand(),
                'variant' => $item->getVariant(),
                'price' => $item->getPrice() ? Decimal::fromNumeric($item->getPrice())->asString() : '',
                'quantity' => $item->getQuantity() ?: 1,
                'position' => $item->getPosition(),
                'coupon' => $item->getCoupon(),
            ],
                $item->getAdditionalAttributes())
        );
    }

    /**
     * Transform product action into enhanced data object
     *
     * @param ProductImpression $item
     *
     * @return array
     */
    protected function transformProductImpression(ProductImpression $item): array
    {
        $data = $this->filterNullValues(array_merge([
            'id' => $item->getId(),
            'name' => $item->getName(),
            'category' => $item->getCategory(),
            'brand' => $item->getBrand(),
            'variant' => $item->getVariant(),
            'price' => $item->getPrice() ? Decimal::fromNumeric($item->getPrice())->asString() : '',
            'list' => $item->getList(),
            'position' => $item->getPosition(),
        ], $item->getAdditionalAttributes()));

        return $data;
    }

    /**
     * Makes sure dependencies are included once before any call
     */
    protected function ensureDependencies(): void
    {
        if ($this->dependenciesIncluded || empty($this->dependencies)) {
            return;
        }

        $result = $this->renderTemplate('dependencies', [
            'dependencies' => $this->dependencies,
        ]);

        $this->trackCode($result);

        $this->dependenciesIncluded = true;
    }
}
