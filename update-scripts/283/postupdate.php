<?php

$classList = new \Pimcore\Model\DataObject\ClassDefinition\Listing();
$classes = $classList->load();
foreach ($classes as $ckey => $class) {
    $found = false;
    foreach ($class->getFieldDefinitions() as $fkey => $field) {
        if ($field instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\ObjectsMetadata) {
            $allowedClassId = $field->getAllowedClassId();
            if (is_numeric($allowedClassId)) {
                $class = \Pimcore\Model\DataObject\ClassDefinition::getById($allowedClassId);
                $allowedClassId  = $class ? $class->getName() : null;
                $field->setAllowedClassId($allowedClassId);
            }
            $found = true;
        }
    }
    if ($found) {
        try {
            $class->save();
        } catch (\Exception $e) {
            \Pimcore\Logger::err($e);
        }
    }
}

$brickList = new \Pimcore\Model\DataObject\Objectbrick\Definition\Listing();
$brickList = $brickList->load();
foreach ($brickList as $brickDef) {
    $found = false;
    foreach ($brickDef->getFieldDefinitions() as $fkey => $field) {
        if ($field instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\ObjectsMetadata) {
            $allowedClassId = $field->getAllowedClassId();
            if (is_numeric($allowedClassId)) {
                $class = \Pimcore\Model\DataObject\ClassDefinition::getById($allowedClassId);
                $allowedClassId  = $class ? $class->getName() : null;
                $field->setAllowedClassId($allowedClassId);
            }
            $found = true;
        }
    }
    if ($found) {
        try {
            $brickDef->save();
        } catch (\Exception $e) {
            \Pimcore\Logger::err($e);
        }
    }
}

$fcList = new \Pimcore\Model\DataObject\Fieldcollection\Definition\Listing();
$fcList = $fcList->load();
foreach ($fcList as $collectionDef) {
    $found = false;
    foreach ($collectionDef->getFieldDefinitions() as $fkey => $field) {
        if ($field instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\ObjectsMetadata) {
            $allowedClassId = $field->getAllowedClassId();
            if (is_numeric($allowedClassId)) {
                $class = \Pimcore\Model\DataObject\ClassDefinition::getById($allowedClassId);
                $allowedClassId  = $class ? $class->getName() : null;
                $field->setAllowedClassId($allowedClassId);
            }
            $found = true;
        }
    }
    if ($found) {
        try {
            $collectionDef->save();
        } catch (\Exception $e) {
            \Pimcore\Logger::err($e);
        }
    }
}
