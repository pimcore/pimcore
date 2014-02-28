<?php

$list = new Object_Class_List();
$classes = $list->load();
if(!empty($classes)){
    foreach($classes as $class){
        $class->save();
    }
}