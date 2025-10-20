# Working With Objects via PHP API

Pimcore provides an object orientated PHP API to work with Objects. There are several generic functionalities
provided by Pimcore and for each Pimcore object class Pimcore generates corresponding PHP classes for working
with these objects via a comfortable PHP API and take full advantage of an IDE (e.g. code completion etc.).

## CRUD Operations
The following code snippet indicates how to access, create and modify an object programmatically:

```php
<?php

use \Pimcore\Model\DataObject;

// Create a new object
$newObject = new DataObject\Myclassname(); 
$newObject->setKey(\Pimcore\Model\Element\Service::getValidKey('New Name', 'object'));
$newObject->setParentId(123);
$newObject->setName("New Name");
$newObject->setDescription("Some Text");

// the optional parameter allows you to provide additional info
// currently supported:
//      * versionNote: note added to the version (see version tab)
$newObject->save(["versionNote" => "my new version"]);


//getting objects
$myObject = DataObject\Myclassname::getById(167);

//reading data
$myObject->getName();
$myObject->getDescription();

// it's also possible to get an object by an foreign ID
$city = DataObject\City::getByZip(5020,1);

// you can also get an object by id where you don't know the type
$object = DataObject::getById(235);

// or obtain an object by path
$object = DataObject::getByPath("/path/to/the/object");

// or get data objects matching a defined set of object types (default is to fetch data objects and variants)
$product = DataObject\Product::getByColor('purple', 1, 0, [Product::OBJECT_TYPE_VARIANT]);

//updating and saving objects
$myObject->setName("My Name");
$myObject->save();


//deleting objects
$city->delete();
```

> When using your generated classes in the code, the classname always starts with a capital letter.  
> Example-Classname: `product`  
> PHP-Class: `DataObject\Product`

<a name="objectsListing">&nbsp;</a>

## Object Listings
Once data is available in a structured manner, it can not only be accessed more conveniently but also be filtered,
sorted, grouped and displayed intuitively by the use of an object listing. Moreover, data can be exported very easily
not only programmatically but also through the Pimcore object csv export.

Object listings are a simple way to retrieve objects from Pimcore while being able to filter and sort data along that
process. Object listings also come with a built-in paginator that simplifies the display of results in a paged manner.

When working with object listings, user defined routes come in handy while implementing object detail views.
User defined routes allow directing requests to certain detail pages, even though the request does not portray the path
of a document, but matches a certain route. For more information have a look at
[URLs based on Custom Routes](../02_MVC/04_Routing_and_URLs/02_Custom_Routes.md).

An object listing class is created automatically for each class defined in Pimcore. Objects for the class `Myobject`
are retrieved through a listing as in the following example:

```php
<?php

use \Pimcore\Model\DataObject;

$entries = new DataObject\Myclassname\Listing();
$entries->setOffset($offset);
$entries->setLimit($perPage);
$entries->setOrderKey("date");
$entries->setOrder("desc");
$entries->setCondition("name LIKE ?", ["%bernie%"]); // use prepared statements! Mysqli only supports ? placeholders
// or
$entries->setCondition("name LIKE :name", ["name" => "%bernie%"]); // With PDO_Mysql you can use named parameters
// to add param to the condition (until build 181 this cannot be used with setCondition in the same listing, you should use setCondition OR addConditionParam but not both)
$entries->addConditionParam("city = ?", "New York", "AND"); // concatenator can be AND or OR

//use array bindings for prepared statements
$entries->setCondition("city IN (?)", [["New York", "Chicago"]]);
// or
$entries->setCondition("city IN (:cities)", ["cities" => ["New York", "Chicago"]]); // named parameters

//if necessary you can of course custom build your query
$entries->setCondition("name LIKE " . $entries->quote("%bernie%")); // make sure that you quote variables in conditions!

// some data types support direct filtering, which can be verified via 'isFilterable()' method on field definition:
if ($entries->getClass()->getFieldDefinition('fieldname e.g. name or age or city')->isFilterable()) {
    $entries->filterByName('Jan'); // filters for name='Jan'
    $entries->filterByAge(18, '>='); // filters for age >= 18
    $entries->filterByCity([['New York', 'Chicago']], 'IN (?)'); // filters for city IN ('New York','Chicago')
}

foreach ($entries as $entry) {
    $entry->getName();
}
 
// there is also a shorthand eg.:
$items = DataObject\Myclassname::getList([
    "offset" => $offset,
    "limit" => $perPage,
    "orderKey" => "date",
    "order" => "desc"
]);
 
// order by multiple columns
$items = DataObject\Myclassname::getList([
    "offset" => $offset,
    "limit" => $perPage,
    "orderKey" => ["date", "name"],
    "order" => "desc"
]);
 
// with different directions
$items = DataObject\Myclassname::getList([
    "offset" => $offset,
    "limit" => $perPage,
    "orderKey" => ["name", "date"],
    "order" => ["asc","desc"]
]);
 
// with random order
$items = new DataObject\PhoneProduct\Listing();
$items->setOrderKey("RAND()", false);
 
foreach ($items as $item) {
    echo $item . "<br />"; // output the path of the object
}
 
// with subselect in order
$items = new DataObject\PhoneProduct\Listing();
$items->setOrderKey("(SELECT id FROM sometable GROUP BY someField)", false);
```

