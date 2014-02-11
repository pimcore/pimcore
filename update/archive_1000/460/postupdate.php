<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->getConnection()->exec("ALTER TABLE `properties_predefined` CHANGE COLUMN `value` `data` text NULL;");

?>