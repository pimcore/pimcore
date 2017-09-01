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
Each cart has a `CartPriceCalculator` (configuration see below) who is responsible for calculating total sums of the 
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
                        guest_cart_class_name: Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart            
        
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
            #  - active tenant is set at \Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment::setCurrentCheckoutTenant()
            noShipping: ~ # inherits from _defaults
```

Following elements are configured: 

* **Cart manager service ID**: The cart manager is the basic entry point for working with carts. It is 
  responsible for all interactions with different carts and provides functionality as creating carts, 
  adding/removing products and also creates the corresponding price calculator. 
* **Cart factory service ID**: builds carts when needed and can be configured with cart class name and further options varying
  by factory implementation
* **Price calculator factory service ID + options and modificators**: The price calculator is a framework for calculation
  and modification (shipping costs, discounts, ...) of prices on cart level. Each modification is implemented in a 
  [`ICartPriceModificator` class](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/CartManager/CartPriceModificator/ICartPriceModificator.php). 
  See [Shipping](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/CartManager/CartPriceModificator/Shipping.php)
  or [Discount](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/CartManager/CartPriceModificator/Discount.php)
  for examples. This should be self speaking. 

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

See also [E-Commerce Demo](https://github.com/pimcore/demo-ecommerce/blob/master/src/AppBundle/Resources/config/pimcore/ecommerce/ecommerce-config.yml) for some examples.  
