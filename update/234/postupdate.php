<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");

$groups = $db->fetchAll("SELECT `id`,`tid`,`language` FROM translations");

$uniqueKeys = array();
foreach ($groups as $group) {
    $key = $group["tid"]."-".$group["language"];
    if(in_array($key,$uniqueKeys)) {
        $db->delete("translations","id = '".$group["id"]."'");
    }
    $uniqueKeys[] = $key;
}

$db->getConnection()->exec("ALTER TABLE `translations` ADD UNIQUE INDEX (`tid`,`language`(10));");

?>