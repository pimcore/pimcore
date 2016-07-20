The Pimcore user interface is based upon the Ext JS Framework.  
A plugin can add Ext components to the user interface or execute any other Javascript required in the plugin context.
User interface plugins for the pimcore admin need to be registered at a plugin broker, which notifies all registered plugins upon certain hooks. 
All Javascript and CSS which should be included, needs to be defined in plugin.xml, as described in [plugin anatomy and design](!Extensions/Plugin_Anatomy). 
These scripts are loaded last upon pimcore startup. 
They are loaded in the same order as specified in plugin.xml.

A plugin must extend pimcore.plugin.admin and can override all methods defined there. 

Each plugin needs to register itself with the plugin broker by calling:

```javascript
pimcore.plugin.broker.registerPlugin(plugin)
```
The broker then will notify each plugin upon the hooks described below:
* uninstall - is called when the corresponding plugin is uninstalled via pimcore admin
* pimcoreReady - admin is loaded, viewport is passed as parameter
* preOpenAsset - before asset is opened, asset and type are passed as parameters
* postOpenAsset - after asset is opened, asset and type are passed as parameters
* preOpenDocument - before document is opened, document and type are passed as parameters
* postOpenDocument - after document is opened, document and type are passed as parameters
* preOpenObject - before object is opened, object and type are passed as parameters
* postOpenObject - after object is opened, object and type are passed as parameters

Uninstall is called after plugin has been uninstalled - this hook can be used to do remove plugin features from the UI after installation. 
Note: In order to be notified upon uninstall, a plugin must override the ```getClassName``` method of ```pimcore.plugin.admin``` and return its own class name

## I18n texts for plugins

Plugin backend development gives details on how plugin specific translation files can be included. 

Once this is done, translations can be accessed anywhere in the plugin's javascript by calling

```javascript
t('translation_key')
```

## Installing and Uninstalling Plugin UI Components

Plugin UI components might need to be activated/loaded after the plugin is installed in the pimcore admin.
As plugin Javascript and CSS files are only available in the browser after installation and reloading of the UI, the  backend plugin can return a flag that UI reload is required.
If this flag is set to true, the UI asks the user to reload after install. After that, all plugin components should be available.
 
With uninstall, it is not absolutely necessary to reload just to deactivate plugin components. 
The plugin is notified through the uninstall hook (provided that it implements the getClassName() method correctly).
In the uninstall function the plugin can hide/deactivate everything in the frontend UI that will not work anymore after uninstalling the plugin.
