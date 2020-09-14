# External System Interaction

Whenever interaction with other systems is required, data objects are the vital components of data exchange. 
Pimcore data objects can be created, filled and listed programmatically in order to realize batch imports and exports 
with only very few lines of code.

Therefore the recommended way of interacting with external systems is using the PHP API of Pimcore and create a 
interaction layer with your custom PHP code. This interaction layer can be within a [Pimcore plugin](../20_Extending_Pimcore/13_Bundle_Developers_Guide/README.md), a library component,
 a custom web service, a [CLI Command](../19_Development_Tools_and_Details/11_Console_CLI.md) or just a simple CLI script - you have the full flexibility.

To keep things simple, we're using simple CLI scripts in the following example, although we're recommending the use of [CLI Commands](../19_Development_Tools_and_Details/11_Console_CLI.md).

## Import
The following example indicates the creation of a new object of the class `myclass`. 
Put the following script into the file `/bin/example.php` (or any other PHP file).
```php
<?php

namespace AppBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;

class AwesomeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('app:awesome')
            ->setDescription('Awesome command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

Thus, with very few lines of codes importer scripts can be implemented to populate data objects. Please have a look at 
Pimcore\Console how to integrate your custom CLI scripts to the [Pimcore console](../19_Development_Tools_and_Details/11_Console_CLI.md).

## Export
Export of data objects can be achieved programmatically similar to imports by using object listings and writing just a
few lines of code.
 
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

For simple CSV exports, Pimcore backend interface provides a CSV export functionality.
 
 
## Memory Issues
If you're using / creating very much objects you should call the Pimcore garbage collector after several cycle to 
prevent memory issues

```php
// just call this static method
\Pimcore::collectGarbage();
```

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
