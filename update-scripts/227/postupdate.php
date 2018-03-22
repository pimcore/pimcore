<?php

$classList = new \Pimcore\Model\DataObject\ClassDefinition\Listing();
$classes = $classList->load();
foreach ($classes as $class) {
    $class->save();
}

$brickList = new \Pimcore\Model\DataObject\Objectbrick\Definition\Listing();
$brickList = $brickList->load();
foreach ($brickList as $brickDef) {
    $brickDef->save();
}
