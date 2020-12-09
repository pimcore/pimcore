# Select Datatypes

There are 7 different select widgets available. Except the Multiselect widgets, all of them are portrayed by an input 
field with a drop-down list of options. The database column type is VARCHAR for all select data types, TEXT for all 
multiselection types. The configured value (not the display value!) is stored in the database. In the case of 
multiselect, values are stored as a comma separated list. 

![Select Field](../../../img/classes-datatypes-select1.png)

For select and multiselect the options can be defined with a value and display value in the class definition: 

![Select Field](../../../img/classes-datatypes-select2.png)

Country and language have fixed option values. For the language field the options can be limited to available system 
languages. The country and language select field are also available as multi select fields.
The user field has fixed values as well. It allows to select a user from all available Pimcore system users. 
Thereby a system user can be associated an object. 

For multiselect types there is extra configuration to set render type (list or tags)

![Select Field](../../../img/multiselect_rendertype.png)

Object Editor view for List Render Type:

![Select Field](../../../img/multiselect_view_list.png)

Object Editor view for Tags Render Type:

![Select Field](../../../img/multiselect_view_tags.png)
### Working with select data types via API

In order to set a select field's value programmatically, the value is simply passed to the setter. To set the values 
of a multiselect field, an array of values is passed to the setter.

```php
$object->setSelect("1");
$object->setMultiselect(["1","2"]);
$object->setLanguage("en");
$object->setCountry("AU");
$object->setUser(1);
$object->save();
```

If one needs to find out what options are available for a select field. This can be done by getting the field definition 
as follows:

```php
$fd = $object->getClass()->getFieldDefinition("multiselect");
$options = $fd->getOptions();
```

If one needs to find out what options are available for a select field inside an ObjectBrick. This can also be done by 
getting the field definition of the brick as follows:

```php
$fd = $brick->getDefinition()->getFieldDefinition("multiselect");
$options = $fd->getOptions();
```


The display name values can be obtained as follows:

```php
use Pimcore\Model\DataObject;

...

$o = DataObject\AbstractObject::getById(49);

// for a (single) select data field
$valuesSingle = DataObject\Service::getOptionsForSelectField($o, "select"); 
$selectedValueSingle = $valuesSingle[$o->getSelect()];

// for a multiselect data field
$valuesMulti = DataObject\Service::getOptionsForMultiSelectField($o, "multiselect");
$selectedValueMulti = $valuesMulti[$o->getMultiselect()];
```

To show the selected option's display name in a Twig template, first put the option values as a view variable inside the controller action:

```php
use Pimcore\Model\DataObject;

...

$product = DataObject\AbstractObject::getById(49);
// for a select data field
$this->view->colorOptions = DataObject\Service::getOptionsForSelectField($product, "color");
```

Then you can use it in the Twig view:

```twig
{{ colorOptions[product.color] }}
```
