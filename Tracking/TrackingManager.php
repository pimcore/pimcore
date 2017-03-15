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

use OnlineShop\Framework\CartManager\ICart;
use OnlineShop\Framework\CheckoutManager\ICheckoutStep;
use OnlineShop\Framework\Model\AbstractOrder;
use OnlineShop\Framework\Model\IProduct;
use OnlineShop\Framework\Exception\InvalidConfigException;

class TrackingManager implements ITrackingManager
{
    /** @var  */
    protected $config;

    /** @var ITracker[] */
    protected $trackers = [];

    /** @var ITrackingItemBuilder[] */
    protected $trackingItemBuilders = [];

    /**
     * @param \Zend_Config $config
     * @throws InvalidConfigException
     */
    public function __construct(\Zend_Config $config)
    {
        $this->processConfig($config);
    }

    /**
     * Process config and register configured trackers
     *
     * @param \Zend_Config $config
     * @throws InvalidConfigException
     */
    protected function processConfig(\Zend_Config $config)
    {
        $container = new \OnlineShop\Framework\Tools\Config\HelperContainer($config, 'trackingmanager');

        if(!isset($container->trackers->toArray()['tracker']['name'])) {
            foreach ($container->trackers->tracker as $cfg) {
                $this->processConfigEntry($cfg);
            }
        } else {
            $this->processConfigEntry($container->trackers->tracker);
        }


    }

    /**
     * Used by processConfig to handle single config entry
     * 
     * @param \Zend_Config $cfg
     * @throws InvalidConfigException
     */
    protected function processConfigEntry(\Zend_Config $cfg)
    {
        $className = $cfg->class;
        if (!class_exists($className)) {
            throw new InvalidConfigException(sprintf('Tracker class %s not found.', $className));
        }

        $itemBuilder = $this->getItemBuilder($cfg->trackingItemBuilder);
        $tracker     = new $className($itemBuilder);

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
     * @return \OnlineShop\Framework\Tracking\ITracker[]
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
     * @param \OnlineShop\Framework\CheckoutManager\ICheckoutStep $step
     * @param ICart $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(ICheckoutStep $step, ICart $cart, $stepNumber = null, $checkoutOption = null)
    {
        $this->ensureDependencies();
        foreach ($this->trackers as $tracker) {
            if ($tracker instanceof \OnlineShop\Framework\Tracking\ICheckoutStep) {
                $tracker->trackCheckoutStep($step, $cart, $stepNumber, $checkoutOption);
            }
        }
    }
}
