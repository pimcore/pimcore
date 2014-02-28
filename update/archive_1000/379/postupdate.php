<?php

$classList = new Object_Class_List();
$classes=$classList->load();
foreach($classes as $c){
    $c->save();
}

?>