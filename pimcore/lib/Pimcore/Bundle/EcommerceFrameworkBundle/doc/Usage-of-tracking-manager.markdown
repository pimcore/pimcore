## 1 - Tracking Manager Configuration

The Tracking Manager enables ecommerce transaction tracking for ecommerce websites built with the framework. Due to
different tracker implementations, it supports different tracking services.

Current implementations of trackers are
* **Google Analytics Classic**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\Ecommerce`
* **Google Analytics Universal**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\UniversalEcommerce`
* **Google Analytics Enhanced Ecommerce**: `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\EnhancedEcommerce`

### Tracking Actions

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

Depending on the used tracking service some of these actions might not be available.
If so, the tracking action is ignored for this tracker.


### Configuration in `EcommerceFrameworkConfig.php`

The configuration takes place in the `EcommerceFrameworkConfig.php`. If no `TrackingItemBuilder` is configured, the
`TrackingItemBuilder` will
fall back to the default implementation `\Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder`.
```php
"trackingmanager" => [
            "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\TrackingManager",
            "config" => [
                "trackers" => [
                    "tracker" => [
                        [
                        "name" => "GoogleAnalyticsEnhancedEcommerce",
                        "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\Tracker\\Analytics\\EnhancedEcommerce",
                        "trackingItemBuilder" => "AppBundle\\Ecommerce\\Tracking\\TrackingItemBuilder"
                        ]
                    ]
                ]
            ]
        ],
```

## 2 - Usage Tracking Manager

For utilizing the Tracking Manager, just call the corresponding methods of the TrackingManager in your controller.
The framework does the rest (adding necessary code snippets to your view).

See the following examples

### Product Impression
```php
<?php
class ShopController extends \Pimcore\Controller\Action\Frontend {
    public function listAction() {
        ...
        $paginator = new Zend\Paginator( $products );
        $paginator->setCurrentPageNumber( $this->getParam('page') );

        $trackingManager = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getTrackingManager();
        foreach($paginator as $product) {
            $trackingManager->trackProductImpression($product);
        }
        ...
    }
}

```

### Checkout
```php
<?php
class CheckoutController extends \Pimcore\Controller\Action\Frontend {
    public function startCheckoutAction() {
        ...
        $trackingManager = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getTrackingManager();
        $trackingManager->trackCheckout($this->getCart());
        ...
    }
}

```


## 3 - Project Specific Data

Adding project specific data to tracking items by extending the `TrackingItemBuilder` class. The extending class has to
be configured in the `EcommerceFrameworkConfig.php`.

### Example for Additional Data in Poduct Impressions

```php
class TrackingItemBuilder extends \Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder {

    private static $impressionPosition = 0;
    
    public function buildProductImpressionItem(IProduct $product)
    {
        $item = parent::buildProductImpressionItem($product);
        
        $item->setVariant($product->getVariantName());
        $item->setId($product->getOSProductNumber());
        
        // define a name for the impressions context (i.e. grid, search, ... )
        $item->setList('grid');

        self::$impressionPosition++;
        $item->setPosition(self::$impressionPosition);

        return $item;
    }
}
```


## External Links
[Google Documentation Enhanced E-Commerce](https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce)
