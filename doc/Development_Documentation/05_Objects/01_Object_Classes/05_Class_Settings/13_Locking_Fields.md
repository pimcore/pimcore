# Locking Fields
Sometimes it's useful that a field cannot be modified/deleted in the class editor. Especially if a class is 
created by a plugin.

Pimcore offers the possibility to lock a field programmatically, you can call the method `setLocked()` on every 
`Pimcore\Model\DataObject\ClassDefinition\Data` object.

### Example

The following example will lock every field inside the class with the ID 7.

```php
$class = DataObject\ClassDefinition::getById(7);
$fields = $class->getFieldDefinitions();
 
foreach ($fields as $field) {
   $field->setLocked(true);
}
 
$class->save();
```
