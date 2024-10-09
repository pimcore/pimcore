# Formatting Service

Pimcore ships a service for international formatting of numbers, currencies and date time. The service is basically a 
factory and wrapper of the [`IntlDateFormatter` component of php](https://php.net/manual/de/class.intldateformatter.php).
  
#### Usage Example
 
```php
<?php
$service = \Pimcore::getContainer()->get(Pimcore\Localization\IntlFormatter::class);

//optionally set locale (otherwise it is resolved from the request)
$service->setLocale("de");

echo $service->formatDateTime($time, IntlFormatter::DATETIME_MEDIUM);
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
    Pimcore\Localization\IntlFormatter:
        calls:
            - [setCurrencyFormat, ['en', '¤ #,##0.0']]
            - [setCurrencyFormat, ['de', '#,##0.00 ¤¤']]
```
