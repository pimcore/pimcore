# Bundle Developers Guide

In previous versions of Pimcore, a plugin system allowed to hook into the system to add custom functionality. Starting with
Pimcore 5, the plugin system was replaced by native Symfony bundles.  Therefore you do not need to any special
plugin structure but can refer to the [Symfony Bundle Documentation](http://symfony.com/doc/current/bundles.html) on how
to get started with your custom bundles. From within your bundle, you have all possibilities to extend the system, from
creating an defining services to routes, library code and anything else.

## Bundle layout

See [Bundle Directory Structure](http://symfony.com/doc/current/bundles.html#bundle-directory-structure) for a standard
bundle directory layout.

## Auto loading config and routing definitions

By default, Symfony does not load configuration and/or routing definitions from bundles but expects you to define everything
in `app/config` (optionally importing config files from bundles or other locations). Pimcore extends the config loading
by trying to load the following configuration files of every active bundle:

* `Resources/config/pimcore/config_<environment>.yml` with fallback to `Resources/config/pimcore/config.yml` if the environment
  specific lookup didn't find anything
* `Resources/config/pimcore/routing_<environment>.yml` with fallback to `Resources/config/pimcore/routing.yml` if the environment
  specific lookup didn't find anything

## Pimcore bundles

There is, however, a special kind of bundle implementing `Pimcore\Extension\Bundle\PimcoreBundleInterface` which gives you
additional possibilities:

* The bundle shows up in the extension manager and can be enabled/disabled from there. Normal bundles need to be registered
  via code in your `AppKernel.php`.
* In the extension manager, you're able to trigger installation/uninstallation of bundles, for example to install/update 
  database structure.
* The bundle adds methods to natively register JS and CSS files to be loaded with the admin interface and in editmode. 

See the [Pimcore Bundles](./01_Pimcore_Bundles.md) documentation to getting started with Pimcore bundles.


Plugins are the most advanced way but also the most complex way of extending Pimcore. Starting with Pimcore version 5,
plg

With plugins several things can be archived - they can be just a library of reuseable code 
components, they can utilize [Pimcores event API](../11_Event_API_and_Event_Manager.md) to
extend backend functionality and they can modify and  extend the Pimcore Backend UI by utilizing
Javascript user interface hooks. 

The following sections explain how to design and structure plugins and how to 
register for and utilize the events provided in the PHP backend and the Ext JS frontend.

* [Plugin_Class](./03_Plugin_Class.md) is the starting point for each plugin.
* [Plugin_Backend_UI](./05_Plugin_Backend_UI.md) for extending the Pimcore Backend UI with Javascript. 

In addition to these topics also have a look at the [Example](./07_Example.md) provided in 
the documentation. 

Additional aspects in plugin development are 
* [Adding Document Editables](./11_Adding_Document_Editables.md)
