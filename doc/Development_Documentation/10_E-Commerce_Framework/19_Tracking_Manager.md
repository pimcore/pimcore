# Tracking Manager

The Tracking Manager enables e-commerce transaction tracking for e-commerce websites built with the framework. Due to
different tracker implementations, it supports different tracking services.

Current implementations of trackers are
* **Google Analytics Classic**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\Ecommerce`
* **Google Analytics Universal**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\UniversalEcommerce`
* **Google Analytics Enhanced E-Commerce**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\EnhancedEcommerce`


## Supported Tracking Actions
The Tracking Manager supports several tracking actions that can be used. These tracking actions are delegated to the 
trackers. 

* Product Impression
    * Tracks product impression
    * `$trackingManager->trackProductImpression($product)`
* Product View
    * Tracks product view (of detail page)
    * `$trackingManager->trackProductView($product)`
* Product Action Add
    * Tracks action for adding product to cart
    * `$trackingManager->trackProductActionAdd($product, $quantity)`
* Product Action Remove
    * Tracks action for removing product from cart
    * `$trackingManager->trackProductActionRemove($product, $quantity)`
* Checkout
    * Tracks start of checkout with first step
    * `$trackingManager->trackCheckout($cart)`
* Checkout Step
    * Tracks checkout step
    * `$trackingManager->trackCheckoutStep($step, $cart, $stepNumber, $checkoutOption)`
* Checkout Complete
    * Tracks checkout complete
    * `$trackingManager->trackCheckoutComplete($order)`

> Be aware: Depending on the used tracking service some of these actions might not be available.
> If so, the tracking action is ignored for this tracker.


## Configuration in `EcommerceFrameworkConfig.php`

The configuration takes place in the [`EcommerceFrameworkConfig.php`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/EcommerceFrameworkConfig_sample.php#L701).
If no `TrackingItemBuilder` is configured, the `TrackingItemBuilder` will fall back to the default implementation 
`\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder`. Further information about `TrackingItemBuilder`
see below. 

```php
/*  tracking manager - define which trackers (e.g. Google Analytics Universal Ecommerce) are active and should
be called when you track something via TrackingManager */
'trackingmanager' => [
    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\TrackingManager',
    'config' => [
        'trackers' => [
            [
                'name' => 'GoogleAnalyticsEnhancedEcommerce',
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\Tracker\\Analytics\\EnhancedEcommerce',
                'trackingItemBuilder' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\TrackingItemBuilder'
            ]
        ]
    ]
],
```


## Working with Tracking Manager

For utilizing the Tracking Manager, just call the corresponding methods of the TrackingManager in your controller.
The framework does the rest (adding necessary code snippets to your view, etc.).

See the following examples

### Product Impression
```php
<?php

namespace AppBundle\Controller;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Zend\Paginator\Paginator;

class ShopController extends FrontendController
{
    public function listAction(Request $request)
    {       
        // ...
        $paginator = new Paginator($products);
        $paginator->setCurrentPageNumber( $request->get('page') );

        $trackingManager = Factory::getInstance()->getTrackingManager();
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
class CheckoutController extends AbstractCartAware {
    public function startCheckoutAction() {
        ...
        $trackingManager = Factory::getInstance()->getTrackingManager();
        $trackingManager->trackCheckout($this->getCart());
        ...
    }
}

```

## Project Specific Data

Adding project specific data to tracking items by extending the `TrackingItemBuilder` class. The extending class has to
be configured in the [`EcommerceFrameworkConfig.php`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/EcommerceFrameworkConfig_sample.php#L708).

### Example for Additional Data in Product Impressions

```php
class TrackingItemBuilder extends \OnlineShop\Framework\Tracking\TrackingItemBuilder {

    private static $impressionPosition = 0;
    
    public function buildProductImpressionItem(IProduct $product)
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


## External Links
[Google Documentation Enhanced E-Commerce](https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce)
