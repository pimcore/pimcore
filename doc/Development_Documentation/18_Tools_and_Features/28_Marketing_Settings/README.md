# Marketing Settings

The `Marketing Settings` give you the possibility to configure marketing-specific settings, which are:

- Google Analytics
- Google Search Console
- Google Tag Manager
- Piwik

## Google Analytics

Google Analytics code is automaticaly injected during rendering the page.
This behaviour can be disabled using:

```php
<?php
// fetch the listener through container or (better) inject it as dependency into your code
$gaListener = $container->get(\Pimcore\Bundle\CoreBundle\EventListener\Frontend\GoogleAnalyticsCodeListener::class);
$gaListener->disable();
```

## Piwik

Similar to Google Analytics, Piwik tracking code can be automatically injected into each response. 
