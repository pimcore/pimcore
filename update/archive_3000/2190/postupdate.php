<?php

$classList = new Object_Class_List();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
		$hasMultiselect = false;
		foreach ($class->getFieldDefinitions() as $key => $value) {
			if($value instanceof Object_Class_Data_Multiselect) {
				$value->setQueryColumnType("text");
				$value->setColumnType("text");

                $hasMultiselect = true;
			}
		}

		if($hasMultiselect) {
			$class->save();
		}
    }
}
