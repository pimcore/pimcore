# Object Data Types

The entire list of data types is indicated below:

### [Text Datatypes](./95_Text_Types.md)

| Name                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| input                    | text input field                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| textarea                 | textarea                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| wysiwyg                  | text area with formatting options through a WYSIWYG editor                                                                                                                                                                                                                                                                                                                                                                                                                      |
| password                 | password field                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |


### [Number Datatypes](./55_Number_Types.md)

| Name                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| numeric                  | spinner field for number input                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| slider                   | number input with slider widget (min - max slider)                                                                                                                                                                                                                                                                                                                                                                                                                              |
| quantity value           | number input with an additional unit. available units can be configured centrally.  |


### [Date Datatypes](./25_Date_Types.md)

| Name                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| date                     | calendar date selector                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| date & time              | calendar date selector + combo box for time                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| time                     | combo box for time                                                                                                                                                                                                                                                                                                                                                                                                                                     |


### [Select Datatypes](./80_Select_Types.md)

| Name                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
|--------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| select                   | combo box                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| user                     | combo box to select from all existing Pimcore users (available since build 716) <br/><br/>In the user settings the object dependencies of each user are shown in the second tab panel.<br/>All objects which reference the selected user are listed in a grid view.<br/><br/>If one needs to find out which objects hold a reference to a specific user, the `Pimcore\\Tool\\Admin::getObjectsReferencingUser($userId)` method can be used to find all referencing objects. |
| country                  | combo box with predefined country list                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| language                 | combo box with predefined language list                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| multiselect              | combo box with multiple select                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| countries                | combo box with multiple select and predefined country list                                                                                                                                                                                                                                                                                                                                                                                                                  |
| languages                | combo box with multiple select and combo box with multiple select and predefined language                                                                                                                                                                                                                                                                                                                                                                                   |

### [Select Options](./77_Select_Options.md)

| Name           | Description                                    |
|----------------|------------------------------------------------|
| select options | Manage select options for (multi)select fields |

### [Dynamic Select Datatypes](./30_Dynamic_Select_Types.md)

| Name                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| dynamic select           | combo box                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |


### [Relational Datatypes](./70_Relation_Types.md)

| Name                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Many-To-One Relation     | reference to a Pimcore document, object or asset                                                                                                                                                                                                                                                                                                                                                                                                                                |
| Many-To-Many Relation    | collection of references to Pimcore documents, objects, assets                                                                                                                                                                                                                                                                                                                                                                                                                  |
| Advanced Many-To-Many Relation | collection of references to Pimcore documents, objects, assets with additional metadata on the relation                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Many-To-Many Object Relation | collection of Pimcore object references                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| Advanced Many-To-One Object Relation | collection of Pimcore object references with additional metadata on the relation                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| [Reverse Object Relation](75_Reverse_Object_Relation_Type.md) | collection of Pimcore object references which are owned by a different object                                                                                                                                                                                                                                                                                                                                |


### Structured Datatypes

| Name                                                | Description                                                       |
|-----------------------------------------------------|-------------------------------------------------------------------|
| [block](./05_Blocks.md)                             | repeatable block of attributes within an object                   |
| [classificationstore](./15_Classification_Store.md) | advanced store for classification systems like ETIM, ecl@ss, etc. |
| [table](./90_Table.md)                              | table input                                                       |
| [structuredtable](./85_Structured_Table.md)         | table with predefined rows and columns                            |
| [fieldcollections](./35_Fieldcollections.md)        | A collection of fields that can be added to the object            |
| [objectbricks](./60_Object_Bricks.md)               | Bricks of attributes, that can be added to objects                |
| [localizedfields](./50_Localized_Fields.md)         | Set of attributes that can be translated                          |

### [Geographic Datatypes](./40_Geographic_Types.md)

| Name                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| geopoint                 | maps widget to find longitude/latitude                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| geobounds                | maps widget to define geographical bounds                                                                                                                                                                                                                                                                                                                                                                                                                                |
| geopolygon               | maps widget to define a geographical area                                                                                                                                                                                                                                                                                                                                                                                                                                |
| geopolyline              | maps widget to define a geographical path                                                                                                                                                                                                                                                                                                                                                                                                                                |


### Other Datatypes

