# Recurring Payment
  
Pimcore currently supports recurring payment for the payment provider Datatrans (_Alias_).
It is performed via backend operations (server-to-server) which are used to create a new order and a new payment attempt by reusing the payment information available in a previous order, the so-called source order.

Recurring payment can for example be used to implement sequential payments like hiring or leasing agreements, or also to perform a one time payment for an authorized user which already committed at least one previous order successfully. 

### Additional Information
- [Datatrans Documentation](https://www.datatrans.ch/alias-tokenization/using-the-alias)

## Best Practice
### Bad Way
![Recurring Payment Bad](../../img/recurring-payment-bad.png) 
### Good Way
![Recurring Payment Good](../../img/recurring-payment-good.png) 

## Implementation
The following code will briefly show how to perform a one time payment inside the checkout process for _Wirecard Checkout Page_ (former known as Qpay).

#### CheckoutController.php

```php
public function paymentAction(Request $request)
{
    $factory = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance();

    $checkoutManager = $factory->getCheckoutManager($this->cart);
    $user = $this->getUser();

    // open payment or submit recurring payment
    if ($request->getMethod() == 'POST') {
        if($sourceOrderId = $request->get("recurring-payment")){

            /* Recurring Payment */
            if ($user && $sourceOrderId) {
                $sourceOrder = \Pimcore\Model\DataObject\OnlineShopOrder::getById($sourceOrderId);

                try {
                    $targetOrder = $checkoutManager->startAndCommitRecurringOrderPayment($sourceOrder, $user->getId());
                    return $this->redirect($this->generateUrl('checkout', ['action' => 'completed']));
                } catch (\Exception $exception) {
                    // show warning
                }
            }
        }
    }

    $templateParams = [];
    $paymentMethods = ["SEPA-DD", "CCARD"]; // supported payment methods

    if ($user) {
        $sourceOrders = [];

        foreach ($paymentMethods as $paymentMethod) {
            $orderManager = $factory->getOrderManager();
            $sourceOrder = $orderManager->getRecurringPaymentSourceOrder($user->getId(), $checkoutManager->getPayment(), $paymentMethod);

            $sourceOrders[$paymentMethod] = $sourceOrder;
        }
        
        $templateParams['sourceOrders'] = $sourceOrders;
    }

    $templateParams['paymentMethods'] = $paymentMethods;

    return $this->render('checkout/payment.html.twig', $templateParams);
}
```

#### payment.html.php

```twig
?>
<form method="post" action="{{ pimcore_url({action: 'confirm'}, 'checkout', true) }}">

    {% if sourceOrders is not empty %}

        <h4>{{ 'checkout.use-recurring-payment'|trans }}</h4>

        {% for paymentMethod in paymentMethods %}
            {% if sourceOrders[paymentMethod] is defined %}
                {% set sourceOrder = sourceOrders[paymentMethod] %}
                {% set paymentProvider = sourceOrder.paymentProvider.paymentProviderQpay %}
                {% if paymentProvider %}
                    {% currentPaymentMethod = paymentProvider.Auth_paymentType %}
                     <p>
                        <input name="recurring-payment" value="{{ sourceOrder.id }}" type="radio">
                        <strong>{{ currentPaymentMethod }}</strong>
                        
                        {% if currentPaymentMethod is same as('SEPA-DD') %}
                            {{ paymentProvider.Auth_bankAccountOwner }} {{ paymentProvider.Auth_bankAccountIBAN }}
                        {% elseif currentPaymentMethod is same as('CCARD') %} 
                            {{ paymentProvider.Auth_maskedPan }} {{ paymentProvider.Auth_expiry }}
                        {% endif %}
                    </p>
                {% endif %}
            {% endif %}
        {% endfor %} 
        
        <hr>
    {% endif %}

</form>
```

#### ecommerce.yml

```yaml
payment_manager:
    providers:
      qpay:
        profiles:
          sandbox:
            secret: 'CHCSH7UGHVVX2P7EHDHSY4T2S4CGYK4QBE4M5YUUG2ND5BEZWNRZW5EJYVJQ'
            customer: 'D200411'
            toolkit_password: 'XXXXXXX' # necessary for recurPayment operation
            optional_payment_properties:
              - paymentType
              - transactionIdentifier # necessary for recurPayment based on SEPA DIRECT DEBIT
            recurring_payment_enabled: true # enable recurring payment
```
