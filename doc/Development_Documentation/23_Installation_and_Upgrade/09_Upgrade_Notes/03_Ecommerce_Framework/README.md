# Breaking Changes from former E-Commerce Framework Plugin 

## Configuration Changes
- Migrated `OnlineShopConfig.php` to Symfony config and Symfony Service Container. This has some major impact on how 
  services are instantiated and on some signatures. This hardly affects usage of the framework, but if you are sub classing
  and extending functionality, it will affect you. 
  For details see [Config Signature changes](./02_Ecommerce_Framework_Config_Signature_Changes.md) and
   [E-Commerce Framework Configuration Docs](../../../10_E-Commerce_Framework/04_Configuration/README.md)



- Replaced setting `defaultlocale` in Environment configuration with `defaultCurrency`

- OrderBackoffice Translations moved to AdminTranslations

- [Findlogic Export URL](../../10_E-Commerce_Framework/05_Index_Service/05_Data_Architecture_and_Indexing_Process.md) 
  changed to `/ecommerceframework/findologic-export`

- Logging now with Symfony standard logging
   - Logs into certain channels: `pimcore_ecommerce`, `pimcore_ecommerce_indexupdater`, `pimcore_ecommerce_sql`, `pimcore_ecommerce_factfinder`, `pimcore_ecommerce_es`, `pimcore_ecommerce_findologic`
   - Log settings in factfinder and findologic configuration section are ignored

## Code Changes
- Changed from Namespace `\OnlineShop\Framework\` to `\Pimcore\Bundle\EcommerceFrameworkBundle\`
   - E-Commerce Framework loads class aliases, so all old class names should work as expected
   
- Replaced `Zend_Config` with `Config` - also in certain interfaces like
   - `IPayment`
   - `OrderManager`
   - `Shipping`
   - `PaymentManager`
   - `PricingManager`
   - `TrackingManager`
  
- Replaced `Zend_Paginator` with `new Zend\Paginator()`

- Replaced `Zend_Db` with `Doctrine DAL` 
   - `\Zend_Db_Select()` becomes `\Pimcore\Db\ZendCompatibility\QueryBuilder` 
   - `\Zend_Db_Expr()` becomes `\Pimcore\Db\ZendCompatibility\Expression`   
   
 - Replaced `Zend_Date` with DateTime - also in certain interfaces like
   - `ICart`
   - `ICartItem`
   - `AbstractOrder` (still works if Pimcore is in `Zend_Date` - mode)
   - `AbstractPaymentInformation` (still works if Pimcore is in `Zend_Date` - mode)
   - `AbstractOffer` (still works if Pimcore is in `Zend_Date` - mode)
   - `OrderDateTime` Filter
   - `IDateRange` for Pricing Manager
   
- Replaced `Zend_Registry` with `Pimcore\Cache\Runtime`

- Replaced `Zend_Currency` with `Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency`
    - Methods `toCurrency($value, $options)`, `getShortName()`, `getSymbol()`, `getName()` still work as before. 
	- As `$options` `'display'` and `'position'` are supported. 

- Replaced `Zend_Form` with Symfony forms
   - all views that render payment forms have to be adapted 
   - in E-Commerce demo add following snippet
```php 
<?php if ($form instanceof \Symfony\Component\Form\FormBuilderInterface) { ?>
    <p><img src="https://www.wirecard.at/fileadmin/templates/images/wirecard-logo.png"/></p>

    <p><?= $this->translate('checkout.payment.txt') ?></p>

    <?php
        $form->remove('submitbutton');
        $form->add('submitbutton', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['attr' => ['class' => 'btn btn-primary'], 'label' => $this->translate('checkout.payment.paynow')]);
        $container = \Pimcore::getContainer();
        echo $container->get('templating.helper.form')->form($form->getForm()->createView());
    ?>

    <script type="text/javascript">
        $(document).ready(function () {
           document.getElementById('paymentForm').submit();
        });
    </script>
<?php } ?>
```
		
- Removed `Zend_View` 
   - Changed constructor of `AbstractFilterType` (and all subclasses) - no `$view` anymore.
   - `Factory::getFilterService` now has no view parameter anymore.
   - `Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\Helper::setupProductList()` - changed `$view` parameter to 
      `ViewModel $viewModel`.
   - View scripts for filters now defined in Symfony template notation, e.g. 
      `"script" => ":Shop/filters:select_category.html.php"`.
   - Fallback for old view scripts path relative to `PIMCORE_PROJECT_ROOT . "/legacy/website/views/scripts"` - but they 
      are also rendered with Symfony engine.

- Price objects (`IPrice`) now use a value object instead of floats to represent prices. If you are calculating with prices
  (e.g. in custom pricing rules), this will affect you. If you are just using Price objects and displaying prices, this 
  change should be transparent for you. For details see [Price changes page](./01_Ecommerce_Framework_Price_Objects.md).   
 
- CartPriceCalculator: property `$gradTotal` was renamed to `$grandTotal` - needs to be reflected in when sub classing 
  the default calculator.
   
- Commands namespace changed from `shop` to `ecommerce`. 

- Renamed tables:
```sql
RENAME TABLE plugin_onlineshop_cart TO ecommerceframework_cart; 
RENAME TABLE plugin_onlineshop_cartcheckoutdata TO ecommerceframework_cartcheckoutdata; 
RENAME TABLE plugin_onlineshop_cartitem TO ecommerceframework_cartitem; 
RENAME TABLE plugin_onlineshop_pricing_rule TO ecommerceframework_pricing_rule; 
RENAME TABLE plugins_onlineshop_vouchertoolkit_reservations TO ecommerceframework_vouchertoolkit_reservations;
RENAME TABLE plugins_onlineshop_vouchertoolkit_statistics TO ecommerceframework_vouchertoolkit_statistics;
RENAME TABLE plugins_onlineshop_vouchertoolkit_tokens TO ecommerceframework_vouchertoolkit_tokens;

RENAME TABLE plugin_onlineshop_productindex TO ecommerceframework_productindex; 
RENAME TABLE plugin_onlineshop_productindex_relations TO ecommerceframework_productindex_relations; 
RENAME TABLE plugin_onlineshop_productindex_store TO ecommerceframework_productindex_store; 
RENAME TABLE plugin_onlineshop_optimized_productindex TO ecommerceframework_optimized_productindex; 
RENAME TABLE plugin_onlineshop_optimized_productindex_relations TO ecommerceframework_optimized_productindex_relations; 
```

- Renamed translations & permissions
```sql 
UPDATE translations_admin SET `key` = REPLACE(`key`, 'plugin_onlineshop_', 'bundle_ecommerce_') WHERE `key` LIKE 'plugin_onlineshop%';
UPDATE users_permission_definitions SET `key` = REPLACE(`key`, 'plugin_onlineshop_', 'bundle_ecommerce_');	
```

- `IEnvironment`: 
  - added 
    - `getSystemLocale()`
    - `getDefaultCurrency()`
  - removed 
    - `setSessionNamespace()`
    - `getSessionNamespace()`
    - `getCurrencyLocale()`
   
