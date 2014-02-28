<?php

    // get classes
    $classList = new Object_Class_List();
    $classes=$classList->load();
    
    
    foreach($classes as $c){
        try {
            foreach ($c->getFieldDefinitions() as $f) {
                if($f->floating) {
                    $f->style = "float: left; margin-right: 5px;";                    
                    $c->save();
                }
            }
        }
        catch (Exception $e) { }
    }
    
?>