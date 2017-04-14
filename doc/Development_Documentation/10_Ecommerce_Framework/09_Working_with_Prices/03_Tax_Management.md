# Tax Management
Within the Price Systems there is a Tax Management component to deal with all sorts of taxes.
So it is possible to calculate and print out taxes as needed.

The Tax Management is quite transparent and should not affect the system very deeply. As a result - if not necessary 
(e.g. because you have a B2B system, etc.) - you don't need to deal with taxes in E-Commerce Framework at all.
Also, if you have your very own tax implementation, this should be perfectly possible and you should need to not deal with
the Tax Management in the E-Commerce Framework at all. Just use all the default values and every thing should be fine.


## Components of Tax Management
If you need to deal with taxes following components are important to know of: 

### Tax Configuration with `OnlineShopTaxClass`
The configuration of taxes is done with `OnlineShopTaxClass` Pimcore objects within in the Pimcore Backend UI. The actual 
tax calculation is always based on the configuration of such a tax class. So, tax configurations for different countries 
and product groups can be established. The tax calculation is always based on one tax class, a combination of tax classes
is not possible.
The configuration concludes a TaxEntryCombinationMode (`combine` and `one after another`) and one or more tax entries
with a name and a tax rate in percent.

![tax classes](../../img/tax-class.png)

The selection of the correct tax class (for a certain country and a certain product group) is done by the Price System
(see next section).


### Tax Class Selection with Price Systems
The Price System decides for each product and environment which tax class to take for tax calculation. Therefore following
 two methods need to be implemented:
- `getTaxClassForProduct`: Should return tax class for given product. The default implementation returns a generic tax 
  class based on the Website Settings or - if not set - an empty tax class.
- `getTaxClassForPriceModification`: See later for more information.

The logic behind the tax class selection can be of any complexity. It can be based on a simple Website Setting (for one tax
class fits all), or based on a complex matrix with product group, sending country, delivering country and several 
other aspects.


### Tax Calculation with `TaxCalculationService`
The actual tax calculation is done in the `TaxCalculationService::updateTaxes(IPrice $price, $calculationMode = self::CALCULATION_FROM_NET)` 
method. It updates taxes in given price object by using its tax entries, tax combination mode and net or gross amount 
based on the given `$calculationMode`.

The calculation is done by the framework in two places:
 - By Price Systems when creating the `PriceInfo` objects
 - By `IPrice` objects when `setAmount`, `setGrossAmount` or `setNetAmount` is called and `$recalc` is set to true.


### The `IPrice` object
Every price in the E-Commerce Framework comes down to an `IPrice` object. Therefore also all calculated taxes and all
necessary information for calculating taxes is stored in `IPrice` objects. Following methods are important:
- `getTaxEntries()`, `setTaxEntries($taxEntries)`: Sets and gets tax entries for price. Each price can have one or more 
   tax entries based on the tax laws. Each of these tax entries contain percent rate and calculated amount. 
   The order of the tax entries is important.
- `getTaxEntryCombinationMode()`, `setTaxEntryCombinationMode($taxEntryCombinationMode)`: Sets and gets the tax entry 
   combination mode which defines how tax calculation is done based on the tax entries:
   - `TaxEntry::CALCULATION_MODE_COMBINE`: Sum up all tax rates and calculate tax amount afterwards.
   - `TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER`: For each tax rate calculate tax amount, add it to total price and 
      then calculate tax amount for next tax rate based on new total sum.
   - `TaxEntry::CALCULATION_MODE_FIXED`: Amounts and percent rates are fixed and cannot be (re)calculated based on 
      information within the `IPrice` object. This mode is needed for `subTotal` and `grandTotal` in `CartPriceCalculator`.   
- `getGrossAmount()`, `setGrossAmount($grossAmount, $recalc = false)`: Sets and gets gross amount of price. If `$recalc` 
   is set to `true`, corresponding net price is calculated based on tax entries and tax entry combination mode.
