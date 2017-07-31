# Basic Configuration

The configuration takes place in the `pimcore_ecommerce_framework.checkout_manager` configuration section and is [tenant aware](../04_Configuration/README.md).

```yaml
pimcore_ecommerce_config:
    checkout_manager:
        tenants:
            _defaults:
                # the following two values are default values an can be omitted
                # service ID of a checkout manager factory which builds cart specific checkout managers
                factory_id: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerFactory
                
                # options passed to the factory - available options vary by implementation
                factory_options:
                    class: \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManager

                # commit order processor
                commit_order_processor:
                    # order processor service ID
                    id: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor
                    
                    # options passed to the commit order processor - available options vary by implementation
                    options:
                        confirmation_mail: /en/emails/order-confirmation

                # define different checkout steps which need to be committed before commit of order is possible
                steps:
                    deliveryaddress:
                        class: \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\DeliveryAddress
                        
                    # example step from the Ecommerce demo, which extends AbstractStep
                    confirm:
                        class: \AppBundle\Ecommerce\Checkout\Confirm

            default:
                # define payment provider which should be used for payment.
                # payment providers are defined in payment_manager section.
                payment:
                    provider: qpay

            paypal:
                payment:
                    provider: paypal

```

Following elements are configured: 
* **Service ID and options of the checkout manager factory**: The Checkout Manager is a central player of the checkout process.
  It checks the state of single checkout steps, is responsible for the payment integration and also calls the commit order 
  processor in the end. As the a checkout manager is specific to a cart instance, checkout manager factory takes care of
  creating checkout managers on demand. 
* [**Checkout steps and their implementation**](./03_Checkout_Steps.md): Each checkout step (e.g. Delivery address, 
  delivery date, ...) needs a concrete checkout step implementation. The implementation is responsible for storing 
  and validating the necessary data, is project dependent and has to be implemented for each project. 
* [**Service ID and options of the commit order processor**](./05_Committing_Orders.md): When finalization of the order is 
   done by the commit order processor. This is the places, where custom ERP integrations and other project dependent 
   order finishing stuff should be placed. 
* **Additional stuff like**: 
   * Mail configuration
   * [Payment Implementation](./07_Integrating_Payment.md)
   * [Checkout Tenants](./09_Checkout_Tenants.md)
