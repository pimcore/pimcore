# Recurring Payment
  
Recurring Payment is performed via a [transaction-based operation](https://guides.wirecard.at/back-end_operations:transaction-based:start) offered by [Wirecard Checkout Page](https://guides.wirecard.at/wcp:start) and [Wirecard Checkout Seamless](https://guides.wirecard.at/wcs:start). The *recurPayment* operation is used to create a new order and a new payment attempt by reusing the payment information available in a previous order, the so-called source order.

It can for example be used to implement sequential payments like hiring or leasing agreements, or also to perform a one time payment for an authorized user which already committed at least one previous order which was committed using a [valid payment method](https://guides.wirecard.at/back-end_operations:transaction-based:table). 

For more details concerning the recurPayment operation also have a look at the official [Wirecard Documentation](https://guides.wirecard.at/back-end_operations:transaction-based:recurpayment).

### Requirements, Common Pitfalls & Additional Information
- For backend operations like *recurPayment* a toolkit-password is required on top of the standard Wirecard credentials. 
- Source orders are valid for 400 days.
- There are payment method specific specifications, like for example passing a parameter `transactionIdentifier` with the value `INITIAL` for source orders performed via *SEPA Direct Debit*.
- Every credit card based order payment response contains a parameter `anonymousPan`, which consists of the last 4 digits of the credit card numbe. This is useful, if you you want to let the user choose which card he wants to use for the recurring payment. For some credit cards also `maskedPan` and  `expiry` are available. The `maskedPan` includes a masked string of the whole creditcard-number. The expiry is useful to validate if the source order can still be used for a recurPayment operation.

## Best Practice
### Bad Way
![Recurring Payment Bad](../../img/recurring-payment-bad.png) 
### Good Way
![Recurring Payment Good](../../img/recurring-payment-good.png) 

## Implementation

The following code will briefly show how to execute a recurPayment operation for a one time payment in the checkout process.

#### CheckoutController.php

```php
    public function paymentAction(Request $request)
    {
        $factory = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance();

        $checkoutManager = $factory->getCheckoutManager($this->cart);
        $user = $this->getUser();

        if ($request->getMethod() == "POST") {

            $paymentMethod = $request->get("payment-method");
            $paymentMethodRecurring = $request->get("payment-recurring");

            $sourceOrderId = $request->get("source-order-{$paymentMethod}");

            /* Recurring Payment */
            if ($user && $sourceOrderId && $paymentMethod && ($paymentMethodRecurring == $paymentMethod)) {
                $sourceOrder = \Pimcore\Model\DataObject\OnlineShopOrder::getById($sourceOrderId);

                try {
                    $targetOrder = $checkoutManager->startAndCommitRecurringOrderPayment($sourceOrder);
                    return $this->redirect("/my/payment/success/url");
                } catch (\Exception $exception) {
                    // TODO: show warning
                }

            }

            // Render default payment frame
            return $this->render("/my/payment/frame/view");

        }

        $paymentMethods = ["SEPA-DD", "CCARD"];

        if ($user) {
            $sourceOrders = [];

            foreach ($paymentMethods as $paymentMethod) {
                $orderManager = $factory->getOrderManager();
                $sourceOrder = $orderManager->getRecurringPaymentSourceOrder(
                    $user->getId(), $checkoutManager->getPayment(), $paymentMethod);

                $sourceOrders[$paymentMethod] = $sourceOrder;
            }
            $this->view->sourceOrders = $sourceOrders;
        }

        $this->view->paymentMethods = $paymentMethods;

        // payment.html.php rendered

    }
```

#### payment.html.php

```php
<form action="/route/to/pay-action" method="post">

    <?php
    if ($this->security()->isGranted("IS_AUTHENTICATED_FULLY")) {

        foreach ($this->paymentMethods as $paymentMethod) {
            $sourceOrder = $this->sourceOrders[$paymentMethod];
            $sourceOrderId =  $sourceOrder ? $sourceOrder->getId() : ""
            ?>
            
            <input hidden
                name="source-order-<?= $paymentMethod ?>" 
                value="<?= $sourceOrderId ?>">
                
            <input name="payment-method" value="<?= $paymentMethod ?>" type="radio">
    
            <?php
            if ($paymentProvider = $sourceOrder->getPaymentProvider()->getPaymentProviderQpay()) {

                switch ($paymentProvider->getAuth_paymentType()) {
                    case "SEPA-DD": ?>
                        <p>
                            <?= $paymentProvider->getAuth_bankAccountOwner() ?><br>
                            <?= $paymentProvider->getAuth_bankAccountIBAN() ?>
                            <input name="payment-recurring" value="<?= $paymentMethod ?>" type="checkbox">
                        </p>
                        <?php
                        break;
                    case "CCARD": ?>
                        <p>
                            <?= $paymentProvider->getAuth_maskedPan() ?><br>
                            <?= $paymentProvider->getAuth_expiry() ?>
                            <input name="payment-recurring" value="<?= $paymentMethod ?>" type="checkbox">
                        </p>
                        <?php
                        break;
                        // ...
                }
            }
        }
    }
        ?>

        <button type="submit">Pay now</button>
    
    </form>


```

#### ecommerce.yml

```yaml
payment_manager:
    providers:
      qpay:
       
       # ...

        profiles:
          sandbox:
            secret: 'CHCSH7UGHVVX2P7EHDHSY4T2S4CGYK4QBE4M5YUUG2ND5BEZWNRZW5EJYVJQ'
            customer: 'D200411'
            toolkit_password: '2g4f9q2m' # neccesary for recurPayment operation
            optional_payment_properties:
              - paymentType
              - financialInstitution # restrict for Visa, Mastercard etc.
              - transactionIdentifier # neccesary for recurPayment based on SEPA DIRECT DEBIT
            recurring_payment_enabled: true
```