# Recurring Payment
  
Pimcore currently supports recurring payment for the payment providers Wirecard Checkout Page (_recurPayment_) and Datatrans (_Alias_).
It is performed via backend operations (server-to-server) which are used to create a new order and a new payment attempt by reusing the payment information available in a previous order, the so-called source order.

Recurring payment can for example be used to implement sequential payments like hiring or leasing agreements, or also to perform a one time payment for an authorized user which already committed at least one previous order successfully. 

### Additional Information
- [Wirecard Documentation](https://guides.wirecard.at/back-end_operations:transaction-based:recurpayment)
- [Datatrans Documentation](https://www.datatrans.ch/alias-tokenization/using-the-alias)


### Wirecard: Requirements, Common Pitfalls & Additional Information
- For backend operations like *recurPayment* a toolkit-password is required on top of the standard Wirecard credentials. 
- Source orders are valid for 400 days.
- There are payment method specific specifications, like for example passing a parameter `transactionIdentifier` with the value `INITIAL` for source orders performed via *SEPA Direct Debit*.
- Every credit card based order payment response contains a parameter `anonymousPan`, which consists of the last 4 digits of the credit card number. This is useful, if you you want to let the user choose which card he wants to use for the recurring payment. For some credit cards also `maskedPan` and  `expiry` are available. The `maskedPan` includes a masked string of the whole creditcard-number. The expiry is useful to validate if the source order can still be used for a _recurPayment_ operation.

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

    $paymentMethods = ["SEPA-DD", "CCARD"]; // supported payment methods

    if ($user) {
        $sourceOrders = [];

        foreach ($paymentMethods as $paymentMethod) {
            $orderManager = $factory->getOrderManager();
            $sourceOrder = $orderManager->getRecurringPaymentSourceOrder($user->getId(), $checkoutManager->getPayment(), $paymentMethod);

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
?>
<form method="post" action="<?= $this->pimcoreUrl(array('action' => 'confirm'), 'checkout', true) ?>">

    <?php if (!empty($this->sourceOrders)): ?>

        <h4><?= $this->t("checkout.use-recurring-payment"); ?></h4>

        <?php
        foreach ($this->paymentMethods as $paymentMethod) :
            /* @var \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $sourceOrder */
            $sourceOrder = $this->sourceOrders[$paymentMethod];
            $sourceOrderId = $sourceOrder ? $sourceOrder->getId() : "";

            if (!$sourceOrder) {
                continue;
            }

            if ($paymentProvider = $paymentProviderBrick->getPaymentProviderQpay()) :
                $currentPaymentMethod = $paymentProvider->getAuth_paymentType();
                ?>
                <p>
                    <input name="recurring-payment" value="<?= $sourceOrderId ?>" type="radio">
                    <strong><?= $currentPaymentMethod ?></strong>

                    <?php
                    switch ($currentPaymentMethod) {
                        case "SEPA-DD":
                            echo $paymentProvider->getAuth_bankAccountOwner() . " " . $paymentProvider->getAuth_bankAccountIBAN();
                            break;
                        case "CCARD":
                            echo $paymentProvider->getAuth_maskedPan() . " " . $paymentProvider->getAuth_expiry();
                            break;
                    }
                    ?>
                </p>
                <?
            endif;
        endforeach;
        ?>
        <hr>
    <?php endif; ?>

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
