# System Settings

In system settings (*Settings* > *System Settings*) system wide settings for Pimcore can be made. Changes should 
be made with care and only by developers. 
These settings are saved in `var/config/system.yml`. 


## Appearance & Branding 
Contains settings about changing appearance of Pimcore admin like login screen color, Admin interface color, background image & custom logo etc.

 
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
 You can choose one of the following options to access the system configuration:

```php 
<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Config;

class DefaultController extends FrontendController
{
    public function defaultAction(Request $request, Config $config)
    {
        // option 1 - use type-hinting to inject the config service
        $bar = $config['general']['valid_languages'];
        
        // option 2 - use the container parameter 
        $foo = $this->getParameter('pimcore.config')['general']['valid_languages'];    
    }

}
```
