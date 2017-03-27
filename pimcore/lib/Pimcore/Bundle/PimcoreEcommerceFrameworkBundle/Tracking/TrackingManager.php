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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICheckoutStep;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct;
use Pimcore\Config\Config;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class TrackingManager implements ITrackingManager
{
    /** @var  */
    protected $config;

    /** @var ITracker[] */
    protected $trackers = [];

    /** @var ITrackingItemBuilder[] */
    protected $trackingItemBuilders = [];

    /**
     * @var EngineInterface
     */
    protected $renderer;

    /**
     * @param Config $config
     * @throws InvalidConfigException
     */
    public function __construct(Config $config, EngineInterface $renderer)
    {
        $this->renderer = $renderer;
        $this->processConfig($config);
    }

    /**
     * Process config and register configured trackers
     *
     * @param Config $config
     * @throws InvalidConfigException
     */
    protected function processConfig(Config $config)
    {
        $container = new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tools\Config\HelperContainer($config, 'trackingmanager');

        foreach ($container->trackers as $cfg) {
            $this->processConfigEntry($cfg);
        }
    }

    /**
     * Used by processConfig to handle single config entry
     * 
     * @param Config $cfg
     * @throws InvalidConfigException
     */
    protected function processConfigEntry(Config $cfg)
    {
        $className = $cfg->class;
        if (!class_exists($className)) {
            throw new InvalidConfigException(sprintf('Tracker class %s not found.', $className));
        }

        $itemBuilder = $this->getItemBuilder($cfg->trackingItemBuilder);
        $tracker     = new $className($itemBuilder, $this->renderer);

        if($tracker instanceof ITracker) {
            $this->registerTracker($cfg->name, $tracker);
        } else {
            throw new InvalidConfigException(sprintf('Tracker class %s not an insance of ITracker.', $className));
        }

    }
    /**
     * Get an item builder instance, fall back to default implementation
     *
     * @param null $className
     * @return ITrackingItemBuilder
     * @throws InvalidConfigException
     */
    protected function getItemBuilder($className = null)
    {
        $itemBuilder = null;
        if (null !== $className) {
            if (!class_exists($className)) {
                throw new InvalidConfigException(sprintf('Tracking item builder class %s not found.', $className));
            }

            $itemBuilder = new $className();
        } else {
            // fall back to default implementation
            $itemBuilder = new TrackingItemBuilder();
        }

        return $itemBuilder;
    }

    /**
     * Register a tracker
     *
     * @param ITracker $tracker
     * @return $this
     */
    public function registerTracker($name, ITracker $tracker)
    {
        if (!isset($this->trackers[$name])) {
            $this->trackers[$name] = $tracker;
        }

        return $this;
    }

    /**
     * Get all registered trackers
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ITracker[]
     */
    public function getTrackers()
    {
        return $this->trackers;
    }


    /**
     * Ensure the dependency for enhanced e-commerce tracking "ec.js" 
     * is included.
     */
    public function ensureDependencies()
    {
        foreach ($this->trackers as $tracker) {
            $tracker->includeDependencies();
        }
        
        return $this;
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
        $this->ensureDependencies();
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
     * @implements IProductView
     */
    public function trackProductView(IProduct $product)
    {
        $this->ensureDependencies();
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
     * @param int $quantity
     */
    public function trackProductActionAdd(IProduct $product, $quantity = 1)
    {
        $this->ensureDependencies();
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
     * @param int $quantity
     */
    public function trackProductActionRemove(IProduct $product, $quantity = 1)
    {
        $this->ensureDependencies();
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
     * @param ICart $cart
     */
    public function trackCheckout(ICart $cart)
    {
        $this->ensureDependencies();
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
        if(!$order->getProperty("os_tracked")) {

            //add property to order object in order to prevent multiple checkout complete tracking
            $order->setProperty("os_tracked", "bool", true);
            $order->save();

            $this->ensureDependencies();
            foreach ($this->trackers as $tracker) {
                if ($tracker instanceof ICheckoutComplete) {
                    $tracker->trackCheckoutComplete($order);
                }
            }
        }
    }

    /**
     * Track checkout step
     *
     * @implements ICheckoutStep
     *
     * @param ICheckoutStep $step
     * @param ICart $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(ICheckoutStep $step, ICart $cart, $stepNumber = null, $checkoutOption = null)
    {
        $this->ensureDependencies();
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking\ICheckoutStep) {
                $tracker->trackCheckoutStep($step, $cart, $stepNumber, $checkoutOption);
            }
        }
    }
}
