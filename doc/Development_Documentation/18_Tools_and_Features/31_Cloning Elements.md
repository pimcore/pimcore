# Cloning Elements

Use

```php
$new = Pimcore\Model\Element\Service::cloneMe($source)
```

to get a safe copy of the original element (everything that implements ElementInterface).
Note that this will not update any internal references.
For example: 
A relation inside the source element pointing to the source element will still reference the source element in the copy.

If you want to get a persistent copy use the `copyAsChild`method of the corresponding service.
E.g.

```php
$user = \Pimcore\Model\User::getById(123);

$assetService = new \Pimcore\Model\Asset\Service($user);
$assetService->copyAsChild($target, $source);

$documentService = new \Pimcore\Model\Document\Service($user);
$documentService->copyAsChild($target, $source); // additional arguments are available for inheritance, ...

$objectService = new \Pimcore\Model\DataObject\Service($user);
$objectService->copyAsChild($target, $source);
```
where `$source`is the source element and `$target` the parent element of the new element.
This will also create a unique element key (or filename for asset elements).

If you also want to update the references there is a helper method which accomplishes this for you.
Just call the service's `rewriteIds` and provide a mapper config.
 
 Example:
 
 ```php
 $rewriteConfig = array(
     "object" => array(
         176 => 190
     )
 );
 $object = DataObject\Service::rewriteIds($object, $rewriteConfig);
 ```
 meaning that in the copy everything point to object with ID 176 will be replaced with a reference pointing to object 190.