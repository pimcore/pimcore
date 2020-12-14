### Working with Runtime Cache
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
\Pimcore\Model\DataObject\AbstractObject::getById(123) === \Pimcore\Model\DataObject\AbstractObject::getById(123) => true

\Pimcore\Model\DataObject\AbstractObject::getById(123) === \Pimcore\Model\DataObject\AbstractObject::getById(123, true) => true/false
```
