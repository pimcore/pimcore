# Plugin Anatomy and Design

Plugins are situated within the plugins folder in the Pimcore root directory. For each plugin a separate 
folder must be created.

You can generate the structure of the plugin in the administration panel:

<div class="inline-imgs">

[comment]: #TODOinlineimgs

Go to: ![Tools](../../img/Icon_tools.png)  **Tools -> Extensions ->** ![Create new plugin skeleton](../../img/Icon_Create_new_plugin_skeleton.png)

</div>

The folder structure of a plugin should look as follows:

```
plugins
└───ExtensionExample
    │
    └───config (this is totally optional)
    │   |   di.php
    ├───controllers
    │   │   IndexController.php
    │   │   ...
    └───lib
    |   ├───ExtensionExample
    |   |   Plugin.php
    └───static
    |   └───css
    |   |   | example.css
    |   |   | ...
    |   └───js
    |   |   | startup.js
    |   |   | ...
    └───views
    |   └───scripts
    |   |   └───index
    |   |   |   | index.php
    |   |   |   | ...
    |   composer.json
    |   plugin.xml
    |   ...

```

The lib folder should contain all PHP libraries and plugin specific libraries, as well as any other php code required by 
the plugin. The lib folder is also the place for the plugin class, which needs to extend the abstract plugin class and 
implement the plugin interface provided by Pimcore. 

All user interface components should be situated in the static folder. Javascript/CSS files are included when the Pimcore 
user interface starts up, provided that they are properly specified in the plugin config.  

Further information on user interface plugin development is given in Ext JS frontend development.

A plugin needs to be configured in the plugin.xml with the following parameters:

* plugin name (unique identifier = plugin folder) (must not contain spaces)
* plugin nice name (name to be displayed in Pimcore)
* plugin icon (icon to be displayed in Pimcore)
* plugin description
* plugin server (repository where plugin can be downloaded and updated)
* plugin version and revision (these tags must be available, values are filled at repository checkin)
* source to display in an iframe in the Pimcore admin when opening the plugin optins (can be used for your own config form)
* PHP class name of the plugin
* PHP include paths
* PHP namespaces
* Javascript paths
* CSS file paths
* Javascript paths for scripts which should be included in document editmode
* CSS paths for stylesheets which should be included in document editmode
* Dependency Injection config paths (optional, for more informations about DI look at here: [Dependency Injection](../03_Dependency_Injection.md))

An example plugin.xml file looks like below:

```
<?xml version="1.0"?>
<zend-config xmlns:zf="http://framework.zend.com/xml/zend-config-xml/1.0/">
<plugin>
    <!-- unique identifier = folder name -->
    <pluginName>ExtensionExample</pluginName>
    <pluginNiceName>My ExtensionExample Plugin</pluginNiceName>
    <!-- 64 x 64 Pixel Icon -->
    <pluginIcon>/plugin/ExtensionExample/static/img/icon.png</pluginIcon>
    <pluginDescription>
        Put the description of your Plugin here.
        It is displayed in Pimcore backend
    </pluginDescription>
    <pluginServer>your.pluginrepository.com</pluginServer>
    <!-- Version, revision and timestamp are updated by createRevision script at check in to plugin server!-->
    <pluginVersion>1.0.0</pluginVersion>
    <pluginRevision>1</pluginRevision>
    <pluginBuildTimestamp>0</pluginBuildTimestamp>
    <!-- content to include in Pimcore admin in a iframe, if pluginIframeSrc is defined,
     the options button is included and the iframe is shown in an extra tab -->
    <pluginIframeSrc>/plugin/ExtensionExample/controller/action</pluginIframeSrc>
    <!-- className of the plugin which extends Pimcore_API_Plugin_Abstract-->
    <pluginClassName>ExtensionExample\Plugin</pluginClassName>
    <!-- include paths relative to plugin-directory -->
    <pluginIncludePaths>
    <path>/ExtensionExample/path1</path>
    <path>/ExtensionExample/path2</path>
    </pluginIncludePaths>
    <!-- namespaces to register with autoloader-->
    <pluginNamespaces>
    <namespace>ExtensionExample</namespace>
    <namespace>Resource</namespace>
    </pluginNamespaces>
    <!-- (optional) di config paths relative to plugin-directory -->
    <pluginDependencyInjectionPaths>
        <path>/ExtensionExample/config/di.php</path>
    </pluginDependencyInjectionPaths>
    <!-- js files needed for Pimcore plugin (backend) with path relative to plugin-directory -->
    <pluginJsPaths>
        <path>/ExtensionExample/static/js/test.js</path>
    </pluginJsPaths>
    <!-- css files needed for Pimcore plugin (backend) with path relative to plugin-directory -->
    <pluginCssPaths>
        <path>/ExtensionExample/static/css/test.css</path>
    </pluginCssPaths>   
    <!-- js which should be included in document edit mode,  path relative to plugin-directory -->
    <pluginDocumentEditmodeJsPaths>
        <path>/ExtensionExample/static/js/editmode.js</path>
    </pluginDocumentEditmodeJsPaths>
    <!-- css files which should be included in document edit mode, path relative to plugin-directory -->
    <pluginDocumentEditmodeCssPaths>
        <path>/ExtensionExample/static/css/edimode.css</path>
    </pluginDocumentEditmodeCssPaths>
    <!-- path to a configuration file which is directly opened in the file explorer when you click on the configure button in the extension manager -->
    <pluginXmlEditorFile>/website/var/plugins/test/config.xml</pluginXmlEditorFile>
</plugin>
</zend-config>
```
