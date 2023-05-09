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

To switch from Symfony configuration to settings store for the first time, you need to copy the content of your `system_settings.yaml` file and convert it into JSON format.

You can then manually connect to your database and insert the converted JSON string into the `settings_store` table or use a [script](../19_Development_Tools_and_Details/42_Settings_Store.md).

You need to provide these values for the settings store:
- id => `system_settings`
- scope => `pimcore_system_settings`
- data => your converted JSON string
- type => `string`

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
