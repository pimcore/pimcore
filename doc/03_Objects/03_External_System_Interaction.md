---
title: "External System Interaction"
description: "Importing and exporting data object data with external systems using the PHP API."
---

# External System Interaction

Whenever interaction with other systems is required, data objects are the vital components of data exchange. 
Create, fill, and list Pimcore data objects programmatically to implement batch imports and exports
with only a few lines of code.

Therefore the recommended way of interacting with external systems is using the PHP API of Pimcore and create a 
interaction layer with your custom PHP code. This interaction layer can be within a [Pimcore bundle](../10_Extending_Pimcore/04_Pimcore_Bundle_Developers_Guide/README.md), a library component,
 a custom web service, a [CLI Command](../08_Development_Details/09_CLI_and_Pimcore_Console.md) or just a simple CLI script - you have the full flexibility.

The following examples use plain CLI scripts. For production use, prefer [CLI Commands](../08_Development_Details/09_CLI_and_Pimcore_Console.md).

## Import
The following example indicates the creation of a new object of the class `myclass`. 
Put the following script into the file `/bin/example.php` (or any other PHP file).

```php
<?php

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;

#[AsCommand(
    name: 'app:awesome',
    description: 'Awesome command'
)]
class AwesomeCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //create single object
        $object = new DataObject\Myclass();
        $object->setKey(1);
        $object->setParentId(1);
        $object->setPublished(true);
        $object->setMyattribute("This is a test");
        $object->save();


        // or create multiple objects
        for ($i = 0; $i < 60; $i++) {
            $o = new DataObject\News();
            $o->setKey(uniqid() . "-" . $i);
            $o->setParentId(1);
            $o->setPublished(true);
            $o->save();

            $output->writeln("Created object " . $o->getFullPath() . "\n");
        }
    }
}
```

With a few lines of code, implement importer scripts to populate data objects. See
Pimcore\Console for integrating custom CLI scripts into the [Pimcore console](../08_Development_Details/09_CLI_and_Pimcore_Console.md).

## Export
Export data objects programmatically, similar to imports, by using object listings with a few lines of code.
 
 ```php
 
 $file = fopen("export.csv","w");
 
 $entries = new DataObject\Myclassname\Listing();
 $entries->setCondition("name LIKE ?", "%bernie%");
  
 foreach($entries as $entry) { 
    fputcsv($file, [
        'id' => $entry->getId(),
        'name' => $entry->getName()
    ]);
 }
 
 fclose($file);
 
 ```

For simple CSV exports, Pimcore Studio provides a CSV export functionality.
 
 
## Memory Issues
If you process or create a large number of objects, call the Pimcore garbage collector after several cycles to prevent memory issues.

```php
// call this static method
\Pimcore::collectGarbage();
```
Or use `RuntimeCache::disable()` before iterating many objects to avoid excessive memory usage.

**WARNING:** This will flush the entire internal registry!

To avoid this, you can pass an array with keys (indexes) which should stay in the registry eg. 

```php
\Pimcore::collectGarbage(["myImportantKey", "myConfig"]);

// You can also add items to the static list of globally protected keys by passing them to
$longRunningHelper = \Pimcore::getContainer()->get(\Pimcore\Helper\LongRunningHelper::class);
$longRunningHelper->addPimcoreRuntimeCacheProtectedItems(["myVeryImportantKey", "mySuperImportKey", "..."]);

// This list is maintained as long as the process exists. You can remove protected keys again by calling
$longRunningHelper->removePimcoreRuntimeCacheProtectedItems(["myVeryImportantKey", "mySuperImportKey", "..."]);

```
You can pass in a string instead of an array if you only want to supply a single key.

## Temporary files

Temporary files which get created during processing are usually deleted when the current process finishes. In some cases this may be a problem when thousands of temporary files get created within a process. If you want to clear temporary files before the script ends, you can call
```php
\Pimcore::deleteTemporaryFiles();
```
