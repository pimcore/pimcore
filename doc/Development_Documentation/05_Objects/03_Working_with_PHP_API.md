# Working with Objects via PHP API

Pimcore provides an object orientated PHP API to work with Objects. There are several generic functionalities 
provided by Pimcore and for each Pimcore object class Pimcore generates corresponding PHP classes for working
with these objects via a comfortable PHP API and take full advantage of a IDE (e.g. code completion etc.). 
    
## CRUD Operations
The following code snippet indicates how to access, create and modify an object programmatically:

```php 
// Create a new object
$newObject = new Object\Myclassname(); 
$newObject->setKey(\Pimcore\File::getValidFilename('New Name'));
$newObject->setParentId(123);
$newObject->setName("New Name");
$newObject->setDescription("Some Text");
$newObject->save();


//getting objects
$myObject = Object\Myclassname::getById(167);

//reading data
$myObject->getName();
$myObject->getDescription();

// it's also possible to get an object by an foreign ID
$city = Object\City::getByZip(5020,1);

// you can also get an object by id where you don't know the type
$object = Object::getById(235);

// or obtain an object by path
$object = Object::getByPath("/path/to/the/object");


//updating and saving objects
$myObject->setName("My Name");
$myObject->save();

 
//deleting objects
$city->delete();

```


> When using your generated classes in the code, the classname always starts with a capital letter.  
> Example-Classname: `product`  
> PHP-Class: `Object\Product`

<a name="objectsListing">&nbsp;</a>

## Object Listings
Once data is available in a structured manner, it can not only be accessed more conveniently but also be filtered, 
sorted, grouped and displayed intuitively by the use of an object listing. Moreover, data can be exported very easily 
not only programmatically but also through the Pimcore object csv export.

Object listings are a simple way to retrieve objects from Pimcore while being able to filter and sort data along that 
process. Object listings also come with a built-in paginator that simplifies the display of results in a paged manner.

When working with object listings, user defined routes come in handy while implementing a object detail views. 
User defined routes allow directing requests to certain detail pages, even though the request does not portray the path 
of a document, but matches a certain route. For more information have a look at 
[URLs based on Custom Routes](../02_MVC/04_Routing_and_URLs/02_Custom_Routes.md).

An object listing class is created automatically for each class defined in Pimcore. Objects for the class `Myobject` 
are retrieved through a listing as in the following example:

```php
$entries = new Object\Myclassname\Listing();
$entries->setOffset($offset);
$entries->setLimit($perPage);
$entries->setOrderKey("date");
$entries->setOrder("desc");
$entries->setCondition("name LIKE ?", ["%bernie%"]); // use prepared statements! Mysqli only supports ? placeholders
// or
$entries->setCondition("name LIKE :name", ["name" => "%bernie%"]); // With PDO_Mysql you can use named parameters
// to add param to the condition
$entries->addConditionParam("city = ?", "New York", "AND"); // concatenator can be AND or OR
   
//if necessary you can of course custom build your query
$entries->setCondition("name LIKE " . $entries->quote("%bernie%")); // make sure that you quote variables in conditions!
foreach ($entries as $entry) {
    $entry->getName();
}
 
// there is also a shorthand eg.:
$items = Object\Myclassname::getList([
    "offset" => $offset,
    "limit" => $perPage,
    "orderKey" => "date",
    "order" => "desc"
]);
 
// order by multiple columns
$items = Object\Myclassname::getList([
    "offset" => $offset,
    "limit" => $perPage,
    "orderKey" => ["date", "name"],
    "order" => "desc"
]);
 
// with different directions
$items = Object\Myclassname::getList([
    "offset" => $offset,
    "limit" => $perPage,
    "orderKey" => ["name", "date"],
    "order" => ["asc","desc"]
]);
 
// with random order
$items = new Object\PhoneProduct\Listing();
$items->setOrderKey("RAND()", false);
 
foreach ($items as $item) {
    echo $item . "<br />"; // output the path of the object
}
 
// with subselect in order
$items = new Object\PhoneProduct\Listing();
$items->setOrderKey("(SELECT id FROM sometable GROUP BY someField)", false);
```

