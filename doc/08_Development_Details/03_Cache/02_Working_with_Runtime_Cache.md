---
title: Runtime Cache
description: Handle cached API results and force-reload data from the database.
---

# Working With Runtime Cache
Pimcore uses a runtime cache extensively to store API results for performance. Understanding how to deal with cached results is essential to ensure your code always works with the correct data. The following examples illustrate common scenarios:

```php
//Delete item from the list
$list = new \Pimcore\Model\DataObject\Myclassname\Listing;
//or
$list = new \Pimcore\Model\Document\Listing;
//or
$list = new \Pimcore\Model\Asset\Listing;
$list->load();
$list->current()->delete(); //delete current item from list

$list->load(); //call load again to reset the runtime cache

foreach ($list as $element) {
    ...
}

//using force param to load latest data from database
\Pimcore\Model\DataObject::getById(123) === \Pimcore\Model\DataObject::getById(123) => true

\Pimcore\Model\DataObject::getById(123) === \Pimcore\Model\DataObject::getById(123, ['force' => true]) => false
```

Using a large number of objects in one process could result in a "not enough memory" error.
For example, iterate through thousands of objects while reading or even creating them.
Please refer to this [section](../../03_Objects/03_External_System_Interaction.md#memory-issues) to handle memory issues, which also clears runtime cache along with tasks.
