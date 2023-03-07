# Adding Document Types

Previously, the only way to define custom documents was to create them in a special namespace `Pimcore\Model\Document`. This
is still possible, but the document can be in any namespace as long as the document is correctly registered. Registration
can be done via 2 config entries which define a list of prefixes (namespaces) to be searched and a static mapping from
document type to class name. For best performance, you should always use the type definitions mapping as it avoids having to look
up class names.

To register a new document, you need to follow 2 steps:

## 1) Create the document class

The document **must** extend `Pimcore\Model\Document`. Lets create a `Book` document (the namespace does not matter
but it's best practice to put your documents into a `Model\Document` sub-namespace):

```php
<?php
// src/Model/Document/Book.php

namespace App\Model\Document;

class Book extends \Pimcore\Model\Document
{
    // do override the type here
    protected string $type = 'book';
}
```

## 2) Register the document on the document type map

Next we need to update `pimcore.documents.type_definitions.map` configuration to include our document. This can be done in any config
file which is loaded (e.g. `/config/config.yaml`), but if you provide the editable with a bundle you should define it
in a configuration file which is [automatically loaded](./03_Auto_Loading_Config_And_Routing_Definitions.md). Example:

```yaml
# /config/config.yaml

pimcore:
    documents:
        type_definitions:
            map:
                book: \App\Model\Document\Book
```
