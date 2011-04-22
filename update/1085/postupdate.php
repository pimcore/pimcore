<?php



// update all Object_Class_Data_Input - fields because you can now specify the length of the field
$classList = new Object_Class_List();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
        foreach ($class->getFieldDefinitions() as $fieldDef) {
            if($fieldDef instanceof Object_Class_Data_Date || $fieldDef instanceof Object_Class_Data_Datetime) {
                $fieldDef->setQueryColumnType("bigint(20)");
                $fieldDef->setColumnType("bigint(20)");
            }
        }
        $class->save();
    }
}


?>