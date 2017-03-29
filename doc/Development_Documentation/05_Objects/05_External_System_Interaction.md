# External System Interaction

Whenever interaction with other systems is required, data objects are the vital components of data exchange. 
Pimcore data objects can be created, filled and listed programmatically in order to realize batch imports and exports 
with only very few lines of code.

Therefore the recommended way of interacting with external systems is using the PHP API of Pimcore and create a 
interaction layer with your custom PHP code. This interaction layer can be within a [Pimcore plugin](../10_Extending_Pimcore/13_Bundle_Developers_Guide/README.md), a library component,
 a custom web service, a [CLI Command](../09_Development_Tools_and_Details/11_Console_CLI.md) or just a simple CLI script - you have the full flexibility.

To keep things simple, we're using simple CLI scripts in the following example, although we're recommending the use of [CLI Commands](../09_Development_Tools_and_Details/11_Console_CLI.md).

## Import
The following example indicates the creation of a new object of the class `myclass`. 
Put the following script into the file `/app/Resources/cli/example.php` (or any other PHP file).
```php
<?php 

include_once(__DIR__ . "/../../../pimcore/config/startup_cli.php");

use \Pimcore\Model\Object;

//create single object 

$object = new Object\Myclass();
$object->setKey(1);
$object->setParentId(1);
$object->setPublished(true);

$object->setMyattribute("This is a test");

$object->save();


// or create multiple objects
for ($i = 0; $i < 60; $i++) {
    $o = new Object\News();
    $o->setKey(uniqid() . "-" . $i);
    $o->setParentId(1);
    $o->setPublished(true);
    $o->save();
    echo("Created object " . $o->getFullPath() . "\n");
}

```
Thus, with very few lines of codes importer scripts can be implemented to populate data objects. Please have a look at 
Pimcore\Console how to integrate your custom CLI scripts to the [Pimcore console](../09_Development_Tools_and_Details/11_Console_CLI.md).

## Export
Export of data objects can be achieved programmatically similar to imports by using object listings and writing just a
few lines of code.
 
 ```php
 
 $file = fopen("export.csv","w");
 
 $entries = new Object\Myclassname\Listing();
 $entries->setCondition("name LIKE ?", "%bernie%");
  
 foreach($entries as $entry) { 
    fputcsv($file, [
        'id' => $entry->getId(),
        'name' => $entry->getName()
    ]);
 }
 
 fclose($file);
 
 ```

For simple CSV exports, Pimcore backend interface provides a CSV export functionality, see User Documentation for details.
 
 
## Memory Issues
If you're using / creating very much objects you should call the Pimcore garbage collector after several cycle to 
prevent memory issues

```php
// just call this static method
Pimcore::collectGarbage();
```

**WARNING:** This will flush the entire Zend_Registry!

To avoid this, you can pass an array with keys (indexes) which should stay in the registry eg. 

```php 
Pimcore::collectGarbage(["myImportantKey","myConfig"]);

// You can also add items to the static list of globally protected keys by passing them to
\Pimcore::addToGloballyProtectedItems(["myVeryImportantKey", "mySuperImportKey", "..."]);

// This list is maintained as long as the process exists. You can remove protected keys again by calling
\Pimcore::removeFromGloballyProtectedItems(["myVeryImportantKey", "..."]);

```
You can pass in a string instead of an array if you only want to supply a single key.