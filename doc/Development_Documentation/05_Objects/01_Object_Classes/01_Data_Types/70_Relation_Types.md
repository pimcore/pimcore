# Relational Datatypes

## Many-To-One, Many-To-Many and Many-To-Many Object Relation Data Fields 

Many-To-One, Many-To-Many and Many-To-Many Objects are pure relation data types, which means they represent a relation to an other Pimcore 
element (document, asset, object). The Many-To-One and Many-To-Many data types can store relations to any other Pimcore element. 
In the object field definition there is the possibility to configure which types and subtypes of elements are allowed.

The Many-To-Many Object field allows relations to one or more data objects, but no other elements. Therefore the restriction settings for 
objects are limited to object classes.

The width and height of the input widget can be configured in the 
object class settings. For a Many-To-One relation only the width can be configured, since it is represented by a single drop area. 
Lazy Loading is explained further below in the section about relations and lazy loading.


The input widgets for all three relation data types are represented by drop areas, which allow to drag and drop elements 
from the tree on the left to the drop target in the object layout.

In addition to the drag and drop feature, elements can be searched and selected directly from the input widget. In case 
of objects it is even possible to create a new object and select it for the objects widget.
 
![Relation Fields](../../../img/classes-datatypes-relation2.png)


#### Filtering for relations via PHP API
These pure relation types are stored in a separate database table called `object_relations_ID`. In the according 
`object_~ID~` database view, which is used for querying data, the relations fields are summarized as a comma 
separated list of IDs of related elements. Therefore, if one needs to create an object list with a filter condition on a 
relation column this can be achieved as follows:

```php
$relationId = 162;
$list = new \Pimcore\Model\DataObject\Example\Listing();
$list->setCondition("mySingleRelation__id = ".$relationId);
$objects=$list->load();
 
 
$relationId = 345;
$list = new \Pimcore\Model\DataObject\Example\Listing();
$list->setCondition("myManyToManyRelations like '%,object|".$relationId.",%'");
$objects=$list->load();
```

#### Assigning relations via PHP API
In order to set a Many-To-One data field, a single Pimcore element needs to be passed to the setter. With Many-To-Many 
and Many-To-Many Objects, an array of elements is passed to the setter:

```php
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
 
$object = DataObject\AbstractObject::getById(12345);
 
$object->setMyManyToOneField(Document::getById(23));

$object->setMyManyToManyField([
    Asset::getById(350),
    DataObject\AbstractObject::getByPath("/products/testproduct")
]);

$object->setMyManyToManyObjectField([
    DataObject\Product::getById(98),
    DataObject\Product::getById(99)
]);
 
$object->save();
```

#### Deleting relations via PHP API
In order to remove all elements from this object's Many-To-Many field, the setter can be called with null or an array:

```php
$object->setMyManyToManyField([]);
 
//that would have the same result
$object->setMyManyToManyField(null);
```
Internally the setter sets the value to an empty array, regardless if an empty array or null is passed to it.


#### Unpublished relations
Related items that are unpublished are normally not returned. You can disable this behavior like this:
```php
//also include unpublished relations form now on
DataObject\AbstractObject::setHideUnpublished(false);
//get a related object that is either published or unpublished
$relationObject = $relation->getObject();
//return to normal behavior
DataObject\AbstractObject::setHideUnpublished(true);
```


## Advanced Many-To-One Object Relation 
This data type is an extension to the Many-To-One Object data type. To each assigned object additional metadata can be saved. 
The type of the metadata can be text, number, selection or a boolean value.

A restriction of this data type is that only one allowed class is possible. As a result of this restriction, it is 
possible to show data fields of the assigned objects.
Which metadata columns are available and which fields of the assigned objects are shown has to be defined during the 
class definition.

![Advanced Many-To-One Object Relation Configuration](../../../img/classes-datatypes-relation5.png)

The shown class definition results in the following object list in the object editor. The first two columns contain 
`id` and `title` of the assigned object. The other four columns are metadata columns and can be edited within this 
list.

![Advanced Many-To-One Object Relation Field](../../../img/classes-datatypes-relation6.png)

