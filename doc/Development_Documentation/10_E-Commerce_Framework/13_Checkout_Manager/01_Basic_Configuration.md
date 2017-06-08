# Basic Configuration

The configuration takes place in the [EcommerceFrameworkConfig.php](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Resources/install/EcommerceFrameworkConfig_sample.php#L83-L83)
```php
/* general settings for checkout manager */
'checkoutmanager' => [
    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CheckoutManager\\CheckoutManager',
    'config' => [
        /* define different checkout steps which need to be committed before commit of order is possible */
        'steps' => [
            'deliveryaddress' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CheckoutManager\\DeliveryAddress'
            ],
            /* example step from the Ecommerce demo, which extends AbstractStep */
            /*"confirm" => [
                "class" => "\\AppBundle\\Ecommerce\\Checkout\\Confirm"
            ]*/
        ],
        /* optional
             -> define payment provider which should be used for payment.
             -> payment providers are defined in payment manager section. */
        'payment' => [
            'provider' => 'qpay'
        ],
        /* define used commit order processor */
        'commitorderprocessor' => [
            'class' => '\\AppBundle\\Ecommerce\\Order\\Processor'
        ],
        /* settings for confirmation mail sent to customer after order is finished.
             also could be defined defined directly in commit order processor (e.g. when different languages are necessary)
         */
        'mails' => [
            'confirmation' => '/en/emails/order-confirmation'
        ],
        /* special configuration for specific checkout tenants */
        'tenants' => [
            'paypal' => [
                'payment' => [
                    'provider' => 'paypal'
                ]
            ],
            'datatrans' => [
                'payment' => [
                    'provider' => 'datatrans'
                ]
            ]
        ]
    ]
],
```

Following elements are configured: 
* **Implementation of the checkout manager**: The Checkout Manager is a central player of the checkout process. It checks 
  the state of single checkout steps, is responsible for the payment integration and also calls the commit order 
  processor in the end. 
* [**Checkout steps and their implementation**](./03_Checkout_Steps.md): Each checkout step (e.g. Delivery address, 
  delivery date, ...) needs a concrete checkout step implementation. The implementation is responsible for storing 
  and validating the necessary data, is project dependent and has to be implemented for each project. 
* [**Implementation of the commit order processor**](./05_Committing_Orders.md): When finalization of the order is 
   done by the commit order processor. This is the places, where custom ERP integrations and other project dependent 
   order finishing stuff should be placed. 
* **Additional stuff like**: 
   * Mail configuration
   * [Payment Implementation](./07_Integrating_Payment.md)
   * [Checkout Tenants](./09_Checkout_Tenants.md)
