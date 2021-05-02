# System Settings

In system settings (*Settings* > *System Settings*) system wide settings for Pimcore can be made. Changes should 
be made with care and only by developers. 
These settings are saved in `var/config/system.yml`. 


## General 
Contains general settings about timezone, view suffix, additional path variables, default langauge, user interface etc.

 
## Localization & Internationalization (i18n/l10n) 
These settings are used in documents to specify the content language (in properties tab), for objects in localized-fields, 
for shared translations, ... simply everywhere the editor can choose or use a language for the content.
Fallback languages are currently used in object's localized fields and shared translations.

## Debug

Several debugging settings for Pimcore, like the Application Logger settings.

Please note that the core logger (log levels, files, ...) can now directly be configured via Symfony's Monolog configuration.
For details see:

* [Symfony Logging](https://symfony.com/doc/3.4/logging.html#handlers-writing-logs-to-different-locations)
* [Logging](../19_Development_Tools_and_Details/07_Logging.md) 

## E-Mail Settings
Settings for default values of Mails sent via `Pimcore\Mail`. 


## Website
System settings about the CMS part of Pimcore.

## Documents
Settings for documents like version steps, default values and URL settings. 


## Objects
Version steps for objects. 


## Assets 
Settings for assets like version steps, default color profiles for thumbnail processing and display settings.


## Google Credentials & API Keys
Google API Credentials (Service Account Client ID for Analytics, ...) is required for the Google API integrations. 
Only use a *Service Account* from the Google Cloud Console.

Google API Key (Simple API Access for CSE, ...) is e.g. required for correct display of geo data types in Pimcore ojbects. 
 
 
## Output-Cache
Settings for Pimcore [output cache](../19_Development_Tools_and_Details/09_Cache/README.md).


## Outputfilters
Settings for default output filters shipped with Pimcore. 


## HTTP Connectivity (direct, proxy, ...)
Settings for outbound HTTP connectivity of Pimcore - needed e.g. for Pimcore Updates or custom code using HTTP-Clients. 
 
 
## Newsletter
Possibility for configuring different newsletter delivery settings from the default e-mail settings.
 
 
## Access system config in PHP Controller
Using `\Pimcore\Config::getSystemConfig()` is deprecated. You can choose one of the following options to access the system configuration:

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
