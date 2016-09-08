# Structure table

## Add structure table to the class

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

## Using structured table with PHP api

In the code, the data of this field can be accessed as shown in the code snippets, below:

```php
/** @var \Pimcore\Model\Object\Data\StructuredTable $structuredData */
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