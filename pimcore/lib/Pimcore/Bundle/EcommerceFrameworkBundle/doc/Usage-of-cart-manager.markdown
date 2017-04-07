## 1 - Cart Manager configuration

> Basically, every user specific product collection is a cart. No matter how it is called (cart, wish list, compare list, ...), all these product collections need the same base functionality. Therefore all different product collections are carts with a specific name. 

The configuration takes place in the EcommerceFrameworkConfig.php
```php
 /* general settings for cart manager */
        "cartmanager" => [
            "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\MultiCartManager",
            "config" => [
                /* default cart implementation that is used */
                "cart" => [
                    "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\Cart",
                    "guest" => [
                        "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\SessionCart"
                    ]
                ],
                /* default price calculator for cart */
                "pricecalculator" => [
                    "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceCalculator",
                    "config" => [
                        /* price modificators for cart, e.g. for shipping-cost, special discounts, ... */
                        "modificators" => [
                            "shipping" => [
                                "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceModificator\\Shipping",
                                "config" => [
                                    "charge" => "5.90"
                                ]
                            ]
                        ]
                    ]
                ],
                /*  special configuration for specific checkout tenants
                    - for not specified elements the default configuration is used as fallback
                    - active tenant is set at \Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment::setCurrentCheckoutTenant() */
                "tenants" => [
                    "noShipping" => [
                        "pricecalculator" => [
                            "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceCalculator",
                            "config" => [
                                "modificators" => "\n                                "
                            ]
                        ]
                    ]
                    /* you also can use external files for additional configuration */
                    /* "expensiveShipping" =>[ "file" => "ecommerce\\cartmanager-expensiveShipping.php ] */
                ],
                
            ]
        ],
```

Following elements are configured: 
* **Implementation of the cart manager**: The cart manager is the basic entry point for working with carts. It is responsible for all interactions with different carts and provides functionality as creating carts, adding/removing products and also creates the corresponding price calculator. 
* **Implementation of the cart**
* **Implementation of the price calculator**: The price calculator is a framework for calculation and modification (shipping costs, discounts, ...) of prices on cart level. Each modification is implemented in a price modificator class. 


## 2 - Working with carts
#### Creating carts
```php
<?php
$manager = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getCartManager();
$cartId = $manager->createCart(array('name' => $cartName));
$cart = $manager->getCart( $cartId );
```

#### Adding and removing products
```php
<?php
$manager = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getCartManager();
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
$manager = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getCartManager();
$cart = $manager->getCartByName($cartName);

if (count($cart->getItems()) > 0) {
  foreach ($cart->getItems() as $item) {
    $product = $item->getProduct();
 
    //item key
    $cartItemId = $item->getItemKey();
 
    //item amount
    $amount = $item->getCount();
 
    //price info
    $priceInfo = $item->getPriceInfo();
    $price = $item->getPrice(); 
  }
}
```



## 3 - Price calculation in carts
```php
<?php
// delivers sum without any price modifications
$subTotal = $cart->getPriceCalculator()->getSubTotal();
 
// iterates through all price modifications
foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
    // $name is a label for modification
    // $modification is a \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IModificatedPrice
}
 
// delivers sum including all price modifications
$grandTotal = $cart->getPriceCalculator()->getGrandTotal();
```

## 4 - Checkout tenants for carts
The e-commerce framework has the concept of checkout tenants which allow different cart manager and checkout manager configurations based on a currently active checkout tenant. 
The current checkout tenant is set in the framework environment as follows. Once set, the cart manager uses all specific settings of the currently active checkout tenant. 

```php
<?php
$environment = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getEnvironment();
$environment->setCurrentCheckoutTenant('default');
$environment->save();

$environment->setCurrentCheckoutTenant('noShipping');
$environment->save();
```
