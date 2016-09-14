# Working with documents via php API

[TOC]

## General

Pimcore provides and object orientated php API to work with Documents.

## CRUD operations

You can find basic crud operations for the documents below:

### Create a new document
To create a new document you need to specify two mandatory fields:
* An Unique key
* A parent id - id of the parent document (document, folder and every other type)
 
You can set also every other value available in documents structure (settings, properties, children etc.).

<div class="notice-box">
Type of the document (page, folder, link, email, snippet etc.) The complete list with available core types you can find here: `\Pimcore\Model\Document::$types`
Every type has a ther own model. for example: link type of document could be created by `\Pimcore\Model\Document\Link`.
</div>

The example below, shows how to create simple page document via PHP API. 

```php
//CREATE A PAGE DOCUMENT
$document1 = new \Pimcore\Model\Document\Page();
$document1->setKey('document10');
$document1->setParentId(82); // id of a document or folder
$document1->save();
```

Now in the documents tree you can see, the newly created document. 
It was putted into the *apitests* directory (the *apitests* directory is the parent).

![Create document by API](../img/documents_api_create.png)

### Edit an existing document

If you'd like to get a document data you can use the `getById` method from the `\Pimcore\Model\Document` class.
You're also able to load a document by path. 

Find below, the list of available methods for loading documents.

| Reference                          | Arguments    | Description                     |
|------------------------------------|--------------|---------------------------------|
| \Pimcore\Model\Document::getById   | int $id      | Returns a document by it's ID   |
| \Pimcore\Model\Document::getByPath | string $path | Returns a document by it's path |

The following code presents how to get the wysiwyg editable value of the document.

```php
//LOAD A DOCUMENT
$document = \Pimcore\Model\Document::getById(4);
if($document instanceof \Pimcore\Model\Document\Page) {

    //the logic when the type of the document is page
    /** @var \Pimcore\Model\Document\Page $document */

    /** @var \Pimcore\Model\Document\Tag\Wysiwyg $wysiwygElement */
    $wysiwygElement = $document->getElement('content');
    Zend_Debug::dump($wysiwygElement->getData());

}
```

And the output is:

```
string(22) "
test test test
"
```

You've probably guessed that if you want to change value of a choosen editable or any other value, you can just set the value (by an available method) and after, save the document.

```php

...

/** @var \Pimcore\Model\Document\Tag\Wysiwyg $wysiwygElement */
$wysiwygElement = $document->getElement('content');

$wysiwygElement->setDataFromResource('<p>Lorem Ipsum is simply dummy text of the printing and typesetting.</p>');

$document->save(); //save changes in the document
```

### Delete an document

As simple or even simpler is deletion. Just load the document and use `delete` method available in `\Pimcore\Model\Document`.

```php
$document = \Pimcore\Model\Document::getById(110);
if (null ==! $document) {
    $document->delete();
}
```


## Documents listings