| Name                                                                                   | Description                                                                                            |
|----------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------|
| [image](./45_Image_Types.md)                                                           | drop area & preview for a Pimcore image asset                                                          |
| [externalimage](./45_Image_Types.md)                                                   | relation to an image that is not stored in Pimcore                                                     |
| [imageadvanced](./45_Image_Types.md#image-advanced-supporting-hotspotsmarkerscropping) | drop area & preview for a Pimcore image asset with additional features for markers, hotspots, cropping |
| [imagegallery](./45_Image_Types.md)                                                    | collection of `Image Advanced` images                                                                  |          
| [video](./98_Video_Type.md)                                                            | drop area & preview for a Pimcore video asset                                                          |
| [checkbox](./65_Others.md#checkbox)                                                    | checkbox                                                                                               |
| [link](./65_Others.md#link)                                                            | link selector with link target                                                                         |
| [calculatedvalue](./10_Calculated_Value_Type.md)                                       | datatype for calculated values - calculation can be defined with a PHP class                           |


### CRM Datatypes

| Name                      | Description                                                                           |
|---------------------------|---------------------------------------------------------------------------------------|
| firstname                 | typed input field for firstname                                                       |
| lastname                  | typed input field for lastname                                                        |
| email                     | typed input field for email including validation                                      |
| gender                    | typed and prefilled select for gender                                                 |
| persona                   | typed selectbox for personas defined within Pimcore                                   |
| personas                  | typed selectbox with multiselect for personas defined within Pimcore                  |
| [consent](./20_Consent.md) | store consent of user for something, e.g. consent for direct marketing mailing        |


The following datatypes are only available if the PimcoreNewsletterBundle is enabled and installed:

| Name                      | Description                                                                           |
|---------------------------|---------------------------------------------------------------------------------------|
| newsletteractive          | typed checkbox if newsletter is active                                                |
| newsletterconfirmed       | typed checkbox if newsletter is confirmed                                             |


### General Aspects

All data types are wrapped in an object derived from `Pimcore\Model\DataObject\ClassDefinition\Data`. 
These data type objects provide getters and setters and they define the Description in the frontend. 
Data type objects are displayed in the first column of the table above. 
The second column indicates the underlying data type class and the third column outlines the Description used in Pimcore 
to fill in, edit and display data objects.


Besides the `name`, which is the name of the object's property and the `title`, which is shown in the GUI, an 
object field has the general configuration options listed below. The title can be translated for different system 
languages. Please see the article about Translations to find out how to add object field translations.

* `mandatory`: Makes the field mandatory and does not allow saving the object when it is empty
* `not editable`: Does not allow a change of this field's value in Pimcore backend (data change can only be done 
  programmatically)
* `invisible`: The field is not visible in Pimcore
* `visible in grid view`: Determines if the field's data column is shown in the object grid view, or hidden 
  (meaning it has to be activated manually)
* `visible in search result`: Determines if the field's data column is shown in the search results grid, or hidden 
  (meaning it has to be activated manually)
* `indexed`: puts an index on this column in the database
* `unique`: If checked, the value has to be unique across all objects of this class. Note that only works on top level attributes and not on nested stuff inside localized fields etc. Beware that this does not add a database index to the query table which `Listing` classes use.
* Moreover, each data field can have a `tooltip`, which is shown when the mouse hovers over the input field.

![Data Field Settings](../../../img/classes-datatypes1.jpg)
![Data Field Settings](../../../img/classes-datatypes2.jpg)


The `layout settings` allow to apply custom CSS to any object field.


![Data Field Settings](../../../img/classes-datatypes3.jpg)


> **WARNING**  
> Please note that renaming a field means the loss of data from the field in all objects using this class.

See sub-pages of this page for detail documentation of different data types. 

### Default values

For datatypes which support default values (currently these are Input, Date, Datetime, Numeric, Checkbox, Select and Quantity Value) you can either specify a fixed default value or you can specify a default value generator service or class which can generate a dynamic default value. 

The data is persisted according to the following rules.

1. ***No [inheritance](../05_Class_Settings/25_Inheritance.md)***: default value is persisted to store/query table on create
2. ***With inheritance and NO parent value***: default value is persisted to store/query table on create
3. ***With inheritance and existing parent value***: no value is persisted to store table, 
inherited value is persisted to query table, inheritance is active

A default value generator is a class which implements `\Pimcore\Model\DataObject\ClassDefinition\DefaultValueGeneratorInterface`. This class can generate a value based on the current data of an object.
Have a look at [Calculated Value](./10_Calculated_Value_Type.md) for an overview of contextual information.

If a default value generator is defined then it has a higher priority than a configured static default value.

The decisions are made in the following order:
1. default value generator. if defined the process stops here
2. parent value if inheritance is enabled
3. fixed default value
