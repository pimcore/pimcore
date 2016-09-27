# Plugins and Support for ExtJS 6 and ExtJS 3.4

It is quite ease for a plugin to support both (ExtJS 6 and ExtJS 3.4) versions at the same time.

Usually, you register your source JS and CSS files via the `plugin.xml` by specifying them in the `<pluginJsPaths>` and 
`<pluginCssPaths>` sections.

Example:

```xml
<pluginJsPaths>
             <path>/plugins/FatalShutdown/static/js/startup.js</path>
             <path>/plugins/FatalShutdown/static/js/detailwindow.js</path>
             <path>/plugins/FatalShutdown/static/js/pimcore/layout/portlets/fatalshutdown.js</path>
</pluginJsPaths>
<pluginCssPaths>
             <path>/plugins/FatalShutdown/static/css/icons.css</path>
             <path>/plugins/FatalShutdown/static/css/style.css</path>
</pluginCssPaths>
```

What's new is that Pimcore 4 is that, if running in modern ExtJS 6 mode, it will first check the existence of the 
`<pluginJsPaths-extjs6>` and `<pluginCssPaths-extjs6>` sections and if found prefer them over the standard ones.

Example:

```xml
<pluginJsPaths-extjs6>
             <path>/plugins/FatalShutdown/static6/js/startup.js</path>
             <path>/plugins/FatalShutdown/static6/js/detailwindow.js</path>
             <path>/plugins/FatalShutdown/static6/js/pimcore/layout/portlets/fatalshutdown.js</path>
</pluginJsPaths-extjs6>
<pluginCssPaths-extjs6>
             <path>/plugins/FatalShutdown/static6/css/icons.css</path>
             <path>/plugins/FatalShutdown/static6/css/style.css</path>
</pluginCssPaths-extjs6>
```

After that it will always fallback to the standard scenario. So the typical way to migrate you plugin while still keep 
the 3.4 version working is to provide both sections and then just git rid of the extjs6 section when done.

 
There is also a PHP-side switch. You can find out whether you are running Pimcore in modern mode by calling.
`\Pimcore\Tool\Admin::isExtJS6()`.
