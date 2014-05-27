<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    $db->query("CREATE TABLE `keyvalue_translator_configuration` (
      `id` INT(10) NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(200) NULL DEFAULT NULL,
      `translator` VARCHAR(200) NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) DEFAULT CHARSET=utf8;");

} catch (\Exception $e) {
    echo $e->getMessage();
}
