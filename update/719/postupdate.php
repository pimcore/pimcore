<?php

// enables the feature to store the order of relations inside multiple relation fields like objects, multihref, ...

$db = Pimcore_Resource_Mysql::get("database");


// get classes
$classList = new Object_Class_List();
$classes=$classList->load();


foreach ($classes as $c) {
    try {
        $db->getConnection()->exec("ALTER TABLE `object_relations_" . $c->getId() . "` ADD COLUMN `index` int(11) unsigned NOT NULL DEFAULT 0;");
        $db->getConnection()->exec("ALTER TABLE `object_relations_" . $c->getId() . "` ADD INDEX `index` (`index`);");
    }
    catch (Exception $e) { }
}

?>