All the other functionality is the same as with the normal objects data type.


#### Access objects with metadata via PHP API
```php
use Pimcore\Model\DataObject;

$object = DataObject\AbstractObject::getById(73585);

//getting list of assigned objects with metadata (array of DataObject\Data\ObjectMetadata)
$objects = $object->getMetadata();

//get first object of list
$relation = $objects[0];

//get relation object
$relationObject = $relation->getObject();

//access meta data via getters (getter = key of metadata column)
$metaText = $relation->getText();
$metaNumber = $relation->getNumber();
$metaSelect = $relation->getSelect();
$metaBool = $relation->getBool();

//setting data via setter
$relation->setText("MetaText2");
$relation->setNumber(5512);
$object->save();
```

#### Save objects with metadata

```php
use Pimcore\Model\DataObject;

//load your object (in this object we save the metadata objects)
$object = DataObject\AbstractObject::getById(73585);

//create a empty array for your metadata objects
$objectArray = [];

//loop throu the objectlist (or array ...) and create object metadata
foreach ($yourObjectsList as $yourObject) {
  
    //create the objectmetadata Object, "yourObject" is the referenced object
    $objectMetadata = new DataObject\Data\ObjectMetadata('metadata', ['text', 'number'],  $yourObject);
    //set into the metadata field (named text) the value "Metadata"
    $objectMetadata->setText('Metadata');
    //set into the metadata field (named Number) the value 23
    $objectMetadata->setNumber(23);

    //add to the empty "objectArray" array
    $objectArray[] = $objectMetadata;
}

//set the metadataArray to your object
$object->setMetadata($objectArray);

// now save all
$object->save();
```


## Advanced Many-To-Many Relation

This datatype is similar to the `Advanced Many-To-One Object Relation` data-type in the way that additional information can be 
added to the relation.

The main difference is that all element types (documents, assets and objects) can be added to the relation list. 
The element types can also be mixed. Essentially, the same rules as for the standard Many-To-Many Relation apply.

The API is nearly identical. However, instead of dealing with an `ObjectMetadata` class you have to do the same stuff 
with `ElementMetadata`.

```php
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;

$referencedElement = Document::getById(123);
$references = [];
$elementMetadata = new DataObject\Data\ElementMetadata('metadata', ['text', 'number'], $referencedElement);

//set into the metadata field (named text) the value "my lovely text"
$elementMetadata->setText('my lovely text');

//set into the metadata field (named Number) the value 23
$elementMetadata->setNumber(23);


$references[] = $elementMetadata;

//set the metadata array to your object
$object->setMetadata($references); 
```


## Lazy Loading

> Note that from 6.5.0 on relations are always lazy loaded. The configuration option has been removed.

Whenever an object is loaded from database or cache, all these related objects are loaded with it. Especially with 
Many-To-Many relations it is easy to produce a huge amount of relations, which makes the object or an object list slow 
in loading.

As a solution to this dilemma, Many-To-Many relational data types can be classified as `lazy loading` 
in the class definition.

![Lazy Loading](../../../img/classes-datatypes-relation3.png)

Attributes which are lazy loaded, are only loaded from the `database/cache` when their getter is called. In the 
example above this would mean, that the Many-To-Many relational data is only loaded when calling `$object->getMyManyToManyField();`.


## Dependencies

There are several object data types which represent a relation to an other Pimcore element. The pure relation types are
* Many-To-One Relation
* Many-To-Many Relation
* Advanced Many-To-Many Relation
* Many-To-Many Object Relation
* Advanced Many-To-One Object Relation

Furthermore, the following data types represent a relation, but they are not reflected in the `object_relation_..` 
tables, since they are by some means special and not pure relations. (One could argue that the image is, but for now it 
is not classified as a pure relation type)

* Image
* Link
* Wysiwyg

All of these relations produce a dependency. In other words, the dependent element is shown in both element's dependencies 
tab and Pimcore issues a warning when deleting an element which has dependencies.

![Dependencies](../../../img/classes-datatypes-relation4.png)
