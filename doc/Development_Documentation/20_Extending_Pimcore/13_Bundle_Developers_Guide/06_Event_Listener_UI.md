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
    public function addCSSFiles(PathsEvent $event)
    {
        $event->setPaths(
            array_merge(
                $event->getPaths(),
                [
                    '/admin-static/css/admin-style.css'
                ]
            )
        );
    }

    public function addJSFiles(PathsEvent $event)
    {
        $event->setPaths(
            array_merge(
                $event->getPaths(),
                [
                    '/admin-static/js/startup.js'
                ]
            )
        );
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

For registering events just add a listener with some of the events from [events.js](https://github.com/pimcore/pimcore/blob/10.5/bundles/AdminBundle/Resources/public/js/pimcore/events.js). 


## Validate Pimcore Object's Data in frontend before saving

It is possible to validate Pimcore Object's Data in frontend and cancel the saving if needed.

This can be done by using preventDefault() and stopPropagation():

Code example in startup.js:

```javascript
document.addEventListener(pimcore.events.preSaveObject, (e) => {
    let userAnswer = confirm(`Are you sure you want to save ${e.detail.object.data.general.o_className}?`);
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

It is possible to add leftside main navigation via event listener. See the following example to know how: 

> The new navigation item (the `li` tag) must have an `id` attribute starting with `pimcore_menu_`, whose suffix must be the prefix of the "menu variable" of the toolbar.
  So, the `xxx` part of the id `pimcore_menu_xxx` must match `pimcore.layout.toolbar.prototype.xxxMenu` to display the navigation item.

```javascript
let navEl = Ext.get('pimcore_menu_search').insertSibling('<li id="pimcore_menu_mds" data-menu-tooltip="mds extension" class="pimcore_menu_item pimcore_menu_needs_children">mds extension</li>', 'after');
const menu = new Ext.menu.Menu({
    items: [{
        text: "Item 1",
        iconCls: "pimcore_icon_apply",
        handler: function () {
            alert("pressed 1");
        }
    }, {
        text: "Item 2",
        iconCls: "pimcore_icon_delete",
        handler: function () {
            alert("pressed 2");
        }
    }],
    cls: "pimcore_navigation_flyout"
});
pimcore.layout.toolbar.prototype.mdsMenu = menu;


document.addEventListener(pimcore.events.pimcoreReady, (e) => {
    let toolbar = pimcore.globalmanager.get("layout_toolbar");
    navEl.on("mousedown", toolbar.showSubMenu.bind(toolbar.mdsMenu));

    const mdsMenuReady = new CustomEvent("mdsMenuReady", {
        detail: {
            mdsMenu: toolbar.mdsMenu,
            type: "document"
        }
    });

    document.dispatchEvent(mdsMenuReady);
});
```
