# Tracking Manager

The Tracking Manager enables e-commerce transaction tracking for e-commerce websites built with the framework. Due to
different tracker implementations, it supports different tracking services.

Current implementations of trackers are

* **Google Tag Manager (GTM)**: `\\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\GoogleTagManager`
* **Google Analytics Classic**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\Ecommerce`
* **Google Analytics Universal**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\UniversalEcommerce`
* **Google Analytics Enhanced E-Commerce**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\EnhancedEcommerce`
* **Matomo**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Piwik`

## Supported Tracking Actions

The Tracking Manager supports several tracking actions that can be used. These tracking actions are delegated to the 
trackers. 

* Product Impression
    * Tracks product impression
    * `$trackingManager->trackProductImpression($product)`
* Product View
    * Tracks product view (of detail page)
    * `$trackingManager->trackProductView($product)`
* Category View
    * Tracks a category page view
    * `$trackingManager->trackCategoryPageView($category)`
* Cart Product Action Add
    * Tracks action for adding product to cart
    * `$trackingManager->trackCartProductActionAdd($cart, $product, $quantity)`
* Cart Product Action Remove
    * Tracks action for removing product from cart
    * `$trackingManager->trackProductActionRemove($cart, $product, $quantity)`
* Cart Update
    * Tracks a generic cart update for trackers not supporting add/remove. This can be sent on every cart list or cart
      change (see [Piwik Docs](https://piwik.org/docs/ecommerce-analytics/#tracking-add-to-cart-items-added-to-the-cart-optional)
      for an example)
    * `$trackingManager->trackProductActionRemove($cart, $product, $quantity)`
* Checkout
    * Tracks start of checkout with first step
    * `$trackingManager->trackCheckout($cart)`
* Checkout Step
    * Tracks checkout step
    * `$trackingManager->trackCheckoutStep($step, $cart, $stepNumber, $checkoutOption)`
* Checkout Complete
    * Tracks checkout complete
    * `$trackingManager->trackCheckoutComplete($order)`
    
There are 2 deprecated actions which are still supported by should be replaced with their new counterparts:

* Product Action Add (use `trackCartProductActionAdd` instead)
    * Tracks action for adding product to cart
    * `$trackingManager->trackProductActionAdd($product, $quantity)`
* Product Action Remove (use `trackCartProductActionRemove` instead)
    * Tracks action for removing product from cart
    * `$trackingManager->trackProductActionRemove($product, $quantity)` 

> Be aware: Depending on the used tracking service some of these actions might not be available.
> If so, the tracking action is ignored for this tracker.


## Configuration

The configuration takes place in the `pimcore_ecommerce_framework.tracking_manager` config section.
If no `TrackingItemBuilder` is configured, the `TrackingItemBuilder` will fall back to the default implementation 
`\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder`. Further information about `TrackingItemBuilder`
see below. 

```yaml
pimcore_ecommerce_framework:
    # tracking manager - define which trackers (e.g. Google Analytics Universal Ecommerce) are active and should
    # be called when you track something via TrackingManager
    tracking_manager:
        # service ID of tracking manager - the following is the default value and can be omitted
        tracking_manager_id: Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager

        trackers:
            # enable the core enhanced_ecommerce tracker with default options
            enhanced_ecommerce:
                enabled: true
                
            my_custom_tracker:
                # use already defined enhanced ecommerce tracker
                id: Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\EnhancedEcommerce
                
                # options vary by tracker implementation
                options:
                    template_prefix: AppBundle:Tracking/analytics/enhanced 
           
                # service id for item builder
                item_builder_id: AppBundle\Ecommerce\Tracking\TrackingItemBuilder
                
                # List of assortment and checkout tenants where this tracker should be activated for.
                tenants:

                    # Add list of assortment tenants where the tracker should be activated for. Empty array means activated for all tenants.
                    assortment:           []

                    # Add list of checkout tenants where the tracker should be activated for. Empty array means activated for all tenants.
                    checkout:             []                
```

## Tracking Manager With Tenants

The Tracking Manager supports tenants in a different way than the other framework modules. While with the other modules,
the tenant configuration takes place on the highest level of configuration and each configuration is only valid for one 
tenant, the tenant configuration in the tracking manager takes place within the trackers them self. There a list of assortment
and checkout tenants for which the tracker should be enabled can be provided (see configuration above).

If nothing is set or an empty array is provided, the tracker is active for all tenants.     


## Working with Tracking Manager

For utilizing the Tracking Manager, just call the corresponding methods of the TrackingManager in your controller.
The framework does the rest (adding necessary code snippets to your view, etc.).

See the following examples

### Product Impression
```php
<?php

namespace AppBundle\Controller;

use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Zend\Paginator\Paginator;

class ShopController extends FrontendController
{
    public function listAction(Request $request, TrackingManager $trackingManager)
    {       
        // ...
        $paginator = new Paginator($products);
        $paginator->setCurrentPageNumber( $request->get('page') );

        foreach ($paginator as $product) {
            $trackingManager->trackProductImpression($product);
        }
        
        // ...
    }
}
```

### Checkout
```php
<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;

class CheckoutController extends AbstractCartAware
{
    public function startCheckoutAction(TrackingManager $trackingManager) {
        ...
        $trackingManager->trackCheckout($this->getCart());
        ...
    }
}

```

## Project Specific Data

Adding project specific data to tracking items by extending the `TrackingItemBuilder` class. The extending class has to
be defined as service and configured on the tracker configuration (see above).

### Example for Additional Data in Product Impressions

Define a custom item builder:

```php
<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;

class TrackingItemBuilder extends \Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder
{
    private static $impressionPosition = 0;
    
    public function buildProductImpressionItem(ProductInterface $product)
    {
        $item = parent::buildProductImpressionItem($product);

        $item->setId($product->getOSProductNumber());

        $item->setList($this->getImpressionListName());

        self::$impressionPosition++;
        $item->setPosition(self::$impressionPosition);

        return $item;
    }
}
```

And define it as service:

```yaml
services:
    AppBundle\Ecommerce\Tracking\TrackingItemBuilder: ~
```


## External Links
[Google Documentation Enhanced E-Commerce](https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce)
