<?php

$list = new Object_Fieldcollection_Definition_List();
$list = $list->load();

if(is_array($list)){
    foreach ($list as $fc) {
        $hasMultiselect = false;
        foreach ($fc->getFieldDefinitions() as $key => $value) {
            if($value instanceof Object_Class_Data_Multiselect) {
                $value->setQueryColumnType("text");
                $value->setColumnType("text");

                $hasMultiselect = true;
            }
        }

        if($hasMultiselect) {
            $fc->save();
        }
    }
}