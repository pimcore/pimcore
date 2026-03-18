---
title: Cloning Elements
description: Clone and copy documents, assets, and data objects programmatically.
---

# Cloning Elements

Use `Service::cloneMe()` to get an in-memory copy of any element (anything implementing `ElementInterface`):

```php
$new = Pimcore\Model\Element\Service::cloneMe($source)
```

This does not update internal references.
For example, a relation in the source element pointing to itself still references the source element in the copy.

For a persistent copy, use the `copyAsChild` method of the corresponding service:

```php
$user = \Pimcore\Model\User::getById(123);

$assetService = new \Pimcore\Model\Asset\Service($user);
$assetService->copyAsChild($target, $source);

$documentService = new \Pimcore\Model\Document\Service($user);
$documentService->copyAsChild($target, $source); // additional arguments are available for inheritance, ...

$objectService = new \Pimcore\Model\DataObject\Service($user);
$objectService->copyAsChild($target, $source);
```
Where `$source` is the source element and `$target` the parent element of the new element.
This also creates a unique element key (or filename for asset elements).

To update references in the copy, call the service's `rewriteIds` method with a mapper config:

 ```php
 $rewriteConfig = array(
     "object" => array(
         176 => 190
     )
 );
 $object = DataObject\Service::rewriteIds($object, $rewriteConfig);
 ```
 This replaces all references to object ID 176 with references to object ID 190 in the copy.
