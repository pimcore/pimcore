# Price Systems

In terms of pricing, the E-Commerce Framework has the concept of Price Systems. These Price Systems are responsible for 
retrieving or calculating prices and returning so called `PriceInfo` objects which contain the calculated prices. 
Each product can have its own Price System. 

So very complex pricing structures and different price sources can be integrated into the system quite easily.

In terms of product availabilities and stocks, the very similar concept of Availability Systems is used. 


## Configuration of Price Systems
There are two places where the configuration of Price Systems takes place: 
- **Product class**: Each product has to implement the method ```getPriceSystemName()``` which returns the name of its 
  Price System. 
- [**EcommerceFrameworkConfig.php**](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/EcommerceFrameworkConfig_sample.php#L56-L56):
  In the `pricesystems` section the mapping between Price System names and their implementation classes takes place. 
  Price System implementations at least need to implement the interface `\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem`, 
  but [there](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/PriceSystem) 
  already exist some useful concrete implementations. 

```php
'pricesystems' => [
    /* Define one or more price systems. The products getPriceSystemName method need to return a name here defined */
    'pricesystem' => [
        [
            'name' => 'default',
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PriceSystem\\AttributePriceSystem',
            'config' => [
                'attributename' => 'price'
            ]
        ],
        [
            'name' => 'defaultOfferToolPriceSystem',
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PriceSystem\\AttributePriceSystem',
            'config' => [
                'attributename' => 'price'
            ]
        ]
    ]
],
```

> The simplest price system is [`\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/PriceSystem/AttributePriceSystem.php) 
> which reads the price from an attribute of the product object. For implementing custom price systems have a look at method comments 
> of [`\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/PriceSystem/IPriceSystem.php) 
> and the implementations of the existing price systems. 


## Getting and Printing Prices
Once the Price Systems are set up correctly, working with prices should be quite easy. Each product has the method 
`getOSPrice()` which returns a `\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice` object with the price of 
the product. 

Internally the product gets its Price System and starts the price calculation to get the price. 

When the price system returns a custom `PriceInfo` object (e.g. with additional stock prices, customer specific prices etc.), 
this `PriceInfo` can be retrieved by calling `getOSPriceInfo()` method of the product object. 

A sample for printing the price on a product detail page is: 
```php
<?php ?>
<p class="price">
   <span><?= $this->product->getOSPrice() ?></span>
</p>
```
