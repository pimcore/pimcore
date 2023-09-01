# Working With Runtime Cache
Pimcore heavily uses runtime cache to cache API results for performance reasons. However, it is very crucial to understand that how to deal with cached results so that correct data should utilized from the API. Let's take few examples to understand similar situations:

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

All used objects will be cached in a memory, to intentionally avoid this, run the command:

```php
RuntimeCache::clear();
```

When to use it depends on your specific case, but in general, it could be used periodically in a loop.
