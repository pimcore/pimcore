# Marketing Settings

The `Marketing Settings` give you the possibility to configure marketing-specific settings, which are:
- Google Analytics
- Google Search Console
- Google Tag Manager


### Google Analytics
Google Analytics code is automaticaly injected during rendering the page.
This behaviour can be disabled using:

```
$front = \Zend_Controller_Front::getInstance();
$front->unregisterPlugin('\Pimcore\Controller\Plugin\Analytics');
```
