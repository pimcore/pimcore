# Object Variants
The best way to show the use and function of object variants is via an use case:

Your goal is to store lots of products in Pimcore. Many of these products are variants of each other, for example a 
yellow t-shirt, a blue t-shirt, a red t-shirt etc. Most of the t-shirts' attributes have the same values and they 
just differ in color and EAN code.

One way to archive this is to make a generic t-shirt object and then create for each variant a child object within the 
tree. This approach works fine, but if you have dozens or even hundreds of variants, your object tree becomes quite 
big and confusing.


This is where object variants come in. Basically, they are just objects which aren't shown in the object tree. In the 
tree, you just create the generic t-shirt. For each variant of this t-shirt, you create an object variant, which is 
not shown in the object tree but in an own tab within the object editor.

There is no other difference between objects and variants, therefore variants support all functionalitites as objects do. 

So, you can create hundreds of object variants without blowing your object tree.

![Object Variants](../../../img/classes-variants.png)

As the normal object grid, the object variant grid supports paging, filtering, hiding of columns and visualization of 
inherited values. So even a big number of variants should be manageable.

## Create and organize Object Variants
To use object variants, they have to be activated in the class definition first. Object variants only make sense, 
if inheritance is activated. Therefore, inheritance is a requirement for object variants.

![Object Variants](../../../img/classes-variants1.png)

Once they are activated, the object editor has an additional tab 'Variants'. There, all variants of the current object 
are shown in a grid. Via buttons object variants can be created, opened and deleted.

![Object Variants](../../../img/classes-variants2.png)


To create object variants via code, just create a normal object, set as parent the generic t-shirt and set the object 
type to `Object_Abstract::OBJECT_TYPE_VARIANT`.

```php

$objectX = new Object\Product();
$objectX->setParent(Object\Product::getById(362603));
$objectX->setKey("variantname");
$objectX->setColor("black");
$objectX->setType(Object\AbstractObject::OBJECT_TYPE_VARIANT);
$objectX->save();
```

## Query Object Variants

#### Get all Object Variants of an object
Getting all variants of an object is quite simple. Just call `getChildren` and pass the wanted object types as an array. 
If only variants should be returned use following line.

```php
$objectX->getChildren([Object\AbstractObject::OBJECT_TYPE_VARIANT]);
```

By default, `getChildren` delivers objects and folders but no variants.



#### Object Variants in Object Lists

Similar to `getChildren`, the object list objects now have an object type property, which defines the object types to 
deliver. Per default objects and folders are delivered. To deliver object variants, use one of the following code 
snippets:

```php
$list = new Object\Product\Listing();
$list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_VARIANT]);
$list->load();

// or


Object\Product::getList([
    "objectTypes" => [Object\AbstractObject::OBJECT_TYPE_VARIANT]
]);
```

If you want regular objects and variants, you should use:

```php
$list = new Object\Product\Listing();
$list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_VARIANT,Object\AbstractObject::OBJECT_TYPE_OBJECT]]);
$list->load();

```
