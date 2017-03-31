## 1 - Basic Idea of price systems
In terms of pricing, the ecommerce framework has the concept of price systems. These price systems are responsible for retrieving or calculating prices and returning so called PriceInfo objects which contain the calculated prices. Each product can have its own price system. 
So very complex pricing structures can be integrated into the system quite easily.

In terms of procuct availabilities and stocks, the very similar concept of availability systems is used. 


## 2 - Configuration of price systems
There are two places where the configuration of price system takes place: 
- **Product class**: each product has the method ```getPriceSystemName()``` which returns the name of its price system. 
- **OnlineShopConfig.php**: in the pricesystems section the mapping between price system names and their implementation classes takes place. Price system implementations at least need to implement the interface ```\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceSystem```, but there exist already some useful concrete implementations.

```php
"pricesystems" => [
            /* Define one or more price systems. The products getPriceSystemName method need to return a name here defined */
            "pricesystem" => [
                [
                    "name" => "default",
                    "class" => "\\OnlineShop\\Framework\\PriceSystem\\AttributePriceSystem",
                    "config" => [
                        "attributename" => "price"
                    ]
                ],
                [
                    "name" => "defaultOfferToolPriceSystem",
                    "class" => "\\OnlineShop\\Framework\\PriceSystem\\AttributePriceSystem",
                    "config" => [
                        "attributename" => "price"
                    ]
                ]
            ]
        ],
```

> The simplest price system is ```\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\AttributePriceSystem``` which reads the price from an attribute of the product object. For implementing custom price systems have a look at method comments of ```\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceSystem``` and the implementations of the existing price systems.


## 3 - Working with prices
Once the price systems are set up correctly, working with prices should be quite easy. Each product has the method ```getOSPrice()``` which returns an ```\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice``` with the price of the product.

Internally the product gets its price system and starts the price calculation to get the price. 

When the price system returns a custom PriceInfo object (e.g. with additional stock prices, customer specific prices etc.), this PriceInfo can be retrieved by calling ```getOSPriceInfo()``` on the product object. 

The snippet for printing the price on a product detail page may be: 
```php
<?php ?>
<p class="price">
   <span><?= $this->product->getOSPrice() ?></span>
</p>
```


## 4 - Using pricing rules
Basically pricing rules are supported by the ecommerce framework out of the box. The pricing rules themselves can be configured in the pimcore backend by putting conditions and actions together. Once active, all rules are checked and applied  automatically by the system - including reducing product prices, adding price modificators to reduce cart totals, removing shipping costs and adding gift items to the cart. 
To print the applied rules in the frontend, the developer needs to add some lines of code. Depending on the location, following scripts can be used. 


#### Product Detail Page
```php
<?php $priceInfo = $this->product->getOSPriceInfo(); ?>
<?php if($priceInfo->getRules()) { ?>
	<div class="discounts">
		<p><strong><?= $this->translate("shop.detail.your_benefit") ?></strong></p>
		<ul>
			<?php foreach($priceInfo->getRules() as $rule ) { ?>
				<?php foreach($rule->getActions() as $action) { ?>
					<?php if($action instanceof \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Action\ProductDiscount) { ?>
						<?php if($action->getAmount() > 0) { ?>
							<li><?= $rule->getLabel() ?> <?= $this->translate("shop.detail.your_benefit.discount.amount", $formatter->formatCurrency($action->getAmount(), "EUR")) ?></li>
						<?php } else if($action->getPercent() > 0) { ?>
							<li><?= $rule->getLabel() ?> <?= $this->translate("shop.detail.your_benefit.discount.percent", $action->getPercent()) ?></li>
						<?php } ?>
					<?php } else if($action instanceof \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Action\IGift) { ?>
							<li>
								<?= $this->translate("shop.detail.your_benefit.discount.gift", '<a href="' . $action->getProduct()->getShopDetailLink($this, true) . '"> ' . $action->getProduct()->getName() . '</a>') ?>
							</li>
					<?php } else if($action instanceof \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Action\FreeShipping) { ?>
						<li>
							<?= $this->translate("shop.detail.your_benefit.discount.freeshipping") ?>
						</li>
					<?php } ?>
				<?php } ?>

			<?php } ?>
		</ul>
	</div>
<?php } ?>
```


#### Cart Product List - printing Gift Items
```php
<?php foreach($cart->getGiftItems() as $item) { $linkDetail = $item->getProduct()->getShopDetailLink($this); ?>
	<tr>
		<td class="cart-list-items-image">
			<a href="<?= $linkDetail ?>" >
				<img src="<?= $item->getProduct()->getFirstImage(array('width' => 120, 'height' => 120, 'aspectratio' => true)) ?> " alt="" border="0" />
			</a>
		</td>
		<td class="cart-list-items-name" valign="top">
			<a href="<?= $linkDetail ?>" ><?= $item->getProduct()->getOSName() ?></a>
		</td>
		<td class="cart-list-items-quantity">
			<?= $item->getCount() ?>
		</td>
	</tr>
<?php } ?>
```
> All other price modifications on cart level are included as cart price modificators. See 'Usage of cart manager' for more details and how to print them. 


## 5 - Using vouchers
Like pricing rules, also vouchers are supported out of the box by the framework. 
But to use vouchers, a few things need to be done. 
- Create an OnlineShopVoucherSeries
- Create tokens based on the OnlineShopVoucherSeries.
- Create a pricing rule for the OnlineShopVoucherSeries and define the benefit of the voucher.
- Allow the user to add a token to his cart. 

#### Create an OnlineShopVoucherSeries
A voucher series contains basic information of the voucher and settings for creating the voucher tokens. It is represented by OnlineShopVoucherSeries objects. Currently there are two types of vouchers supported - Single and Pattern. 

#### Create tokens based on the OnlineShopVoucherSeries
In the pimcore backend, each OnlineShopVoucherSeries object has an additional tab for managing the voucher tokens. Depending on the token type there are different functionalities for managing the tokens and some statistics concerning the voucher. 
- Simple: 'activate' the token and specify how often it may be used. 
- Pattern: create tokens based on the defined pattern, export created tokens as csv and get an overview of created tokens and their usages. 

#### Create a pricing rule for the OnlineShopVoucherSeries
Once a voucher series is defined and tokens are created, a pricing rule has to define the benefits of the voucher. To do so, a special condition allows to specify the voucher series the pricing rule should be valid for. As action all available actions for pricing rules can be used. 

#### Allow the user to add a token to his cart
A voucher token is always applied to a cart. To do so, use following snippet. 
```php
<?php
if ($token = strip_tags($this->getParam('voucherToken'))) {

	try{
        $this->cart->addVoucherToken($token);
    } catch( \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\VoucherServiceException $e ){
        $voucherError = $this->view->t('cart.msg-error.' . $e->getCode());
    }
}

```
#### Error Codes of Exceptions thrown
| Code 	| Description                                                      	|
|------	|------------------------------------------------------------------	|
| 1  	| Token already in use.                                            	|
| 2     | Token already reserved.                                          	|
| 3  	| Token reservation not possible                                   	|
| 4     | No token for this code exists.                                   	|
| 5  	| Criteria oncePerCart: Token of same series already in cart.      	|
| 6 	| Criteria onlyTokenPerCart: Tokens in cart and tried to add token of type "only"|
| 7 	| Criteria onlyTokenPerCart: Token of type "only" already in cart. 	|
| 8 	| No more usages for a single token.|

> Since benefits for vouchers are defined via pricing rules, no special actions are needed to display them. They are just displayed the same way as all other pricing rules.