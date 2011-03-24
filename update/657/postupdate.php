<?php


$db = Pimcore_Resource_Mysql::get();
$db->getConnection()->exec("ALTER TABLE `thumbnails` CHANGE COLUMN `format` `format` enum('PNG','JPEG','GIF','SOURCE') COLLATE utf8_bin NULL DEFAULT NULL;");

?>