# Pimcore TinyMCE


## General

TinyMCE bundle provides TineMCE as WYSIWYG-editor.
Similar to Textarea and Input you can use the WYSIWYG editable in the templates to provide rich-text editing.

## Configuration

Available configuration options can be found here: [config options](https://www.tiny.cloud/docs/configure/)

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

This will show you the default menubar from TinyMCE in all editables.

To load that file in editmode, you need to implement `getEditmodeJsPaths` in your bundle class. Given your bundle is named
`AppAdminBundle` and your `editmode.js` created before was saved to `src/AppAdminBundle/public/js/editmode.js`:

```php
<?php

namespace AppAdminBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class AppAdminBundle extends AbstractPimcoreBundle
{
    public function getEditmodeJsPaths()
    {
        return [
            '/bundles/appadmin/js/pimcore/editmode.js'
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

    public function onEditmodeJsPaths(PathsEvent $event)
    {
        $event->setPaths(array_merge($event->getPaths(), [
            '/bundles/app/js/pimcore/editmode.js'
        ]));
    }
}
```
