<?php

$list = new Object_Objectbrick_Definition_List();
$list = $list->load();

if(is_array($list)){
    foreach ($list as $brick) {
        $hasMultiselect = false;
        foreach ($brick->getFieldDefinitions() as $key => $value) {
            if($value instanceof Object_Class_Data_Multiselect) {
                $value->setQueryColumnType("text");
                $value->setColumnType("text");

                $hasMultiselect = true;
            }
        }

        if($hasMultiselect) {
            $brick->save();
        }
    }
}
