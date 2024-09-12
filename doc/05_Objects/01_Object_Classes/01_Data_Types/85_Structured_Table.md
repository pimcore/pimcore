# Structured Table

## Add Structured Table to the Class

Similar to the table widget, the structured table can hold structured data. 
But there are a few fundamental differences:

* The rows and columns are predefined and named.
* The data type per column can be defined. Possible data types are text, number and boolean.
* The data of a structured table can be accessed via getters and setters and is stored in a structured way in the database.

You can add structured table component in a class definition:

![Add structured table component to the class](../../../img/Objects_Structured_Table_add_data_component.png)

To define the table, you have to specify rows and columns headers which would be used to fill the structured table content.

![Structured table definition](../../../img/Objects_Structured_Table_definition.png)

Now, you can use the table in your object, like below:

![Edit object with structured table](../../../img/Objects_Structured_Table_use.png)

## Storage of Structured tables

For each row and column combination a new column in table of the structured table containing class will be stored.

So it will be a clear restriction not to use this type of structuring data in case of a bigger number of data rows or columns. By using this design the maximum number of columns per table should be taken into consideration because it could be a technical restriction for it.

## Using structured table with PHP API

In the code, the data of this field can be accessed as shown in the code snippets, below:

```php
/** @var \Pimcore\Model\DataObject\Data\StructuredTable $structuredData */
$structuredData = $object->getAdditionalinfo();

//Returns an associated array of row CommunityEdition with all columns
$structuredData->getCommunityedition();

//Returns an associated array of row CommunityEdition with all columns
$structuredData->getCommunityedition__support();

//Delivers an associated array of row CommunityEdition with all columns
$structuredData->setCommunityedition__support("Forum");

//Alternative way of setting data to a structured table
$data = [];
$data['communityedition']['opensource'] = true;
$structuredData->setData($data);
```
