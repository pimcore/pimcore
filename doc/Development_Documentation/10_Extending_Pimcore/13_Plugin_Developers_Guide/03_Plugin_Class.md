# Plugin Class

## General
The plugin class is the entry point for each plugin. It should extend `Pimcore\API\Plugin\AbstractPlugin` and must 
implement `Pimcore\API\Plugin\PluginInterface`. This means a plugin must implement the `install`, `uninstall` and 
`isInstalled` static methods specified in the interface.

The `install` method can be used to create database tables and do other initial tasks.
The `uninstall` method should make sure to undo all these things. Moreover, it can override the `readyForInstall` method.
This is also the right place to check for requirements such as minimum Pimcore version or read/write permissions on the filesystem. 
If this method returns `false`, the plugin cannot be installed via the Pimcore admin.


A simple plugin class could look as follows: 
```php
<?php
 
namespace Test;
  
use Pimcore\API\Plugin as PluginLib;
class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface {
    public function init() {
        // this method is called automatically during the initialization process of the plugin
    }
     
    public static function install(){
        return true;
    }
     
    public static function uninstall(){
        return true;
    }
    
    public static function isInstalled() {
        return true;
    }
}
```

Around this plugin class, a plugin can contain additional serverside PHP code in form of libs, models etc. and 
can interact with Pimcore events to modify Pimcore default behavior. 


## Hooks
To hook into the core functions you can attach to any event provided by the [Pimcore event manager](../11_Event_API_and_Event_Manager.md). 
For a full list of events and to learn more about events in Pimcore please have a look at the [event reference](../11_Event_API_and_Event_Manager.md). 

The following example shows a plugin that hooks into the document save process. 

```php
<?php

namespace ExtensionExample;

use Pimcore\API\Plugin as PluginLib;

class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface
{
    public function init()
    {
        parent::init();

        // register your events here

        // using anonymous function
        \Pimcore::getEventManager()->attach("document.postAdd", function ($event) {
            // do something
            $document = $event->getTarget();
        });

        // using methods
        \Pimcore::getEventManager()->attach("document.postUpdate", [$this, "handleDocument"]);
    }

    public function handleDocument($event)
    {
        // do something
        $document = $event->getTarget();
    }

    public static function install()
    {
        // implement your own logic here
        return true;
    }
    
    public static function uninstall()
    {
        // implement your own logic here
        return true;
    }

    public static function isInstalled()
    {
        // implement your own logic here
        return true;
    }
}
```


## i18n / Translations

If a plugin requires its own i18n texts in Pimcore backend UI, the PHP plugin class should override the `getTranslationFile` 
method contained in `Pimcore\API\Plugin\AbstractPlugin`. 

This method receives the current language as parameter and must return the path to the according texts file relative to 
the plugin directory - e.g. `/ExtensionExample/texts/en.csv`. The texts file must be a .csv file in which translations 
are specified by the translation key in the first column and the text in the second column. 
Column separator: ',' text identifier: '”'

```php
/**
 *
 * @param string $language
 * @return string $languageFile for the specified language relative to plugin directory
 */
public static function getTranslationFile($language)
    return parent::getTranslationFile($language); // TODO: Change
}
```


### Plugin Installation and Deinstallation in Pimcore UI
When a plugin is installed/uninstalled in the Pimcore backend UI, the frontend component might need the following information 
from the plugin.

If a plugin does not have a user interface component, these abstract methods can be ignored, and do not need to be overridden. 
More information on installing and uninstalling a plugin with UI components, is provided in plugin user interface development.

```php
public static function getJsClassName(){
    return "";
}
 
public static function needsReloadAfterInstall(){
    return false;
}
```

## Plugin State
Each plugin can show a status message in Pimcore plugin settings. 
To accomplish that override `getPluginState` method in class `plugins/ExtensionExample/lib/ExtensionExample/Plugin.php`.

```php
public static function getPluginState()
{
    return parent::getPluginState(); // TODO: Change it
}
```

## Local Storage for your Plugin
Sometimes a plugin needs to put file somewhere (logfiles, cache files and other dynamically generated),
Best practise is to use following location for it: 
`website/var/plugins/ExtensionExample` where **ExtensionExample** is the name of the plugin.