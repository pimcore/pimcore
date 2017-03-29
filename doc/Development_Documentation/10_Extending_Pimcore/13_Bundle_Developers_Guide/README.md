# Bundle Developers Guide

In previous versions of Pimcore, a plugin system allowed you to hook into the system to add custom functionality. Starting with
Pimcore 5, the plugin system was replaced by native Symfony bundles. Therefore, you do not need to any special
plugin structure but can refer to the [Symfony Bundle Documentation](http://symfony.com/doc/current/bundles.html) on how
to get started with your custom bundles. A bundle can do anything - in fact, core Pimcore functionalities like the admin
interface are implemented as bundle. From within your bundle, you have all possibilities to extend the system, from
defining new services or routes to hook into the event system or provide controllers and views.


## Bundle layout

See [Bundle Directory Structure](http://symfony.com/doc/current/bundles.html#bundle-directory-structure) for a standard
bundle directory layout.


## Pimcore bundles

There is a special kind of bundle implementing `Pimcore\Extension\Bundle\PimcoreBundleInterface` which gives you additional
possibilities. These bundles provide a similar API as plugins did in previous versions:

* The bundle shows up in the extension manager and can be enabled/disabled from there. Normal bundles need to be registered
  via code in your `AppKernel.php`.
* In the extension manager, you're able to trigger installation/uninstallation of bundles, for example to install/update 
  database structure.
* The bundle adds methods to natively register JS and CSS files to be loaded with the admin interface and in editmode. 

See the [Pimcore Bundles](./05_Pimcore_Bundles.md) documentation to getting started with Pimcore bundles.


## Service configuration

If you want to provide custom services from within your bundle, you need to create an `Extension` which is able to load
your service definitions. This is covered in detail in the [Extensions Documentation](http://symfony.com/doc/current/bundles/extension.html).

An example how to create an extension for your bundles can be found in
[Loading Service Definitions](./01_Loading_Service_Definitions.md).


## Auto loading config and routing definitions

Bundles can provide config and routing definitions in `Resources/config/pimcore` which will be automatically loaded with
the bundle. See [Auto loading config and routing definitions](./03_Auto_Loading_Config_And_Routing_Definitions.md) for
more information.


## i18n / Translations

See the [Symfony Translation Component Documentation](http://symfony.com/doc/current/translation.html#translation-resource-file-names-and-locations)
for locations which will be automatically searched for translation files.

For bundles, translations should be stored in the `Resources/translations/` directory of the bundle in the format `locale.loader`
(or `domain.locale.loader` if you want to handle a specific translation domain). For the most cases this will be something
like `Resources/translations/en.yml`, which resolves to the default `messages` translation domain.


## Events

To hook into core functions you can attach to any event provided by the [Pimcore event manager](../11_Event_API_and_Event_Manager.md).
Custom listeners can be registered from your bundle by defining an event listener service. Further reading:
 
* [Symfony Event Dispatcher](http://symfony.com/doc/current/event_dispatcher.html) for documentation how to create event
   listeners and how to register them as a service
* [Pimcore Event Manager](../11_Event_API_and_Event_Manager.md) for a list of available events


## Local Storage for your Bundle

Sometimes a bundle needs to save files (e.g. generated files or cached data, ...). If the data is temporary and should be
removed when the symfony cache is cleared, please use a directory inside the cache directory (e.g. `Pimcore::getKernel()->getCacheDir()`).

If you need persistent storage, create a unique directory in `PIMCORE_PRIVATE_VAR`, e.g. `var/bundles/YourBundleName`.


## Extending the Admin UI

The following section explains how to design and structure bundles and how to register for and utilize the events provided
in the PHP backend and the Ext JS frontend: [Plugin_Backend_UI](./06_Plugin_Backend_UI.md)

In addition to these topics also have a look at the [Example](./07_Example.md) provided in the documentation. 

Additional aspects in bundle development are:

* [Adding Document Editables](./11_Adding_Document_Editables.md)
