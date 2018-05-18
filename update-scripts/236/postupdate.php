<?php

$classList = new \Pimcore\Model\DataObject\ClassDefinition\Listing();
$classes = $classList->load();
foreach ($classes as $class) {
    $class->save();
}
