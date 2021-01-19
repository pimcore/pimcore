# Cart Manager

The Cart Manager is responsible for all aspects concerning carts and can manage multiple carts. 
Important to know is, that in the E-Commerce Framework every user specific product collection is a cart. No matter 
how it is called (cart, wish list, compare list, ...), all these product collections need the same base 
functionality. Therefore all different product collections are carts with a specific name.
 

## Working with Carts

#### Creating carts
```php
<?php
$manager = Factory::getInstance()->getCartManager();
$cartId = $manager->createCart(array('name' => $cartName));
$cart = $manager->getCart( $cartId );
```

#### Adding and removing products
```php
<?php
$manager = Factory::getInstance()->getCartManager();
$cart = $manager->getCartByName($cartName);

$id = $cart->addItem( $product, $amount );
$cart->save();

$cart->removeItem( $id );
$cart->save();


//alternative way

$cartItemId = $manager->addToCart( $product, $amount, $cart->getId() );
//save is done automatically 

$manager->removeFromCart($cartItemId, $cartId);
//save is done automatically 
```

#### List products of cart
```php
<?php
$manager = Factory::getInstance()->getCartManager();
$cart = $manager->getCartByName($cartName);

if (count($cart->getItems()) > 0) {
  foreach ($cart->getItems() as $item) {
    $product = $item->getProduct();
 
    //item key
    $cartItemId = $item->getItemKey();
 
    //item amount
    $amount = $item->getCount();
 
    //price info and price
    $priceInfo = $item->getPriceInfo();
    $price = $item->getPrice(); 
  }
}
```


## Price Calculation in Carts
Each cart has a `CartPriceCalculator` (configuration see below) which is responsible for calculating total sums of the 
cart. The `CartPriceCalculator` sums up all product prices to a sub total, adds (or subtracts) so called price 
modificators like shipping costs, cart discounts, etc. and then calculates a grand total. 

See sample below for how to get the different sums: 
```php
<?php
// delivers sum without any price modifications
$subTotal = $cart->getPriceCalculator()->getSubTotal();
 
// iterates through all price modifications
foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
    // $name is the label of a modification
    // $modification is a OnlineShop_Framework_IModificatedPrice
}
 
// delivers sum including all price modifications
$grandTotal = $cart->getPriceCalculator()->getGrandTotal();
``` 


## Configuration of Cart Manager

The configuration takes place in the `pimcore_ecommerce_framework.cart_manager` configuration section which is [tenant aware](./04_Configuration/README.md).

```yaml
pimcore_ecommerce_framework:
    cart_manager:
        tenants:
            # defaults for all cart managers
            _defaults:
                # define service manager id of cart service - following value is default and can be omitted
                cart_manager_id: Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\MultiCartManager

                # configuration for carts - the following values are set by default and can be omitted 
                cart:
                    # service ID of a cart factory which creates individual carts at runtime                    
                    factory_id: Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartFactory
                    
                    # options passed to cart factory, e.g. the cart class (available options vary by factory implementation)
                    factory_options:
                        cart_class_name: Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart
                        guest_cart_class_name: Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart
                        cart_readonly_mode: deactivated            
        
            default:   
                price_calculator:
                    # list price modificators for cart, e.g. for shipping-cost, special discounts, ...
                    # key is name of modificator
                    modificators:
                        shipping:
                            class: Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Shipping
                            # configuration options for price modificator
                            options:
                                charge: "5.90"

            # additional checkout tenant for cart manager
            #  - active tenant is set at \Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface::setCurrentCheckoutTenant()
            noShipping: ~ # inherits from _defaults
```

Following elements are configured: 

* **Cart manager service ID**: The cart manager is the basic entry point for working with carts. It is 
  responsible for all interactions with different carts and provides functionality as creating carts, 
  adding/removing products and also creates the corresponding price calculator. 
* **Cart factory service ID**: builds carts when needed and can be configured with cart class name and further options varying
  by factory implementation
  * Factory option `cart_readonly_mode`, possible values are:
     * `deactivated`: Cart is never read only (will be default value with Pimcore 7), details see also [Payment Integration](./13_Checkout_Manager/07_Integrating_Payment.md).
     * `strict` (default value, deprecated, will be removed in Pimcore 7): Cart is read only as soon as payment is pending.  
* **Price calculator factory service ID + options and modificators**: The price calculator is a framework for calculation
  and modification (shipping costs, discounts, ...) of prices on cart level. Each modification is implemented in a 
  [`CartPriceModificatorInterface` class](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/CartManager/CartPriceModificator/CartPriceModificatorInterface.php). 
  See [Shipping](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/CartManager/CartPriceModificator/Shipping.php)
  or [Discount](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/CartManager/CartPriceModificator/Discount.php)
  for examples.




## Available Cart Implementations

Following cart implementations are shipped with Pimcore core and can be configured in the `factory_options` section of 
the cart manager configuration: 

* **Session-Cart** (class name `Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart`): This cart implementation 
stores all cart information in the **session** of the user. If the session is cleared, also the carts are deleted. 
Use this implementation when no user login is available and storing carts in the database has no benefit.   

* **Database-Cart** (class name `Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart`): This cart implementation
stores all cart information in the **database**. In this case, it is important that the currently logged in user is set 
to the [E-Commerce Framework Environment](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/EnvironmentInterface.php)
with the code snippet in the box below. 
Use this implementation when user logins are available and the carts should be persisted beyond session lifetime. 

> Note: if you are using the database cart, don't forget to set the currently logged in user to the environment like 
> follows: `Factory::getInstance()->getEnvironment()->setCurrentUserId(YOUR_USER_ID)` 

## Checkout Tenants for Carts
The E-Commerce Framework has the concept of so called Checkout Tenants which allow different cart manager and 
checkout manager configurations based on a currently active checkout tenant.
 
The current checkout tenant is set in the framework environment as follows: 

```php
<?php
$environment = Factory::getInstance()->getEnvironment();
$environment->setCurrentCheckoutTenant('default');
$environment->save();

$environment->setCurrentCheckoutTenant('noShipping');
$environment->save();
```

Once set, the cart manager uses all specific settings of the currently active checkout tenant which are configured
in the configuration (identified by tenant name).

See also [Demo](https://github.com/pimcore/demo/blob/1.6/app/config/ecommerce/base-ecommerce.yml#L197) for some examples.  
