# Adding Document Editables 

With bundles, it is also possible to add an individual Document Editable. 

Previously, the only way to define editables was to create them in a special namespace `Pimcore\Model\Document\Tag`. This
is still possible, but now editables can be in any namespace as long as the editable is correctly registered. Registration
can be done via 2 config entries which define a list of prefixes (namespaces) to be searched and a static mapping from
editable name to class name. For best performance, you should always use the class mapping as it avoids having to look
up class names.

To register a new editable, you need to follow 3 steps:

## 1) Create the editable class

The editable **must** extens `Pimcore\Model\Document\Tag`. Lets create a `Markdown` editable (the namespace does not matter
but it's best practice to put your editables into a `Model\Document\Tag` sub-namespace):

```php
<?php
// src/AppBundle/Model/Document/Tag

namespace AppBundle\Model\Document\Tag;

class Markdown extends \Pimcore\Model\Document\Tag
{
    // methods as required by Pimcore\Model\Document\Tag and Pimcore\Model\Document\Tag\TagInterface
}
```

## 2) Register the editable on the editable map

Next we need to update `pimcore.documents.tag.map` configuration to include our editable. This can be done in any config
file which is loaded (e.g. `app/config/config.yml`), but if you provide the editable with a bundle you should define it
in a configuration file which is [automatically loaded](./03_Auto_Loading_Config_And_Routing_Definitions.md). Example:

```yaml
# src/AppBundle/Resources/config/pimcore/config.yml

pimcore:
    documents:
        tags:
            map:
                markdown: \AppBundle\Model\Document\Tag\Markdown
```

## 3) Create frontend JS

For the frontend, a JavaScript class needs to be added `pimcore.document.tags.mytag`. It can 
extend any of the existing `pimcore.document.tags` and must return it's type by overwriting 
the function `getType()`.

This JS file must be included in editmode. You can tell Pimcore to do so by implementing `getEditmodeJsPaths()`
in your bundle class. 
