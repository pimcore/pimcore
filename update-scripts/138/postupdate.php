<?php

$classList = new \Pimcore\Model\DataObject\ClassDefinition\Listing();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
        $doSave = false;
        foreach ($class->getFieldDefinitions() as $fieldDef) {
            if($fieldDef instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\Select) {
                $fieldDef->setQueryColumnType("varchar");
                $fieldDef->setColumnType("varchar");
                $doSave = true;
            }
        }
        if($doSave){
            $class->save();
        }
    }
}
