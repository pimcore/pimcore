<?php


$classList = new Object_Class_List();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
        $class->save();
    }
}

Search_Backend_Tool::createSearchDataTable();


?>