- `getNetAmount()`, `setNetAmount($netAmount, $recalc = false)`: Sets and gets net amount of price. If `$recalc` is set 
   to `true`, corresponding gross price is calculated based on tax entries and tax entry combination mode.
- `getAmount()`, `setAmount($amount, $priceMode = self::PRICE_MODE_GROSS, $recalc = false)`: Gets the gross amount by 
   default and sets the gross or net amount based on the params.


## Special Aspects
### Tax Calculation in Pricing Rules
E-Commerce Framework Pricing Rules always modify the gross price of the product and recalculate the net price based on the
tax entries of the product price. So specified discount amounts on product level need to be configured as gross discounts.

Since cart discounts are implemented as `CartPriceModificators` the next point is important for them. It might be necessary
to extend the default implementation of the cart discounts for the correct selection of the tax class for discounts. The
default implementation uses the `getTaxClassForPriceModification` method of the Price System called `default` for tax class
selection.

### Tax Calculation in `CartPriceModificators`
The `CartPriceModificators` themselves can decide if the modification should be done as net or gross amount since the 
interface `IModificatedPrice` extends `IPrice` and therefore all tax related information as explained above.
The method `getTaxClassForPriceModification` in Price Systems can be used to delegate the tax class selection to Price 
Systems.


## Putting the Pieces Together
For setting up the tax management, following steps are necessary.

### 1) Defining Tax Classes
Create and configure all the `OnlineShopTaxClass` objects within Pimcore.

### 2) Configuring Price System
Setup the correct price systems and implement their methods `getTaxClassForProduct` and `getTaxClassForPriceModification`.
It might also be necessary to have a look at the `CartPriceModificator` for cart discounts and make sure that it selects the
correct tax class.

### 3) Printing Taxes
To print taxes into the frontend use following samples:

#### Product Detail Page
```php
<?php
    $price = $this->product->getOSPrice();
?>
<strong><?= $price ?></strong>

<div class="tax">
    <p><strong><?= $this->translate("shop.detail.included_tax") ?></strong></p>
    <ul>
        <?php foreach($price->getTaxEntries() as $entry) { ?>
            <?php
                $amountAsCurrency = $price->getCurrency()->toCurrency($entry->getAmount());
            ?>
            <li><?= $entry->getEntry()->getName() ?>: <?=  $entry->getPercent() ?>% (<?= $amountAsCurrency ?>)</li>
        <?php } ?>
    </ul>
</div>
```

#### Cart
```php
<?php $grandTotal = $cart->getPriceCalculator()->getGrandTotal() ?>

<?php if($grandTotal->getTaxEntries()) { ?>

    <tr>
        <td><?= $this->translate("cart.taxes") ?></td>
        <td>
            <?php foreach($grandTotal->getTaxEntries() as $taxEntry) { ?>
                <?php
                    $amountAsCurrency = $grandTotal->getCurrency()->toCurrency($taxEntry->getAmount());
                ?>
                <?= $taxEntry->getEntry()->getName() ?>: <?= $taxEntry->getPercent() ?>% (<?= $amountAsCurrency ?>)<br/>
            <?php } ?>
        </td>
    </tr>

<?php } ?>
```

#### Order Confirmation Mail
```php

<?php if($order->getTaxInfo()) { ?>

    <tr>
        <td><?= $this->translate("cart.taxes") ?></td>
        <td>
            <?php foreach($order->getTaxInfo() as $taxEntry) { ?>
                <?= $taxEntry[0] ?>: <?= $taxEntry[1] ?> (<?=  $currency->toCurrency($taxEntry[2]) ?>)<br/>
            <?php } ?>
        </td>
    </tr>

<?php } ?>

```

### 4) Taxes in `OnlineShopOrders`
Make sure that all tax related attributes are available in `OnlineShopOrder` and `OnlineShopOrderItem`. Then the 
E-Ccommerce Framework populates all needed values automatically and therefore stores the tax information in the 
order objects.
