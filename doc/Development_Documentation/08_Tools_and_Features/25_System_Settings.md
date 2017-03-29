# System Settings

In system settings (*Settings* > *System Settings*) system wide settings fpr Pimcore can be made. Changes should 
be made with care and only by developers. 
These settings are saved in `var/config/system.php`. 


## General 
Contains general settings about timezone, view suffix, additional path variables, default langauge, user interface etc.

 
## Localization & Internationalization (i18n/l10n) 
This settings are used in documents to specify the content language (in properties tab), for objects in localized-fields, 
for shared translations, ... simply everywhere the editor can choose or use a language for the content.
Fallback languages are currently used in object's localized fields and shared translations.

## Debug
Several debugging settings for Pimcore, like Debug Mode, Password Protection, Core Logger, and Application Logger settings. 

### Debug Mode
The Debug Mode is useful if you're developing a website / application with Pimcore.

With debug-mode on, errors and warnings are displayed directly in the browser, otherwise they are deactivated and the 
error-controller is active (Error Page).

You can restrict the debug mode to an (or multiple) IP address(es), so that it is only active for requests from a 
specific remote address.

![System Settings](../img/system-settings1.png)

If you are using `Pimcore\Mail` to send emails and the Debug Mode is enabled, all emails will be sent to the debug email 
receivers defined in *Settings* > *System Settings* > *Email Settings* > *Debug email addresses*. In addition a debug 
information is attached to the email which shows you to who the email would be sent if the debug mode is disabled.

To check anywhere in your own code if you are working in debug-mode, you can make use of the `PIMCORE_DEBUG` constant.

### DEV-Mode
The development mode enables some debugging features. This is useful if you're developing on the core of Pimcore or when 
creating a plugin. Please don't activate it in production systems!

What exactly does the dev mode:
* Loading the source javascript files (uncompressed & commented)
* Disables some caches (Webservice Cache, ...)
* extensive logging into debug.log
* ... and some more little things


## E-Mail Settings
Settings for default values of Mails sent via `Pimcore\Mail`. 


## Website
System settins about the CMS part of Pimcore. 

### EU Cookie Policy Notice
Pimcore has a default implementation for EU cookie policy that looks like as follows. 

![Cookie Policy](../img/system-settings-sample.png)


You can specify your own texts and add your custom detail link using the "Shared Translations".
Just search for "cookie-" in Shared Translations, then you get listed the predefined keys for the cookie 
texts and links:

![Cookie Policy Translation](../img/system-settings2.png)

##### Use a Custom Template Code
```php
// anywhere in your code, preferred in Website\Controller\Action::init() 
$front = \Zend_Controller_Front::getInstance();
$euCookiePlugin = $front->getPlugin("Pimcore\\Controller\\Plugin\\EuCookieLawNotice");
$euCookiePlugin->setTemplateCode("<b>Your Custom Template</b> ...");
```
 
## MySQL Database
Settings for database connection. These settings are read only here and need to be modified (if necessary) directly in 
`var/config/system.php`. 


## Documents
Settings for documents like version steps, default values and URL settings. 


## Objects
Version steps for objects. 


## Assets 
Settings for assets like version steps, default color profiles for thumbnail processing and display settings.


## Google Credentials & API Keys
Google API Credentials (Service Account Client ID for Analytics, ...) is required for the Google API integrations. 
Only use a *Service Account* from the Google API Console.

Google API Key (Simple API Access for Maps, CSE, ...) is e.g. required for correct display of geo data types in Pimcore ojbects. 
 
 
## Ouput-Cache
Settings for Pimcore [output cache](../09_Development_Tools_and_Details/09_Cache/README.md).


## Outputfilters
Settings for default output filters shipped with Pimcore. 


## Web Service API
Settings fpr Pimcore web service API. 


## HTTP Connectivity (direct, proxy, ...)
Settings for outbound HTTP connectivity of Pimcore - needed e.g. for Pimcore Updates or custom code using HTTP-Clients. 
 
 
## Newsletter
Possibility for configuring different newsletter delivery settings from the default e-mail settings.
 
 
