---
title: Formatting Service
description: "International formatting of numbers, currencies, and dates using PHP's Intl extension."
---

# Formatting Service

Pimcore ships a service for locale-aware formatting of numbers, currencies, and dates. The `IntlFormatter` service
wraps PHP's [`IntlDateFormatter`](https://php.net/manual/en/class.intldateformatter.php) and
[`NumberFormatter`](https://php.net/manual/en/class.numberformatter.php) components. By default, it resolves the
locale from the current request.

## Usage Example
 
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

## Overriding the Service Definition

You can override the default service definition with your own, for example to customize the default currency patterns. 

```yaml
services:
    # Formatting service for dates, times and numbers
    Pimcore\Localization\IntlFormatter:
        calls:
            - [setCurrencyFormat, ['en', '¤ #,##0.0']]
            - [setCurrencyFormat, ['de', '#,##0.00 ¤¤']]
```
