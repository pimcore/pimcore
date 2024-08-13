# Bundle Developer's Guide

Since Pimcore utilizes the powerful Symfony Bundle system, let us refer to the [Symfony Bundle Documentation](https://symfony.com/doc/current/bundles.html) on how to get started with your custom bundles. A bundle can do anything - in fact, core Pimcore functionalities like the admin interface are implemented as bundles. From within your bundle, you have all possibilities to extend the system, from defining new services or routes to hook into the event system or provide controllers and views.


## Bundle Directory Structure

See [Bundle Directory Structure](https://symfony.com/doc/current/bundles.html#bundle-directory-structure) for a standard bundle directory layout.


## Pimcore Bundles

There is a special kind of bundle implementing `Pimcore\Extension\Bundle\PimcoreBundleInterface` which gives you additional
possibilities. These bundles provide a similar API as plugins did in previous versions:

* The bundle shows up in the `pimcore:bundle:list` command with info if the bundle can be installed or uninstalled.
* The bundle can be installed with `pimcore:bundle:install` or uninstalled with `pimcore:bundle:uninstall` to trigger the installation/uninstallation (e.g. to install/update database structure).
* The bundle adds methods to natively register JS and CSS files to be loaded with the admin interface and in edit mode. 

See the [Pimcore Bundles](./05_Pimcore_Bundles/README.md) documentation to get started with Pimcore bundles.

### Generating Pimcore Bundles

With the [Pimcore Bundle Generator](https://github.com/pimcore/bundle-generator), we provide a tool for generating bundle skeletons. You can install and activate this bundle in your development instance and simplify starting the Pimcore bundle development.  

```
# generate bundle interactively
$ bin/console pimcore:generate:bundle

# generate bundle with a given name and don't ask questions
$ bin/console pimcore:generate:bundle --namespace=Acme/FooBundle --no-interaction

```

## Common Tasks

Below is a list of common tasks and how to achieve them inside your bundles. 

### Service Configuration

If you want to provide custom services from within your bundle, you must create an `Extension` to load your service definitions. This topic is covered in detail in the [Extensions Documentation](https://symfony.com/doc/current/bundles/extension.html).

You can find an example of how to create an extension for your bundles in [Loading Service Definitions](./01_Loading_Service_Definitions.md).


### Auto-Loading Config and Routing Definitions

Bundles can provide config and routing definitions in `Resources/config/pimcore` which will be automatically loaded with the bundle. See [Auto loading config and routing definitions](./03_Auto_Loading_Config_And_Routing_Definitions.md) for more information.


### i18n / Translations

See the [Symfony Translation Component Documentation](https://symfony.com/doc/current/translation.html#translation-resource-file-names-and-locations) for locations that will be automatically searched for translation files.

For bundles, translations should be stored in the `Resources/translations/` directory of the bundle in the format `locale.loader` (or `domain.locale.loader` if you want to handle a specific translation domain). In most cases, this will be something like `Resources/translations/en.yml`, which resolves to the default `messages` translation domain.

Example: admin.en.yml or messages.en.yml

#### Translations Domain
A translation domain is only considered valid when it is registered as follows:
```yaml
pimcore:
    translations:
        domains:
            - site_1
            - site_2
````

Then only translations stored in a dedicated domain table e.g. `translations_DOMAIN` are used by the Pimcore translation service.

### Security / Authentication

You can fully use the [Symfony Security Component](https://symfony.com/doc/current/security.html) by auto-loading
the security configuration as documented above. The best practice is to define the security configuration in a dedicated `security.yaml`, which can be imported from your bundle's `config.yaml`.

For further details on security please refer to [Security](../../19_Development_Tools_and_Details/10_Security_Authentication/README.md).


### Events

To hook into core functions, you can attach to any event provided by the [Pimcore event manager](../11_Event_API_and_Event_Manager.md).
Custom listeners can be registered from your bundle by defining an event listener service. Further reading:
 
* [Symfony Event Dispatcher](https://symfony.com/doc/current/event_dispatcher.html) for documentation on how to create event listeners and how to register them as a service.
* [Pimcore Event Manager](../11_Event_API_and_Event_Manager.md) for a list of available events.


### Local Storage for Your Bundle

Sometimes a bundle needs to save files (e.g. generated files or cached data, ...). If the data is temporary and should be removed when the Symfony cache is cleared, please use a directory inside the cache directory. The core cache directory can
be fetched from the `Kernel` and is registered as a parameter on the container:

* `$kernel->getCacheDir()`
* `%kernel.cache_dir%` parameter

If you need persistent storage, create a unique directory in `PIMCORE_PRIVATE_VAR`, e.g. `var/bundles/YourBundleName`.

### Extending the Admin UI

The following section explains how to design and structure bundles and how to register for and utilize the events provided in the PHP backend and the Ext JS frontend: [Event_Listener_UI](./06_Event_Listener_UI.md)

### Adding Document Editables

See [Adding Document Editables](./09_Adding_Document_Editables.md)
