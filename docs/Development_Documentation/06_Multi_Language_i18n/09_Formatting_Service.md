# Formatting Service

Pimcore ships a service for international formatting of numbers, currencies and date time. The service is basically a 
 factory and wrapper of the [`IntlDateFormatter` component of php](http://php.net/manual/de/class.intldateformatter.php).
  
#### Usage Example 
```php 
$service = \Pimcore::getContainer()->get('pimcore.locale.intl_formatter');

//optionally set locale (otherwise it is retrived from container)
$service->setLocale("de");

echo $service->formatDateTime($time, IntlFormatterService::DATETIME_MEDIUM);
echo $service->formatNumber("45632325.32");
echo $service->formatCurrency("45632325.32", "EUR");


//for formatting currencies you can also define a pattern
echo $service->formatCurrency("45632325.32", "EUR", "#,##0.00 ¤¤");
```

#### Overwriting Definition
You can overwrite the default service definition with your own, e.g. to overwrite the default currency patterns. 

```yml 
services:
    # Formatting service for dates, times and numbers
    pimcore.locale.intl_formatter:
      class: Pimcore\Bundle\PimcoreBundle\Service\IntlFormatterService
      arguments: ['@pimcore.locale']
      calls:
        - [setCurrencyFormat, ['en', '¤ #,##0.0']]
        - [setCurrencyFormat, ['de', '#,##0.00 ¤¤']]

```