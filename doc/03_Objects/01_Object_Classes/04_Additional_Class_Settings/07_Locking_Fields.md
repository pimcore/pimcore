---
title: "Locking Fields"
description: "Preventing modification or deletion of fields in the class editor."
---

# Locking Fields
Lock fields to prevent modification or deletion in the class editor, particularly for plugin-created classes.

Call `setLocked()` on any `Pimcore\Model\DataObject\ClassDefinition\Data` object to lock a field programmatically.

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
