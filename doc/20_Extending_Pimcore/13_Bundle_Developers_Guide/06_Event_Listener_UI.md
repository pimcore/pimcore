# Event Listener UI

## General

The Pimcore Backend UI is based upon the [Ext JS](https://www.sencha.com/products/extjs/#overview) Framework. An event listener can
add Ext components to the user interface or execute any other JavaScript required in the listener context.

All JavaScript and CSS which should be included, needs to be defined in your bundle class, as described in 
[Pimcore Bundles](./05_Pimcore_Bundles/README.md). 

Alternatively, you can setup this via an Eventlistener:

```yaml
services:
  # adds additional static files to admin backend
  App\EventListener\PimcoreAdminListener:
    tags:
      - { name: kernel.event_listener, event: pimcore.bundle_manager.paths.css, method: addCSSFiles }
      - { name: kernel.event_listener, event: pimcore.bundle_manager.paths.js, method: addJSFiles }
```

```php
<?php
namespace App\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;

class PimcoreAdminListener
{
    public function addCSSFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/admin-static/css/admin-style.css',
        ]);
    }

    public function addJSFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/admin-static/js/startup.js',
        ]);
    }
}
```


These scripts are loaded last upon Pimcore startup. They are loaded in the same order as specified in the bundle class.

Starting point for javascript development is the javascript event listener.

A listener can look as follows: 
```javascript
document.addEventListener(pimcore.events.pimcoreReady, (e) => {
    //print out the parameters of the event
    console.log(e.detail)
});
```

## JavaScript UI Events

For registering events just add a listener with some of the events from [events.js](https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/public/js/pimcore/events.js). 


## Validate Pimcore Object's Data in frontend before saving

It is possible to validate Pimcore Object's Data in frontend and cancel the saving if needed.

This can be done by using preventDefault() and stopPropagation():

Code example in startup.js:

```javascript
document.addEventListener(pimcore.events.preSaveObject, (e) => {
    let userAnswer = confirm(`Are you sure you want to save ${e.detail.object.data.general.className}?`);
    if (!userAnswer) {
        e.preventDefault();
        e.stopPropagation();
        pimcore.helpers.showNotification(t("Info"), t("saving_failed") + ' ' + 'placeholder', 'info');

    }
});
```

## I18n texts for js

Pimcore supports i18n for UI extensions. First see the [i18n section for bundles](./README.md) how to prepare the data 
server-side. 

Once this is done, translations can be accessed anywhere in the javascript code by calling

```javascript
t('translation_key')
```

## Adding Custom Main Navigation Items

It is possible to add leftside main navigation via event listener and the `preMenuBuild` event. See the following example to know how: 

```javascript
pimcore.plugin.mybundle = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        // the event contains the existing menu
        let menu = e.detail.menu;

        let items = [];
        // the property name is used as id with the prefix pimcore_menu_ in the html markup e.g. pimcore_menu_mybundle
        menu.mybundle = {
            label: t('myBundleLabel'), // set your label here, will be shown as tooltip
            iconCls: 'pimcore_main_nav_icon_myIcon', // set full icon name here
            priority: 42, // define the position where you menu should be shown. Core menu items will leave a gap of 10 custom main menu items
            items: items, //if your main menu has subitems please see Adding Custom Submenus To ExistingNavigation Items 
            shadow: false,
            handler: this.openMyBundle, // defining a handler will override the standard "showSubMenu" functionality, use in combination with "noSubmenus"
            noSubmenus: true, // if there are no submenus set to true otherwise menu won't show up
            cls: "pimcore_navigation_flyout", // use pimcore_navigation_flyout if you have subitems
        };
    },

    openMyBundle: function(e) {
        try {
            pimcore.globalmanager.get("plugin_pimcore_mybundle").activate();
        } catch (e) {
            pimcore.globalmanager.add("plugin_pimcore_mybundle", new pimcore.plugin.mybundle());
        }
    }
});

var myBundle = new pimcore.plugin.mybundle();
```
## Adding Custom Submenus To ExistingNavigation Items

It is possible to add submenus to existing menus just by pushing a new menu item into the submenu.

```javascript
pimcore.registerNS("pimcore.bundle.glossary.startup");

pimcore.bundle.glossary.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        // get the user to check for permissions
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (menu.extras && user.isAllowed("glossary") && perspectiveCfg.inToolbar("extras.glossary")) {
            // simply push the new menu item in a existing menu
            menu.extras.items.push({
                text: t("glossary"),
                iconCls: "pimcore_nav_icon_glossary", // make sure your icon class exists
                priority: 5, // define the position where you menu should be shown. Core menu items will leave a gap of 10 custom menu items
                itemId: 'pimcore_menu_extras_glossary', // specify your custom itemId here
                handler: this.editGlossary, // define a handler what should happen if you click on the menu item
            });
        }
    },

    editGlossary: function() {
        try {
            pimcore.globalmanager.get("bundle_glossary").activate();
        } catch (e) {
            pimcore.globalmanager.add("bundle_glossary", new pimcore.bundle.glossary.settings());
        }
    }
});

const pimcoreBundleGlossary = new pimcore.bundle.glossary.startup();
```


## Adding Custom Key Bindings

It is possible to add custom key bindings via event listener the `preRegisterKeyBindings` event and a config setting. Most key bindings are used to have shortcuts for menus.

```yaml
pimcore_admin:
    user:
        default_key_bindings:
            glossary:
                key: 'G'
                action: glossary # make sure that the action has the same name as the function you add to the keyBindingMapping e.g. pimcore.helpers.keyBindingMapping.glossary
                alt: true
                shift: true
```

```javascript
pimcore.registerNS("pimcore.bundle.glossary.startup");

pimcore.bundle.glossary.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
    },
    
    registerKeyBinding: function(e) {
        const user = pimcore.globalmanager.get('user');
        // always check for permissions before adding the key binding
        if (user.isAllowed("glossary")) {
            // make sure the function and the action name are the same
            pimcore.helpers.keyBindingMapping.glossary = function() {
                pimcoreBundleGlossary.editGlossary();
            }
        }
    }
});

const pimcoreBundleGlossary = new pimcore.bundle.glossary.startup();
```
