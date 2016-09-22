# Working with documents via PHP API

[TOC]

## General

Pimcore provides the object orientated PHP API to work with Documents.

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


## Document listings

### Examples

Let's assume that we following documents structure in our Pimcore instance.

![Apitests children - preview](../img/documents_apitests_children_preview.png)

To list all published documents with parentId = 82, see the code below.

```php
//list all published children from the folder
/** @var \Pimcore\Model\Document\Listing $listing */
$listing = \Pimcore\Model\Document::getList(['condition' => "`parentId` = 82"]);
Zend_Debug::dump($listing->getItems(0, 10));
```

In the output ou can see array which contains three items.

```
array(3) {
  [0] => object(Pimcore\Model\Document\Page)#225 (37) { ... }
  [1] => object(Pimcore\Model\Document\Page)#227 (37) { ... }
  [2] => object(Pimcore\Model\Document\Folder)#229 (22) { ... }
}
```

But sometimes, you would need to also get unpublished elements. 
To achieve that the only one thing to do is to add unpublished flag to the configuration argument in the `getList` method.

```php
//list all children from the folder
/** @var \Pimcore\Model\Document\Listing $listing */
$listing = \Pimcore\Model\Document::getList([
    'unpublished' => true,
    'condition' => "`parentId` = 82"
]);
```

Now in the output you can see also unpublished children.

Find below, other powerful keys which you can use in the config parameter in the `getList` method.

| Key         | Value               | Description                                                                       |
|-------------|---------------------|-----------------------------------------------------------------------------------|
| order       | string (asc,desc)   | Set ascending or descending order type.                                           |
| orderKey    | string              | Chosen column name / names for as a order key. You can choose many order keys.    |
| limit       | int                 | amount of collection results limit                                                |
| offset      | int                 | a distance from beginning of the collection items                                 |
| condition   | string              | Your own SQL condition like in the example above.                                 |

Extended example with additional parameters. 

```php
//list all published children from the folder
/** @var \Pimcore\Model\Document\Listing $listing */
$listing = \Pimcore\Model\Document::getList([
    'unpublished' => true,
    'condition' => "`parentId` = 82",
    'orderKey' => ['key', 'published'],
    'order' => 'desc',
    'offset' => 2,
    'limit' => 2
]);
```
Alternatively, you can build the statement by available methods.

```php
$listing = new \Pimcore\Model\Document\Listing();
$listing->setUnpublished(1);
$listing->setCondition("`parentId` = 82")
    ->setOrderKey(['key', 'published'])
    ->setOrder('desc')
    ->setOffset(2)
    ->setLimit(2);
```


### Methods

In the list object you can find few method which for sure, you're going to use. 

| Method                                               | Arguments                           | Description                                                                                 |
|------------------------------------------------------|-------------------------------------|---------------------------------------------------------------------------------------------|
| \Pimcore\Model\Document\Listing::getTotalCount       |                                     | Returns total number of selected rows.                                                      |
| \Pimcore\Model\Document\Listing::getPaginatorAdapter |                                     | List implements `\Zend_Paginator_Adapter_Interface`, you could use the list as a paginator. |
| \Pimcore\Model\Document\Listing::getItems            | int $offset, int $itemsCountPerPage | as arguments you have to specify the limit of rows and the offset.                          |
| \Pimcore\Model\Document\Listing::loadIdList          |                                     | Returns complete array with id as a row.                                                    |

If you want to know more about the paginator usage with lists, you should visit [Working with Objects via PHP API part](../05_Objects/03_Working_with_php_API.md#zendPaginatorListing)


