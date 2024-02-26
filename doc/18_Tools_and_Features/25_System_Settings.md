# System Settings

In the system settings (*Settings* > *System Settings*) system-wide settings for Pimcore can be made. Changes should 
be made with care and only by developers.

These settings are saved in `var/config/system_settings/system_settings.yaml` or in the settings store based on your configuration. The production environment has them stored in the settings store by default.

To save system settings into the settings store, you will need to add following to your configuration:
```yaml
pimcore:
    config_location:
        system_settings:
            write_target:
                type: 'settings-store'
            read_target:
                type: 'settings-store'
```

To switch from the Symfony configuration to the settings store for the first time, please follow these steps:

1. Set your write target to `settings-store`:

```yaml
pimcore:
    config_location:
        system_settings:
            write_target:
                type: 'settings-store'
```
2. Save your system settings via admin user interface (Settings > System Settings).
3. Set your read target to `settings-store` as well:

```yaml
pimcore:
    config_location:
        system_settings:
            write_target:
                type: 'settings-store'
            read_target:
                type: 'settings-store' 
```

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
Settings for assets like version steps.

## Access system config in PHP Controller
 You can access the system configuration like in the following example:

```php 
<?php

namespace App\Controller;

use Pimcore\Config;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FrontendController
{
    public function defaultAction(Request $request, SystemSettingsConfig $config): Response
    {
        // use type-hinting to inject the config service
        $config = Config::getSystemConfiguration();
        $bar = $config['general']['valid_languages'];
    }
}
```