### Using prepared statement placeholders and variables
The syntax is similar to that from the Zend Framework described
[here](https://framework.zend.com/manual/1.12/en/zend.db.adapter.html#zend.db.adapter.select.fetchall).

```php
<?php

use \Pimcore\Model\DataObject;

$entries = new DataObject\Myclassname\Listing();
$entries->setCondition("name LIKE ?", "%bernie%");
$entries->load();
 
foreach($entries as $entry) {...}
 
// using more variables / placeholders
$entries = new DataObject\Myclassname\Listing();
$entries->setCondition("name LIKE ? AND date > ?", ["%bernie%", time()]);
$entries->load();
 
foreach($entries as $entry) {...}
 
 
// using named placeholders (recommended)
$entries = new DataObject\Myclassname\Listing();
$entries->setCondition("name LIKE :name AND date > :date", ["name" => "%bernie%", "date" => time()]);
$entries->load();
```

### Conditions on localized fields
Following code will only search the EN value of the field `name`.
```php
<?php

$entries = new \Pimcore\Model\DataObject\Myclassname\Listing();
$entries->setLocale("en");
$entries->setCondition("name LIKE :name", ["name" => "%term%"]); // name is a field inside a localized field container
```

##### Disable Localized Fields in listings
Sometimes you don't want to have localized data on the listings (condition & order by). For this particular case you
can disable localized fields on your listing (the objects in the list will still include the localized fields).
Conditions and order by statements on localized fields are then not possible anymore.

```php
<?php

$entries = new \Pimcore\Model\DataObject\Myclassname\Listing();
$entries->setIgnoreLocalizedFields(true);
$entries->load();
```

### Get Objects matching a value of a property
Often it's very useful to get a listing of objects or a single object where a property is matching exactly one value.
This is especially useful to get an object matching a foreign key, or get a list of objects with only one condition.

```php
$result = DataObject\ClassName::getByMyfieldname($value, ['limit' => $limit, 'offset' => $offset]);

// or object variants matching a value
$result = DataObject\ClassName::getByMyfieldname($value, ['limit' => $limit, 'offset' => $offset, 'objectTypes' => [DataObject::OBJECT_TYPE_VARIANT]]);

// or for localized fields
$result = DataObject\ClassName::getByMyfieldname($value, ['locale' => $locale, 'limit' => $limit, 'offset' => $offset]);

// or object variants matching a value in localized fields
$result = DataObject\ClassName::getByMyfieldname($value, ['locale' => $locale, 'limit' => $limit, 'offset' => $offset, 'objectTypes' => [DataObject::OBJECT_TYPE_VARIANT]]);

// or object variants and objects matching a value in localized fields
$result = DataObject\ClassName::getByMyfieldname($value, ['locale' => $locale, 'limit' => $limit, 'offset' => $offset, 'objectTypes' => [DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT]]);
```
If you set no limit, a list object containing all matching objects are returned. If the limit is set to 1
the first matching object is returned directly (without listing). Only published objects are return.

Alternatively you can pass an array as second parameter which will be applied to the object listing.
```php
$result = DataObject\ClassName::getByMyfieldname($value, ['limit' => 1,'unpublished' => true]);
```

##### Examples

```php
<?php

use \Pimcore\Model\DataObject;

// get a list of cities in Austria
$list = DataObject\City::getByCountry("AT");
foreach ($list as $city) {
    // do something with the cities
    $city->getZip();
    ...
}
 
 
// get a city by zip
$city = DataObject\City::getByZip(5020, 1);
$city->getZip(); // do something with the city
 
 
 
// get the first 10 cities in Austria
$list = DataObject\City::getByCountry("AT", 10);
foreach ($list as $city) {
    // do something with the cities
    $city->getZip();
}
```

### Get an Object List/Object by Localized Fields

```php
$list = DataObject\News::getByLocalizedfields($fieldName, $value, $locale, $limit | array('limit' => $limit, 'offset' => $offset, 'unpublished' => $unpublished));

// or
$list = DataObject\News::getByFieldName($value, $locale, $limit | array('limit' => $limit, 'offset' => $offset, 'unpublished' => $unpublished));
```


##### Examples

```php
<?php

use \Pimcore\Model\DataObject;

// get a list of cities in Austria by localized field using default locale
$list = DataObject\City::getByLocalizedfields("country", "Österreich");
// or
$list = DataObject\City::getByCountry("Österreich");
 
// get a city by localized name using default locale
$city = DataObject\City::getByLocalizedfields("city", "Wels", null, 1);
// or
$city = DataObject\City::getByLocalizedfields("city", "Wels", null, ['limit' => 1]);
// or
$city = DataObject\City::getByCity("Wels", null, 1);
 
// get the first 10 cities in Austria by localized field using default locale
$list = DataObject\City::getByLocalizedfields("country", "Österreich", null, 10);
// or
$list = DataObject\City::getByCountry("Österreich", null, 10);
  
// get the first 10 cities in Austria by localized field "de" locale
$list = DataObject\City::getByLocalizedfields("country", "Österreich", "de", 10);
// or
$list = DataObject\City::getByCountry("Österreich", "de", 10);
 
//get a country by localized name in english
$country = DataObject\Country::getByLocalizedfields("name", "Austria", "en", 1);
// or
$country = DataObject\Country::getByName("Austria", "en", 1);
```


### Get an Object List including unpublished Objects
Normally object lists only give published objects. This can be changed by setting a lists `unpublished` property to `true`.

```php
<?php

$list = \Pimcore\Model\DataObject\News::getList(["unpublished" => true]);

//or 

$list = new \Pimcore\Model\DataObject\News\Listing();
$list->setUnpublished(true);
$list->load();
```

Sometimes, by default all unpublished objects are returned even if `setUnpublished` is set to `false`. It is the case when working on the admin side (plug-in for instance).
You can switch globally the behaviour (it will bypass `setUnpublished` setting), using the following static method:

```php
<?php

// revert to the default API behaviour, and setUnpublished can be used as usually
\Pimcore\Model\DataObject::setHideUnpublished(true);

// force to return all objects including unpublished ones, even if setUnpublished is set to false
\Pimcore\Model\DataObject::setHideUnpublished(false);
```

### Filter Objects by attributes from Field Collections
To filter objects by attributes from field collections, you can use following syntax
(Both code snippets result in the same object listing).

```php
$list = new DataObject\Collectiontest\Listing();
$list->addFieldCollection("MyCollection", "collection");
$list->addFieldCollection("MyCollection");
$list->setCondition("`MyCollection~collection`.myinput = 'hugo' AND `MyCollection`.myinput = 'testinput'");
```

```php
<?php

$list = \Pimcore\Model\DataObject\Collectiontest::getList([
   "fieldCollections" => [
      ["type" => "MyCollection", "fieldname" => "collection"],
      ["type" => "MyCollection"]
   ],
   "condition" => "`MyCollection~collection`.myinput = 'hugo' AND `MyCollection`.myinput = 'testinput'"
]);
```

You can add field collections to a listing object by specifying the type of the field collection and optionally the
fieldname. The fieldname is the fieldname of the field collection in the class definition of the current object.
Once field collections are added to an object listing, you can access attributes of field collections in the condition
of the object listing. The syntax is as shown in the examples above `FIELDCOLLECTIONTYPE~FIELDNAME.ATTRIBUTE_OF_FIELDCOLLECTION`,
or if you have not specified a fieldname `FIELDCOLLECTION.ATTRIBUTE_OF_FIELDCOLLECTION`.

The object listing of this example only delivers objects of the type Collectiontest, which have
* a Fieldcollection of the type `MyCollection` and the value `testinput` in the attribute `myinput` and
* a Fieldcollection in the field `collection` of the type `MyCollection` and the value `hugo` in the attribute `myinput`.


<a name="zendPaginatorListing">&nbsp;</a>

### Working with Knp\Component\Pager\Paginator

##### Action
```php
public function testAction(Request $request, \Knp\Component\Pager\PaginatorInterface $paginator): Response
{
    $list = new DataObject\Simple\Listing();
    $list->setOrderKey("name");
    $list->setOrder("asc");
 
    $paginator = $paginator->paginate(
        $list,
        $request->query->getInt('page', 1),
        10
    );

    return $this->render('Test/Test.html.twig', [
        'paginator' => $paginator,
        'paginationVariables' => $paginator->getPaginationData()
    ]);
}
```

##### View
```twig
{% for item in paginator %}
    <h2>{{ item.name }}</h2>
{% endfor %}
<br />
 
{% include 'includes/pagination.html.twig' %}
```

##### Partial Script (`includes/pagination.html.twig`)
```twig
<nav aria-label="Pagination">
    <ul class="pagination justify-content-center">
        {%  if(paginationVariables.previous is defined) %}
            <li class="page-item">
                <a class="page-link prev" href="{{  pimcore_url({'page': paginationVariables.previous}) }}" aria-label="Previous">
                    <span aria-hidden="true"></span>
                </a>
            </li>
        {%  endif %}

        {%  for page in paginationVariables.pagesInRange %}

            {%  if(paginationVariables.current == page) %}

                <li class="page-item active" aria-current="page">
                                  <span class="page-link">
                                    {{  page }}
                                    <span class="sr-only">(current)</span>
                                  </span>
                </li>

            {%  else %}
                <li class="page-item"><a class="page-link" href="{{  pimcore_url({'page': page}) }}">{{ page }}</a></li>
            {%  endif %}

        {% endfor %}

        {%  if(paginationVariables.next is defined) %}
            <li class="page-item">
                <a class="page-link next" href="{{  pimcore_url({'page': paginationVariables.next}) }}" aria-label="Next">
                    <span class="flip" aria-hidden="true"></span>
                </a>
            </li>
        {%  endif %}
    </ul>
</nav>
```

### Access and modify internal object list query

It is possible to access and modify the internal query from every object listing. The internal query is based
on `\Doctrine\DBAL\Query\QueryBuilder`.
```php
<?php
// This example lists all cars that have been sold.

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Model\DataObject\Car\Listing;

$list = new Listing();

$list->onCreateQueryBuilder(
    function (QueryBuilder $queryBuilder) {
        $queryBuilder->join(
            'object_localized_CAR_en',
            'object_query_EF_OSOI',
            'onlineOrderItem',
            'onlineOrderItem.product__id = object_localized_CAR_en.id'
        );
    }
);
```

### Debugging the Object List Query

You can access and print the internal query which is based on `\Doctrine\DBAL\Query\QueryBuilder` to debug your conditions like this:

```php
<?php

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Model\DataObject\Car\Listing;

$list = new Listing();

$list->onCreateQueryBuilder(
    function (QueryBuilder $queryBuilder) {
        // echo query
        echo $queryBuilder->getSQL();
    }
);
```
