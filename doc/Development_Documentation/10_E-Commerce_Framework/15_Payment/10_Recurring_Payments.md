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

#### Controller Action

##### CheckoutController.php

```php
  public function payAction(Request $request)
    {
        // ...
        
        $checkoutManager = $this->checkoutService->getCheckoutManager();

        $cart = $this->getCheckoutService()->getCart();
        $checkoutManager->startOrderPayment();

        $paymentMethod = $request->get("payment-method");
        $isRecurrinPaymentRequest = $request->get("payment-recurring");

        $targetOrder = Factory::getInstance()->getOrderManager()->getOrderFromCart($cart);
        $customer = $targetOrder->getCustomer();

        // TODO: Implement getOrderForRecurringPayment($paymentMethod) by considering 
        // payment method specific criterias.
        $sourceOrder = $customer->getOrderForRecurringPayment($paymentMethod);

        /* Recurring Payment */
        if ($isRecurrinPaymentRequest && $paymentMethod && $sourceOrder) {

            /** @var $targetPaymentInfo \Pimcore\Model\Object\Fieldcollection\Data\PaymentInfo */
            $targetPaymentInfo = $checkoutManager->startOrderPayment();

            $sourceOrderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($sourceOrder);
            $targetOrderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($targetOrder);

            /** @var $paymentProvider \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\QPay */
            $paymentProvider = $sourceOrderAgent->getPaymentProvider();

            // TODO: Implement getSuccessPaymentInfo() to get the payment information fieldcollection 
            // from the source order which contains a successfully performed payment.
            $sourcePaymentInfo = $sourceOrder->getSuccessPaymentInfo();

            $targetOrderAgent->setPaymentProvider($paymentProvider);
            $price = new Price(
                Decimal::create($targetOrder->getTotalPrice(), 2),
                $sourceOrderAgent->getCurrency()
            );

            // execute recurPayment operation
            $paymentStatus = $paymentProvider->executeDebit(
                $price,
                $sourcePaymentInfo->getInternalPaymentId(),
                $targetPaymentInfo->getInternalPaymentId()
            );

            if ($paymentStatus->getStatus() == IStatus::STATUS_CLEARED) {

                // Save some additional data to the target order object to be able to
                // determine the source order on which the recurring payment was executed.
                $targetPaymentInfo->setProvider_qpay_sourceOrder($sourceOrder);
                $targetPaymentInfo->setProvider_qpay_paymentType($sourcePaymentInfo->getProvider_qpay_paymentType());
                $targetPaymentInfo->setProvider_qpay_paymentState($paymentStatus->getStatus());
                $targetPaymentInfo->setMessage($paymentStatus->getMessage());

                // Save credit card expiry if available
                if ($paymentMethod == PaymentMethode::CCARD || $paymentMethod == PaymentMethode::MASTERPASS) {
                    try {
                        $data = json_decode($sourcePaymentInfo->getProviderData(), true);
                        $expiry = $data["qpay_response"]["expiry"];
                        $expiry = explode("/", $expiry);
                        $expiryDate = Carbon::createFromFormat("Y-m", "{$expiry[1]}-{$expiry[0]}");
                        $expiryDate = $expiryDate->endOfMonth();

                        $targetPaymentInfo->setProvider_qpay_expiry($expiryDate);
                    } catch (\Throwable $exception) {
                    }
                }

                $targetOrder->save();

                // Commit order 
                $checkoutManager->commitOrderPayment($paymentStatus);

                // Redirect to success page
                return $this->redirect("/my/payment/success/url");

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
        $order = $user->getOrderForRecurringPayment($paymentMethod));
            
        // TODO: Implement getRecurringPaymentData(): extract the relevant data for
        // the used payment-method out of the return value of the previously explained  
        // getSuccessPaymentInfo().
        
        if ($recurringPaymentInfo = $order->getRecurringPaymentData()) {
        
            switch ($paymentMethod) {

                // Show recurring payment <input type="radio"> and an according label 
                // displaying information about the payment here. 
                // I.e. a masked string with the last for digits of the creditcard number
                
                case "SEPA_DD":
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