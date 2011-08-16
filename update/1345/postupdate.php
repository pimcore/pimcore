<?php

    // update all classes with non owner objects
        $classList = new Object_Class_List();
        $classes = $classList->load();
        if (is_array($classes)) {
            foreach ($classes as $class) {
                $hasNonOwner = false;
                if (is_array($class->getFieldDefinitions()) && count($class->getFieldDefinitions())) {
                    foreach ($class->getFieldDefinitions() as $key => $def) {
                        if ((method_exists($def, "isRemoteOwner") and $def->isRemoteOwner())) {
                            $def->getOwnerClassName();
                            $hasNonOwner=true;
                        }
                    }
                }
                if($hasNonOwner){
                     $class->save();
                }
            }
        }




?>