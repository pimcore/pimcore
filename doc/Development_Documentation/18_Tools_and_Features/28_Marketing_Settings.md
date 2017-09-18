# Marketing Settings

The `Marketing Settings` give you the possibility to configure marketing-specific settings, which are:
- Google Analytics
- Google Search Console
- Google Tag Manager


### Google Analytics
Google Analytics code is automaticaly injected during rendering the page.
This behaviour can be disabled using:

```
$gaListener = $container->get('pimcore.event_listener.frontend.google_analytics_code');
$gaListener->disable();
```
