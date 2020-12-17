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
// src/AppBundle/Model/Document/Editable/Markdown.php

namespace AppBundle\Model\Document\Editable;

class Markdown extends \Pimcore\Model\Document\Editable
{
    // methods as required by Pimcore\Model\Document\Editable and Pimcore\Model\Document\Editable\EditableInterface
}
```

## 2) Register the editable on the editable map

Next we need to update `pimcore.documents.editables.map` configuration to include our editable. This can be done in any config
file which is loaded (e.g. `app/config/config.yml`), but if you provide the editable with a bundle you should define it
in a configuration file which is [automatically loaded](./03_Auto_Loading_Config_And_Routing_Definitions.md). Example:

```yaml
# src/AppBundle/Resources/config/pimcore/config.yml

pimcore:
    documents:
        editables:
            map:
                markdown: \AppBundle\Model\Document\Editable\Markdown
```

## 3) Create frontend JS

For the frontend, a JavaScript class needs to be added `pimcore.document.editables.markdown`. It can 
extend any of the existing `pimcore.document.editables.*` class and must return it's type by overwriting 
the function `getType()`. If you extend from other bundles editables make sure your bundle is loaded after your parent editable has been initialized.

```js
// src/Resources/public/js/pimcore/document/editables/markdown.js

pimcore.registerNS("pimcore.document.editables.markdown");
pimcore.document.editables.markdown = Class.create(pimcore.document.editables.textarea, {
    getType: function () {
        return "markdown";
    }
});
```

This JS file must be included in editmode. You can tell Pimcore to do so by implementing `getEditmodeJsPaths()`
in your bundle class. 

```php
// src/AppBundle/AppBundle.php

namespace AppBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class AppBundle extends AbstractPimcoreBundle
{
    public function getEditmodeJsPaths()
    {
        return [
            '/bundles/app/js/pimcore/document/editables/markdown.js'
        ];
    }
}

```
