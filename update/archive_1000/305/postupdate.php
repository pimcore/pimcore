<?php

$db = Pimcore_Resource_Mysql::get("database");

try {
    $db->getConnection()->exec("ALTER TABLE `translations` DROP INDEX `tid_2`, DEFAULT CHARSET=utf8;");
}
catch (Exception $e) {}
try {
    $db->getConnection()->exec("ALTER TABLE `translations` DROP INDEX `tid_3`, DEFAULT CHARSET=utf8;");
}
catch (Exception $e) {}



$list = new Translation_List();
$list->setOrder("asc");
$list->setOrderKey("date");
$translations = $list->load();

$existing = array();

foreach ($translations as $t) {
    if(in_array($t->getKey(),$existing)) {
        $t->delete();
        continue;
    }
    $existing[] = $t->getKey();
}


$db->getConnection()->exec("
ALTER TABLE `translations`
  ADD UNIQUE INDEX `tid_language` (`tid`,`language`(10)),
  ADD UNIQUE INDEX `key_language` (`key`(255),`language`(10)),
 DEFAULT CHARSET=utf8;
");

?>