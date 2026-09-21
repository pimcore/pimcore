---
title: Select Datatypes
description: "Seven select widget variants including country, language, and user selects."
---

# Select Datatypes

Seven select widgets are available. Except for the multiselect variants, all display as an input
field with a drop-down list of options. The database column type is VARCHAR for all select data types, TEXT for all 
multiselection types. The configured value (not the display value!) is stored in the database. In the case of 
multiselect, values are stored as a comma separated list. 

For select and multiselect the options can be defined with a value and display value in the class definition
or retrieve options from different sources:
* [Select Options](./77_Select_Options.md)
* [Class / Service](./30_Dynamic_Select_Types.md)

Country and language have fixed option values. For the language field the options can be limited to available system 
languages. The country and language select field are also available as multi select fields.
The user field has fixed values as well. It selects a user from all available Pimcore system users,
associating a system user with an object.

### Working with select data types via API

Pass the value to the setter to set a select field programmatically. For multiselect fields, pass an array of values.

```php
$object->setSelect("1");
$object->setMultiselect(["1","2"]);
$object->setLanguage("en");
$object->setCountry("AU");
$object->setUser(1);
$object->save();
```

Retrieve available options by getting the field definition:

```php
$fd = $object->getClass()->getFieldDefinition("multiselect");
$options = $fd->getOptions();
```

For a select field inside an ObjectBrick, get the field definition of the brick:

```php
$fd = $brick->getDefinition()->getFieldDefinition("multiselect");
$options = $fd->getOptions();
```


The display name values can be obtained as follows:

```php
use Pimcore\Model\DataObject;

...

$o = DataObject::getById(49);

// for a (single) select data field
$valuesSingle = DataObject\Service::getOptionsForSelectField($o, "select"); 
$selectedValueSingle = $valuesSingle[$o->getSelect()];

// for a multiselect data field
$multiSelectFieldValues = DataObject\Service::getOptionsForMultiSelectField($o, "multiSelectField");
$selectedValues = array_map(
    static fn($value) => $multiSelectFieldValues[$value],
    $o->getMultiSelectField()
); // For PHP >= 7.4

$selectedValues = array_map(
    static function($value) use ($multiSelectFieldValues) {
        return $multiSelectFieldValues[$value];
    }, $o->getMultiSelectField()
); // For PHP <= 7.3
```

To show the selected option's display name in a Twig template, first put the option values as a view variable inside the controller action:

```php
use Pimcore\Model\DataObject;

...

$product = DataObject::getById(49);
// for a select data field
$colorOptions = DataObject\Service::getOptionsForSelectField($product, "color");

return $this->render('foo/bar.html.twig', ['colorOptions' => $colorOptions]);
```

Then you can use it in the Twig view:

```twig
{{ colorOptions[product.color] }}
```
