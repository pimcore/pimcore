<?php
//change columnType for existing class definitions
$classList = new \Pimcore\Model\Object\ClassDefinition\Listing();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
        $doSave = false;
        foreach ($class->getFieldDefinitions() as $fieldDef) {
            if($fieldDef instanceof \Pimcore\Model\Object\ClassDefinition\Data\Select) {
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
