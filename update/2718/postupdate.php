<?php

// get db connection
$db = Pimcore_Resource::get();

$languages = Pimcore_Tool::getValidLanguages();

$list = new Object_Class_List();
$classes = $list->load();
if(!empty($classes)){
    foreach($classes as $class){
        if($class->getFielddefinition("localizedfields")) {

            $tableName = "object_localized_data_" . $class->getId();

            $cols = $db->fetchRow("SELECT * FROM " . $tableName . " LIMIT 1");
            if(is_array($cols)) {
                $cols = array_keys($cols);
                foreach($cols as &$col) {
                    $col = $db->quoteIdentifier($col);
                }

                foreach ($languages as $language) {
                    $tableQueryName = "object_localized_query_" . $class->getId() . "_" . $language;
                    try {
                        $db->query("INSERT INTO " . $tableQueryName . " (" . implode(",", $cols) . ") SELECT * FROM " . $tableName . " WHERE language = '" . $language . "'");
                    } catch (\Exception $e) {
                        echo $e->getMessage() . "<br />";
                    }
                }
            }
        }
    }
}
