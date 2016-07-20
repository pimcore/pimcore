## 1 - Tracking Manager configuration

### Configuration

The configuration takes place in the OnlineShopConfig.xml. If no _Tracker_ is configured, the _TrackingItemBuilder_ will fall back to the default implementation.
```xml
<trackingmanager class="Website\OnlineShop\Tracking\TrackingManager">
   <config>
       <trackers>
           <tracker name="GoogleAnalyticsEnhancedEcommerce" class="Website\OnlineShop\Tracking\Tracker\EnhancedEcommerce" trackingItemBuilder="Website\OnlineShop\Tracking\TrackingItemBuilder" />
       </trackers>
   </config>
</trackingmanager>
```

### Overview

##### External Links
[Google Documentation Enhanced E-Commerce](https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce)

#### Tracking Actions

* Product Impression        (```$trackingManager->trackProductImpression($product)```)
* Product View              (```$trackingManager->trackProductView($product)```)
* Product Action Add        (```$trackingManager->trackProductActionAdd($product, $quantity)```)
* Product Action Remove     (```$trackingManager->trackProductActionRemove($product, $quantity)```)
* Checkout                  (```$trackingManager->trackCheckout($cart)```)
* Checkout Complete         (```$trackingManager->trackCheckoutComplete($order)```)
* Checkout Step             (```$trackingManager->trackCheckoutStep($step, $cart, $stepNumber, $checkoutOption)```)

## 2 - Usage Tracking Manager

#### Trigger events
###### _Product Impression_
```php
<?php
class ShopController extends \Pimcore\Controller\Action\Frontend {
    public function listAction() {
        ...
        $paginator = Zend_Paginator::factory( $products );
        $paginator->setCurrentPageNumber( $this->getParam('page') );

        $trackingManager = \OnlineShop\Framework\Factory::getInstance()->getTrackingManager();
        foreach($paginator as $product) {
            $trackingManager->trackProductImpression($product);
        }
        ...
    }
}

```

###### _Checkout_
```php
<?php
class CartController extends \Pimcore\Controller\Action\Frontend {
    public function listAction() {
        ...
        $trackingManager = \OnlineShop\Framework\Factory::getInstance()->getTrackingManager();
        $trackingManager->trackCheckout($this->getCart());
        ...
    }
}

```


## 3 - Project specific data

Adding project specific data to tracking items by extending the TrackingItemBuilder class. The extending class has to be defined in the OnlineShopConfig.xml.

###### _Product Impression_

```php
class TrackingItemBuilder extends \OnlineShop\Framework\Tracking\TrackingItemBuilder {

    private static $impressionPosition = 0;
    
    public function buildProductImpressionItem(IProduct $product)
    {
        $item = parent::buildProductImpressionItem($product);
        
        $item->setVariant($product->getVariantName());
        $item->setId($product->getOSProductNumber());
        
        // define a name for the impressions context (i.e. grid, search, ... )
        $item->setList(\Zend_Controller_Front::getInstance()->getRequest()->getActionName());

        self::$impressionPosition++;
        $item->setPosition(self::$impressionPosition);

        return $item;
    }
}
```


