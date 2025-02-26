# Table

The table widget can hold structured data in the form of an array. 
The input widget for table data is a table with variable rows and columns as shown below.

![Table preview](../../../img/Objects_Table_preview.png)

The data is stored in an array, which needs to be flattened for storage in the database. 
For this purpose columns are separated with a "|" and rows are distinguished with line breaks. 
The database field for a table is a TEXT column. 
For example, the data shown in the screen above would be stored as:

```
one|two|three
four|five|six
seven|eight|nine
```

![Table settings](../../../img/Objects_Table_settings.png)

The input widget can be preconfigured with default data or a fixed amount of rows and columns. 
The default amount of rows and columns, as well as the default data, can be changed later when the data is entered. It's possible to prevent adding/removing additional rows/columns by setting the "Rows fixed"/"Cols fixed" checkbox. If this is set to fixed the add and delete button for rows and columns will disappear.

In order to set table data programmatically, an array needs to be passed to the setter as shown in the code snippet below:

```php
$object->setTable([
    ["one", "two", "three"], 
    ["four", "five", "six"], 
    ["seven", "eight", "nine"]
]);
```


## Using copy and paste feature in an object using table data type

A copy and paste feature is available to be able to fill easily the table in an object from an Excel sheet for instance:

![Copy and paste feature](../../../img/Objects_Table_copyandpaste.png)

It is possible to copy directly data in the OS clipboard from Excel:

![Copy and paste feature](../../../img/Objects_Table_excel.png)

And after pasting data will be formatted keeping Excel structure:

![Copy and paste feature](../../../img/Objects_Table_copyandpasteresult.png)

You can paste any data (from text files, etc.), separator must be tabulation.
