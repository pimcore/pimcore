# System Settings

In the system settings (*Settings* > *System Settings*) system-wide settings for Pimcore can be made. Changes should 
be made with care and only by developers. 
These settings are saved in `var/config/system_settings/system_settings.yaml` or in the settings store based on your configuration.

## Localization & Internationalization (i18n/l10n) 
These settings are used in documents to specify the content language (in properties tab), for objects in localized-fields, 
for shared translations, ... simply everywhere the editor can choose or use a language for the content.
Fallback languages are currently used in object's localized fields and shared translations.

## Debug
Debugging settings for Pimcore, like Debug email addresses, Debug admin translations.

## Website
System settings about the CMS part of Pimcore.

## Documents
Settings for documents like version steps, default values and URL settings. 

## Objects
Version steps for objects. 

## Assets 
Settings for assets like version steps, default color profiles for thumbnail processing and display settings.

## Access system config in PHP Controller
 You can access the system configuration like in the following example:

```php 
<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pimcore\SystemSettingsConfig;

class DefaultController extends FrontendController
{
    public function defaultAction(Request $request, SystemSettingsConfig $config): Response
    {
        // use type-hinting to inject the config service
        $config = $config->getSystemSettingsConfig();
        $bar = $config['general']['valid_languages'];
    }
}
```
