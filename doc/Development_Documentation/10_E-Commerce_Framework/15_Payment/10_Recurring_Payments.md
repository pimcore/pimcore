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
public function payAction(Request $request)
    {
        // ...

        $factory = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance();
        
        $checkoutManager = $factory->getCheckoutManager();

        $paymentMethod = $request->get("payment-method");
        $isRecurrinPaymentRequest = $request->get("payment-recurring");

        /* Recurring Payment */
        if ($isRecurrinPaymentRequest && $paymentMethod) {
            
            $orderManager = $factory->getOrderManager();
            $sourceOrder = $orderManager->getOrderForRecurringPayment($this->getUser(), $paymentMethod);
            
            try {
                $targetOrder = $checkoutManager->startAndCommitRecurringOrderPayment($sourceOrder);
                return $this->redirect("/my/payment/success/url");
            } catch (\Exception $exception) {
                // TODO
            }

        }

        // Render default payment frame
        return $this->render("/my/payment/frame/view");
    }
```

#### paymentStep.html.php

```php

<?php foreach ($availablePaymentMethods as $paymentMethod) {

    // Show payment method <input> here

    if ($this->security()->isGranted("IS_AUTHENTICATED_FULLY")) {
        $order = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()
            ->getOrderForRecurringPayment($this->getUser(), $paymentMethod));
            
        // TODO: Implement getRecurringPaymentData(): get the relevant for showing 
        // the user to help him choose the payment type.
        
        if ($recurringPaymentInfo = $order->getRecurringPaymentData()) {
        
            switch ($paymentMethod) {

                // Show recurring payment <input type="radio"> and an according label 
                // displaying information about the payment here. 
                // I.e. a masked string with the last for digits of the creditcard number
                
                case "SEPA-DD":
                // ...
                case "CCARD":
                // ...
            }
        }
    }
}

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
```