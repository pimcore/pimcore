# Adding Document Editables 

With bundles, it is also possible to add an individual Document Editable. 

Previously, the only way to define editables was to create them in a special namespace `Pimcore\Model\Document\Editable`. This
is still possible, but now editables can be in any namespace as long as the editable is correctly registered. Registration
can be done via 2 config entries which define a list of prefixes (namespaces) to be searched and a static mapping from
editable name to class name. For best performance, you should always use the class mapping as it avoids having to look
up class names.

To register a new editable, you need to follow 3 steps:

## 1) Create the editable class

The editable **must** extend `Pimcore\Model\Document\Editable`. Lets create a `Markdown` editable (the namespace does not matter
but it's best practice to put your editables into a `Model\Document\Editable` sub-namespace):

```php
<?php
// src/Model/Document/Editable/Markdown.php

namespace App\Model\Document\Editable;

class Markdown extends \Pimcore\Model\Document\Editable
{
    // methods as required by Pimcore\Model\Document\Editable and Pimcore\Model\Document\Editable\EditableInterface
}
```

## 2) Register the editable on the editable map

Next we need to update `pimcore.documents.editables.map` configuration to include our editable. This can be done in any config
file which is loaded (e.g. `/config/config.yaml`), but if you provide the editable with a bundle you should define it
in a configuration file which is [automatically loaded](./03_Auto_Loading_Config_And_Routing_Definitions.md). Example:

```yaml
# /config/config.yaml

pimcore:
    documents:
        editables:
            map:
                markdown: \App\Model\Document\Editable\Markdown
```

## 3) Create frontend JS

For the frontend, a JavaScript class needs to be added `pimcore.document.editables.markdown`. It can 
extend any of the existing `pimcore.document.editables.*` class and must return it's type by overwriting 
the function `getType()`. If you extend from other bundles editables make sure your bundle is loaded after your parent editable has been initialized.

```js
// /public/js/pimcore/document/editables/markdown.js

pimcore.registerNS("pimcore.document.editables.markdown");
pimcore.document.editables.markdown = Class.create(pimcore.document.editables.textarea, {
    getType: function () {
        return "markdown";
    }
});
```

This JS file must be included in editmode. You can tell Pimcore to do so by implementing `addJSFiles()`
in event listener. 

```php
// src/EventListener/PimcoreAdminListener.php

namespace App\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;

class PimcoreAdminListener
{
    public function addJSFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/bundles/app/js/pimcore/document/editables/markdown.js',
        ]);
    }
}

```

Register event listener:
```yaml
services:
  App\EventListener\PimcoreAdminListener:
    tags:
      - { name: kernel.event_listener, event: pimcore.bundle_manager.paths.editmode_js, method: addJSFiles }
```
