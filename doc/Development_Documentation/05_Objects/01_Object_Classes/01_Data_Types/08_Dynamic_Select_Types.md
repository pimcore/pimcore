# Select Types with Dynamic Options

**Experimental, subject to change without notice!!!**

For the select & multiselect datatype you can specify a dynamic options provider class. 
This allows you to generate a list of valid options on-the-fly instead of using a static list.
The select datatype also allows you to define the default option at runtime.

You can also add some additional static data which will be passed to the data provider.

![Select Field](../../../img/dynselect1.png)

Provide a options provider class which implements at least the getOptions method as shown below.
For the select datatype you can also provide a getDefaultValue implementation.

```php
<?php

namespace Website;

use Pimcore\Model\Object\ClassDefinition\Data;


class OptionsProvider
{
    /**
     * @param $context array
     * @param $fieldDefinition Data
     * @return array
     */
    public static function getOptions($context, $fieldDefinition) {
        $object = $context["object"];
        $fieldname = "id: " . ($object ? $object->getId() : "unknown") . " - " .$context["fieldname"];
        $result = array(

                array("key" => $fieldname .' == A', "value" => 2),
                array("key" => $fieldname .' == C', "value" => 4),
                array("key" => $fieldname .' == F', "value" => 5)

        );
        return $result;
    }

    /**
     * @param $context array
     * @param $fieldDefinition Data
     * @return mixed
     */
    public static function getDefaultValue($context, $fieldDefinition) {
        return 4;
    }

}
```

This will generate the following options.

![Select Field](../../../img/dynselect2.png)

## Context Information for the Provider Class

Note that depending the use case not all of the infos will be available.
Especially the existence of the object paramater cannot be guaranteed because the provider class will also be called when a class is saved or if you programmatically call $class->getFieldDefinitions().
Layout definition calls can be distinguished from other ones by checking if the `purpose` parameter is set to `layout`

#### Object (top-level)

| Name | Description |
| --- | ---- |
| object | the `"object"` |
| fieldname | the name of the select field (e.g. `dynSelect`) |


#### Localizedfields

| Name | Description |
| --- | ---- |
| ownerType | `"localizedfield"` |
| ownerName | the name of the localized field ("localizedfields") |
| object | the `"object"` |
| fieldname | the name of the select field (e.g. `dynSelect`) |


#### Objectbricks

| Name | Description |
| --- | ---- |
| containerType | `"objectbrick"` |
| containerKey | the type of the object brick |
| outerFieldname | the object's object brick attribute |
| object | the `"object"` |
| fieldName | the name of the attribute inside the object brick |

#### Fieldcollections

| Name | Description |
| --- | ---- |
| containerType | `"fieldcollection"` |
| containerKey | the type of the fieldcollection |
| subContainerType | sub container type (e.g. localized field inside a field collection) |
| outerFieldname | the object's field collection attribute |
| object | the `"object"` |
| fieldName | the name of the attribute inside the fieldcollection |


#### Classification Store

| Name | Description |
| --- | ---- |
| ownerType | `"classificationstore"` |
| ownerName | the name of the classificationstore attribute |
| fieldname | the name of the attribute inside the fieldcollection |
| groupId   | group id |
| keyId     | key id |
| keyDefinition | the fielddefinition of the classificationstore attribute |

