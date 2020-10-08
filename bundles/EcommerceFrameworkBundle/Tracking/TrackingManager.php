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
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutStepInterface as CheckoutManagerCheckoutStepInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EventListener\Frontend\TrackingCodeFlashMessageListener;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TrackingManager implements TrackingManagerInterface
{
    /**
     * @var TrackerInterface[]
     */
    protected $trackers = [];

    /**
     * @var TrackerInterface[]
     */
    protected $activeTrackerCache = [];

    /**
     * @var string
     */
    protected $cachedAssortmentTenant = null;

    /**
     * @var string
     */
    protected $cachedCheckoutTenant = null;

    /**
     * @var null|EnvironmentInterface
     */
    protected $enviroment = null;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param TrackerInterface[] $trackers
     * @param EnvironmentInterface $environment
     */
    public function __construct(array $trackers = [], EnvironmentInterface $environment)
    {
        foreach ($trackers as $tracker) {
            $this->registerTracker($tracker);
        }

        $this->enviroment = $environment;
    }

    /**
     * @param Session $session
     * @required
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Register a tracker
     *
     * @param TrackerInterface $tracker
     */
    public function registerTracker(TrackerInterface $tracker)
    {
        $this->trackers[] = $tracker;
    }

    /**
     * Get all registered trackers
     *
     * @return TrackerInterface[]
     */
    public function getTrackers(): array
    {
        return $this->trackers;
    }

    /**
     * Get all for current tenants active trackers
     *
     * @return TrackerInterface[]
     */
    public function getActiveTrackers(): array
    {
        $currentAssortmentTenant = $this->enviroment->getCurrentAssortmentTenant() ?: 'default';
        $currentCheckoutTenant = $this->enviroment->getCurrentCheckoutTenant() ?: 'default';

        if ($currentAssortmentTenant !== $this->cachedAssortmentTenant || $currentCheckoutTenant !== $this->cachedCheckoutTenant) {
            $this->cachedCheckoutTenant = $currentCheckoutTenant;
            $this->cachedAssortmentTenant = $currentAssortmentTenant;

            $this->activeTrackerCache = [];
            foreach ($this->trackers as $tracker) {
                $active = false;
                if (empty($tracker->getAssortmentTenants()) || in_array($currentAssortmentTenant, $tracker->getAssortmentTenants())) {
                    $active = true;
                }
                if (empty($tracker->getCheckoutTenants()) || in_array($currentCheckoutTenant, $tracker->getCheckoutTenants())) {
                    $active = true;
                }

                if ($active) {
                    $this->activeTrackerCache[] = $tracker;
                }
            }
        }

        return $this->activeTrackerCache;
    }

    /**
     * Tracks a category page view
     *
     * @param array|string $category One or more categories matching the page
     * @param mixed $page            Any kind of page information you can use to track your page
     */
    public function trackCategoryPageView($category, $page = null)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof CategoryPageViewInterface) {
                $tracker->trackCategoryPageView($category, $page);
            }
        }
    }

    /**
     * Track product impression
     *
     * @implements IProductImpression
     *
     * @param ProductInterface $product
     * @param string $list
     */
    public function trackProductImpression(ProductInterface $product, string $list = 'default')
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof ProductImpressionInterface) {
                $tracker->trackProductImpression($product, $list);
            }
        }
    }

    /**
     * Track product view
     *
     * @param ProductInterface $product
     *
     * @implements ProductInterfaceView
     */
    public function trackProductView(ProductInterface $product)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof ProductViewInterface) {
                $tracker->trackProductView($product);
            }
        }
    }

    /**
     * Track a cart update
     *
     * @param CartInterface $cart
     */
    public function trackCartUpdate(CartInterface $cart)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof CartUpdateInterface) {
                $tracker->trackCartUpdate($cart);
            }
        }
    }

    /**
     * Track product add to cart
     *
     * @param CartInterface $cart
     * @param ProductInterface $product
     * @param int|float $quantity
     */
    public function trackCartProductActionAdd(CartInterface $cart, ProductInterface $product, $quantity = 1)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof CartProductActionAddInterface) {
                $tracker->trackCartProductActionAdd($cart, $product, $quantity);
            }
        }
    }

    /**
     * Track product add to cart
     *
     * @deprecated Use CartProductActionAddInterface::trackCartProductActionAdd instead
     *
     * @param ProductInterface $product
     * @param int|float $quantity
     */
    public function trackProductActionAdd(ProductInterface $product, $quantity = 1)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof IProductActionAdd) {
                $tracker->trackProductActionAdd($product, $quantity);
            }
        }
    }

    /**
     * Track product remove from cart
     *
     * @param CartInterface $cart
     * @param ProductInterface $product
     * @param int|float $quantity
     */
    public function trackCartProductActionRemove(CartInterface $cart, ProductInterface $product, $quantity = 1)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof CartProductActionRemoveInterface) {
                $tracker->trackCartProductActionRemove($cart, $product, $quantity);
            }
        }
    }

    /**
     * Track product remove from cart
     *
     * @deprecated Use CartProductActionRemoveInterface::trackCartProductActionRemove instead
     *
     * @param ProductInterface $product
     * @param int|float $quantity
     */
    public function trackProductActionRemove(ProductInterface $product, $quantity = 1)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof IProductActionRemove) {
                $tracker->trackProductActionRemove($product, $quantity);
            }
        }
    }

    /**
     * Track start checkout with first step
     *
     * @implements CheckoutCompleteInterface
     *
     * @param CartInterface $cart
     */
    public function trackCheckout(CartInterface $cart)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof CheckoutInterface) {
                $tracker->trackCheckout($cart);
            }
        }
    }

    /**
     * Track checkout complete
     *
     * @implements CheckoutCompleteInterface
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

        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof CheckoutCompleteInterface) {
                $tracker->trackCheckoutComplete($order);
            }
        }
    }

    /**
     * Track checkout step
     *
     * @implements CheckoutStepInterface
     *
     * @param CheckoutManagerCheckoutStepInterface $step
     * @param CartInterface $cart
     * @param string|null $stepNumber
     * @param string|null $checkoutOption
     */
    public function trackCheckoutStep(CheckoutManagerCheckoutStepInterface $step, CartInterface $cart, $stepNumber = null, $checkoutOption = null)
    {
        foreach ($this->getActiveTrackers() as $tracker) {
            if ($tracker instanceof CheckoutStepInterface) {
                $tracker->trackCheckoutStep($step, $cart, $stepNumber, $checkoutOption);
            }
        }
    }

    public function getTrackedCodes(): string
    {
        $result = '';
        foreach ($this->getTrackers() as $tracker) {
            if ($tracker instanceof TrackingCodeAwareInterface) {
                if (count($tracker->getTrackedCodes())) {
                    $result .= implode(PHP_EOL, $tracker->getTrackedCodes()).PHP_EOL.PHP_EOL;
                }
            }
        }

        return $result;
    }

    public function forwardTrackedCodesAsFlashMessage(): TrackingManagerInterface
    {
        $trackedCodes = [];

        foreach ($this->getTrackers() as $tracker) {
            if ($tracker instanceof TrackingCodeAwareInterface) {
                if (count($tracker->getTrackedCodes())) {
                    $trackedCodes[get_class($tracker)] = $tracker->getTrackedCodes();
                }
            }
        }

        $this->session->getFlashBag()->set(TrackingCodeFlashMessageListener::FLASH_MESSAGE_BAG_KEY, $trackedCodes);

        return $this;
    }

    public function trackEvent(
        string $eventCategory,
        string $eventAction,
        string $eventLabel = null,
        int $eventValue = null
    ) {
        foreach ($this->getTrackers() as $tracker) {
            if ($tracker instanceof TrackEventInterface) {
                $tracker->trackEvent($eventCategory, $eventAction, $eventLabel, $eventValue);
            }
        }
    }
}
