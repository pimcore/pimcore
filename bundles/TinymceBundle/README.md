# Pimcore TinyMCE


## General

TinyMCE bundle provides TineMCE as WYSIWYG-editor.
Similar to Textarea and Input you can use the WYSIWYG editable in the templates to provide rich-text editing.

## Installation

Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```php
use Pimcore\Bundle\TinymceBundle\PimcoreTinymceBundle;
// ...

return [
    // ...
    PimcoreTinymceBundle::class => ['all' => true],
    // ...
];
```

```bash
bin/console pimcore:bundle:install PimcoreTinymceBundle
```

## Configuration

Available configuration options can be found here: [config options](https://www.tiny.cloud/docs/configure/)

## Default Configuration

`convert_unsafe_embeds` is set to `true` by default.
This means that unsafe elements like `<embed>` or `<object>` will be converted to more restrictive alternatives.
For more details please take a look at the [TinyMCE documentation](https://www.tiny.cloud/docs/configure/content-filtering/#convert_unsafe_embeds).

## Examples

### Basic usage

`wysiwyg` helper doesn't require any additional configuration options.
The following code add a second toolbar.

```twig
<section id="marked-content">
    {{  pimcore_wysiwyg("specialContent", {
            toolbar2: 'forecolor | h1 | h2'
        }) 
    }}
</section>
```
![Wysiwyg with extended toolbar - editmode](./doc/img/editables_wysiwyg_toolbar_editmode.png)

### Custom configuration for TinyMCE

The complete list of configuration options you can find in the [TinyMCE toolbar documentation](https://www.tiny.cloud/docs/advanced/available-toolbar-buttons/).

The WYSIWYG editable allows us to specify the toolbar.
If you have to limit styling options (for example only basic styles like `<b>` tag and lists would be allowed), just use `toolbar1` option.

```twig
<section id="marked-content">
    {{  pimcore_wysiwyg("specialContent", {
        toolbar1: 'forecolor | h1 | h2'
        }) 
    }}
</section>
```

Now the user can use only the limited toolbar.

##### Global Configuration

You can add a Global Configuration for all WYSIWYG Editors for all documents by setting `pimcore.document.editables.wysiwyg.defaultEditorConfig`.
You can add a Global Configuration for all WYSIWYG Editors for all data objects by setting `pimcore.object.tags.wysiwyg.defaultEditorConfig`.

For this purpose, you can create a [Pimcore Bundle](https://pimcore.com/docs/pimcore/current/Development_Documentation/Extending_Pimcore/Bundle_Developers_Guide/index.html) and add the
configuration in a file in the `Resources/public` directory  of your bundle (e.g. `Resources/public/js/editmode.js`).

```
pimcore.document.editables.wysiwyg = pimcore.document.editables.wysiwyg || {};
pimcore.document.editables.wysiwyg.defaultEditorConfig = { menubar: true };
```
This will show you the default menubar from TinyMCE in all document editables.

For the data object settings, you should put them in the `startup.js` in your bundle.
```
pimcore.registerNS("pimcore.plugin.YourTinymceEditorConfigBundle");

pimcore.plugin.YourTinymceEditorConfigBundle = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        pimcore.object.tags.wysiwyg = pimcore.object.tags.wysiwyg || {};
        pimcore.object.tags.wysiwyg.defaultEditorConfig = { menubar: true };
    }
});

const YourTinymceEditorConfigBundlePlugin = new pimcore.plugin.YourTinymceEditorConfigBundle();    
```



To load the `editmode.js` file in editmode, you need to implement `getEditmodeJsPaths` in your bundle class. Given your bundle is named
`AppAdminBundle` and your `editmode.js` and `startup.js` created before was saved to `src/AppAdminBundle/public/js/editmode.js` and `src/AppAdminBundle/public/js/startup.js`:

```php
<?php

namespace AppAdminBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class AppAdminBundle extends AbstractPimcoreBundle
{
    public function getEditmodeJsPaths(): array
    {
        return [
            '/bundles/appadmin/js/pimcore/editmode.js'
        ];
    }
    
    public function getJsPaths()
    {
        return [
            '/bundles/appadmin/js/pimcore/startup.js'
        ];
    }
}
```


###### Registering global configuration via events

You can also add the file which should be loaded in editmode through an event listener to avoid having to implement a
`PimcoreBundle` just for the sake of adding a file. Given you already have an `App` bundle and put the JS config from above
to `public/js/editmode.js` you can create an event listener to add the path to the list of loaded
files in editmode (please see [Events](https://pimcore.com/docs/pimcore/current/Development_Documentation/Extending_Pimcore/Event_API_and_Event_Manager.html) for details on how
to implement and register event listeners):

```php
<?php

namespace App\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Bundle\AdminBundle\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EditmodeListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BundleManagerEvents::EDITMODE_JS_PATHS => 'onEditmodeJsPaths'
        ];
    }

    public function onEditmodeJsPaths(PathsEvent $event): void
    {
        $event->addPaths([
            '/bundles/app/js/pimcore/editmode.js'
        ]);
    }
}
```

### Loading additional TinyMCE plugins that are not shipped with this bundle

You can load additional plugins that are not shipped by default with Pimcore's TinyMCE bundle.

The following example adds the plugin `charmap` (Note: Included since Pimcore 11.4):

1) [Download](https://www.tiny.cloud/get-tiny/) a TinyMCE dist package matching the version the bundle is currently shipped with.
2) Extract the desired plugin from the TinyMCE dist package and place it in your app's or bundle's resource folder, 
   e.g. copy `js/tinymce/plugins/charmap/plugin.min.js` to `public/static/js/tinymce_plugins/charmap/plugin.min.js`.
3) Use TinyMCE's config option [`external_plugins`](https://www.tiny.cloud/docs/tinymce/latest/editor-important-options/#external_plugins)
   to load the plugin:
```javascript
{
    // ...
    external_plugins: {
        charmap: '/static/js/tinymce_plugins/charmap/plugin.min.js',
    },
    // ...
    charmap: [/* ... */],  // plugin's configuration
}
```