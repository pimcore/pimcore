<?php

$classList = new \Pimcore\Model\DataObject\ClassDefinition\Listing();
$classes = $classList->load();
foreach ($classes as $ckey => $class) {
    foreach ($class->getFieldDefinitions() as $fkey => $field) {
        if ($field->getUnique()) {
            $class->save(); //save class to remove unique constraint form query table
            break;
        }
    }
}
