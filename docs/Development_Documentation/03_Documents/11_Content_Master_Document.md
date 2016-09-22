# Content Master Document

## General
The Content Master Document Setting, allows a document to inherit all of its content from another Document elsewhere in the tree.

If this setting is selected and **Apply new master document** is clicked, all content will be erased from the current 
Document and inherited from the Document in the Document field.

See, the step by step process below.

1. Drag master document to the document where you can inherits contents (Content-Master Document in settings tab).
2. Push the *Apply new master document* button

![Apply master document](../img/master_document_1step.png)

3. Confirm the dialog warning

![Confirm master document changes](../img/master_document_2step.png)

4. Now you can see grey places in the document. If you want to overwrite any value, just click the right button on it.
 
![Master document - the editmode preview](../img/master_document_3step.png)

## Content Master document in the code

Content master document doesn't change anything in the PHP API. If you load a document which has related a master document, 
then values returned by it will be replaced by values from the master document (unless values are overwritten).

You can get Master document object by `getContentMasterDocument` method avaialble in `\Pimcore\Model\Document\Page`.

```php
$document = \Pimcore\Model\Document\Page::getById(130);
Zend_Debug::dump([
    'master_document' => $document->getContentMasterDocument()->getKey(),
    'document' => $document->getKey()
]);
```

And you see an output like, below.

```
array(2) {
  ["master_document"] => string(10) "document-2"
  ["document"] => string(16) "child-document-9"
}
```