### Using prepared statement placeholders and variables
The syntax is similar to that from the Zend Framework described 
[here](http://framework.zend.com/manual/en/zend.db.adapter.html#zend.db.adapter.select.fetchall).

```php
$entries = new Object\Myclassname\Listing();
$entries->setCondition("name LIKE ?", "%bernie%");
$entries->load();
 
foreach($entries as $entry) {...}
 
// using more variables / placeholders
$entries = new Object\Myclassname\Listing();
$entries->setCondition("name LIKE ? AND date > ?", ["%bernie%", time()]);
$entries->load();
 
foreach($entries as $entry) {...}
 
 
// using named placeholders (recommended) - only with PDO Mysql Adapter
$entries = new Object\Myclassname\Listing();
$entries->setCondition("name LIKE :name AND date > :date", ["name" => "%bernie%", "date" => time()]);
$entries->load();
```

### Conditions on localized fields
Following code will only search the EN value of the field `name`.
```php
$entries = new Object\Myclassname\Listing();
$entries->setLocale("en"); // string or instance of Zend_Locale
$entries->setCondition("name LIKE :name", ["name" => "%term%"]); // name is a field inside a localized field container
```

##### Disable Localized Fields in listings
Sometimes you don't want to have localized data on the listings (condition & order by). For this particular case you 
can disable localized fields on your listing (the objects in the list will still include the localized fields). 
Conditions and order by statements on localized fields are then not possible anymore. 

```php
$entries = new Object\Myclassname\Listing();
$entries->setIgnoreLocalizedFields(true);
$entries->load();
```

### Get Objects matching a value of a property
Often it's very useful to get a listing of objects or a single object where a property is matching exactly one value.
This is especially useful to get an object matching a foreign key, or get a list of objects with only one condition.

```php
$result = Object\ClassName::getByMyfieldname($value, [int $limit, int $offset]);
```
If you set no limit, a list object containing all matching objects is returned. If the limit is set to 1 
the first matching object is returned directly (without listing). Only published objects are return.

Alternatively you can pass an array as second parameter which will be applied to the object listing.
```php
$result = Object\ClassName::getByMyfieldname($value, ['limit' => 1,'unpublished' => true]);
```

##### Examples
```php

// get a list of cities in Austria
$list = Object\City::getByCountry("AT");
foreach ($list as $city) {
    // do something with the cities
    $city->getZip();
    ...
}
 
 
// get a city by zip
$city = Object\City::getByZip(5020, 1);
$city->getZip(); // do something with the city
 
 
 
// get the first 10 cities in Austria
$list = Object\City::getByCountry("AT", 10);
foreach ($list as $city) {
    // do something with the cities
    $city->getZip();
}
```

### Get an Object List/Object by Localized Fields

```php
$list = Object\News::getByLocalizedfields($fieldName, $value, $locale, $limit | array($limit, $offset, $unpublished));
```


##### Examples
```php
// get a list of cities in Austria by localized field using default locale
$list = Object\City::getByLocalizedfields("country", "Österreich");
 
// get a city by localized name using default locale
$city = Object\City::getByLocalizedfields("city", "Wels", null, 1);
 
// get the first 10 cities in Austria by localized field using default locale
$list = Object\City::getByLocalizedfields("country", "Österreich", null, 10);
  
// get the first 10 cities in Austria by localized field "de" locale
$list = Object\City::getByLocalizedfields("country", "Österreich", "de", 10);
 
//get a country by localized name in english
$country = Object\Country::getByLocalizedfields("name", "Austria", "en", 1);
```


### Get an Object List including unpublished Objects
Normally object lists only give published objects. This can be changed by setting a lists `unpublished` property to 
`true`.

```php
$list = Object\News::getList(["unpublished" => true]);

//or 

$list = new Object\News\Listing();
$list->setUnpublished(true);
$list->load();

```

### Filter Objects by attributes from Field Collections
To filter objects by attributes from field collections, you can use following syntax 
(Both code snippets result in the same object listing).

```php
$list = new Object\Collectiontest\Listing();
$list->addFieldCollection("MyCollection", "collection");
$list->addFieldCollection("MyCollection");
$list->setCondition("`MyCollection~collection`.myinput = 'hugo' AND `MyCollection`.myinput = 'testinput'");
```

```php

$list = Object\Collectiontest::getList([
   "fieldCollections" => [
      ["type" => "MyCollection", "fieldname" => "collection"],
      ["type" => "MyCollection"]
   ],
   "condition" => "`MyCollection~collection`.myinput = 'hugo' AND `MyCollection`.myinput = 'testinput'"
]);
```

You can add field collections to an listing object by specifying the type of the field collection and optionally the 
fieldname. The fieldname is the fieldname of the field collection in the class definition of the current object.
Once field collections are added to an object listing, you can access attributes of field collections in the condition 
of the object listing. The syntax is as shown in the examples above `FIELDCOLLECTIONTYPE~FIELDNAME.ATTRIBUTE_OF_FIELDCOLLECTION`, 
or if you have not specified a fieldname `FIELDCOLLECTION.ATTRIBUTE_OF_FIELDCOLLECTION`.

The object listing of this example only delivers objects of the type Collectiontest, which have
* an Fieldcollection of the type `MyCollection` and the value `testinput` in the attribute `myinput` and
* an Fieldcollection in the field `collection` of the type `MyCollection` and the value `hugo` in the attribute `myinput`. 


<a name="zendPaginatorListing">&nbsp;</a>

### Working with Zend_Paginator

##### Action 
```php
public function testAction( Request $request )
{
    $list = new Object\Simple\Listing();
    $list->setOrderKey("name");
    $list->setOrder("asc");
 
    $paginator = \Zend_Paginator::factory($list);
    $paginator->setCurrentPageNumber( $request->get('page') );
    $paginator->setItemCountPerPage(10);
    $this->view->paginator  = $paginator;
}
```
##### View
```php
<?php foreach($this->paginator as $item) { ?>
    <h2>- <?= $item->getName(); ?></h2>
<?php } ?>
 
<br />
 
<!-- pagination start -->
<?= $this->paginationControl($this->paginator, 'Sliding', 'includes/paging.php', [
   'urlprefix' => $this->document->getFullPath() . '?page=', // just example (this parameter could be used in paging.php to construct the URL)
   'appendQueryString' => true // just example (this parameter could be used in paging.php to construct the URL)
]); ?>
<!-- pagination end -->
```

##### Partial Script (includes/paging.php)
```php
<div>
    <ul class="pagination">
        <!-- First page link -->
        <li class="<?= (!isset($this->previous)) ? 'disabled' : ''; ?>"><a href="<?= $this->url(['page' => $this->first]); ?>">Start</a></li>
  
        <!-- Previous page link -->
        <li class="<?= (!isset($this->previous)) ? 'disabled' : ''; ?>"><a href="<?= $this->url(['page' => $this->previous]); ?>">&lt; Previous</a></li>
 
        <!-- Numbered page links -->
        <?php foreach ($this->pagesInRange as $page): ?>
            <?php if ($page != $this->current): ?>
                <li><a href="<?= $this->url(['page' => $page]); ?>"><?= $page; ?></a></li>
            <?php else: ?>
                <li class="disabled"><a href="#"><?= $page; ?></a></li>
            <?php endif; ?>
        <?php endforeach; ?>
         
        <!-- Next page link -->
        <li class="<?= (!isset($this->next)) ? 'disabled' : ''; ?>"><a href="<?= $this->url(['page' => $this->next]); ?>">Next &gt;</a></li>
         
        <!-- Last page link -->
        <li class="<?= (!isset($this->next)) ? 'disabled' : ''; ?>"><a href="<?= $this->url(['page' => $this->last]); ?>">End</a></li>
         
    </ul>
 </div>
```

### Access and modify internal object list query

It is possible to access and modify the internal query from every object listing. The internal query is based 
on [Zend_Db_Select](http://framework.zend.com/manual/1.12/de/zend.db.select.html).
```php

<?php
// get all news with ratings that is stored in a not Pimcore related table
 
/** @var \Pimcore\Model\Object\Listing\Dao|\Pimcore\Model\Object\News\Listing $list */
$list = new Pimcore\Model\Object\News\Listing();
 
// set onCreateQuery callback
$list->onCreateQuery(function (Zend_Db_Select $query) use ($list) {
    // join another table
    $query->join(
        ['rating' => 'plugin_rating_ratings'],
        'rating.ratingTargetId = object_' . $list->getClassId() . '.o_id',
        ''
    );
});
```

### Debugging the Object List Query

You can access and print the internal query which is based on [Zend_Db_Select](http://framework.zend.com/manual/1.12/de/zend.db.select.html) to debug your conditions like this:

```php
<?php
// get all news with ratings that is stored in a not Pimcore related table
 
/** @var \Pimcore\Model\Object\Listing\Dao|\Pimcore\Model\Object\News\Listing $list */
$list = new Pimcore\Model\Object\News\Listing();
 
// set onCreateQuery callback
$list->onCreateQuery(function (Zend_Db_Select $query) use ($list) {
    // echo query
    echo $query;
});
```
