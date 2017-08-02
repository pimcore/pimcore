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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutStep as CheckoutManagerICheckoutStep;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct;

class TrackingManager implements ITrackingManager
{
    /**
     * @var ITracker[]
     */
    protected $trackers = [];

    /**
     * @param ITracker[] $trackers
     */
    public function __construct(array $trackers = [])
    {
        foreach ($trackers as $tracker) {
            $this->registerTracker($tracker);
        }
    }

    /**
     * Register a tracker
     *
     * @param ITracker $tracker
     */
    public function registerTracker(ITracker $tracker)
    {
        $this->trackers[] = $tracker;
    }

    /**
     * Get all registered trackers
     *
     * @return ITracker[]
     */
    public function getTrackers(): array
    {
        return $this->trackers;
    }

    /**
     * Track product impression
     *
     * @implements IProductImpression
     *
     * @param IProduct $product
     */
    public function trackProductImpression(IProduct $product)
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof IProductImpression) {
                $tracker->trackProductImpression($product);
            }
        }
    }

    /**
     * Track product view
     *
     * @param IProduct $product
     *
     * @implements IProductView
     */
    public function trackProductView(IProduct $product)
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof IProductView) {
                $tracker->trackProductView($product);
            }
        }
    }

    /**
     * Track product add to cart
     *
     * @implements IProductImpression
     *
     * @param IProduct $product
     * @param int|float $quantity
     */
    public function trackProductActionAdd(IProduct $product, $quantity = 1)
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof IProductActionAdd) {
                $tracker->trackProductActionAdd($product, $quantity);
            }
        }
    }

    /**
     * Track product remove from cart
     *
     * @implements IProductImpression
     *
     * @param IProduct $product
     * @param int|float $quantity
     */
    public function trackProductActionRemove(IProduct $product, $quantity = 1)
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof IProductActionRemove) {
                $tracker->trackProductActionRemove($product, $quantity);
            }
        }
    }

    /**
     * Track start checkout with first step
     *
     * @implements ICheckoutComplete
     *
     * @param ICart $cart
     */
    public function trackCheckout(ICart $cart)
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof ICheckout) {
                $tracker->trackCheckout($cart);
            }
        }
    }

    /**
     * Track checkout complete
     *
     * @implements ICheckoutComplete
     *
     * @param AbstractOrder $order
     */
    public function trackCheckoutComplete(AbstractOrder $order)
    {
        if ($order->getProperty('os_tracked')) {
            return;
        }

        // add property to order object in order to prevent multiple checkout complete tracking
        $order->setProperty('os_tracked', 'bool', true);
        $order->save();

        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof ICheckoutComplete) {
                $tracker->trackCheckoutComplete($order);
            }
        }
    }

    /**
     * Track checkout step
     *
     * @implements ICheckoutStep
     *
     * @param CheckoutManagerICheckoutStep $step
     * @param ICart $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(CheckoutManagerICheckoutStep $step, ICart $cart, $stepNumber = null, $checkoutOption = null)
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof ICheckoutStep) {
                $tracker->trackCheckoutStep($step, $cart, $stepNumber, $checkoutOption);
            }
        }
    }
}
