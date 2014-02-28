<?php


// get db connection
$db = Pimcore_Resource_Mysql::get("database");

try {
    $db->getConnection()->exec("ALTER TABLE `thumbnails`
      CHANGE COLUMN `id` `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      CHANGE COLUMN `width` `width` int(11) unsigned NULL DEFAULT NULL,
      CHANGE COLUMN `height` `height` int(11) unsigned NULL DEFAULT NULL,
      CHANGE COLUMN `aspectratio` `aspectratio` tinyint(1) unsigned NULL DEFAULT '0',
      ADD COLUMN `cover` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `aspectratio`,
      ADD COLUMN `contain` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `cover`,
      CHANGE COLUMN `interlace` `interlace` tinyint(1) unsigned NULL DEFAULT '0' AFTER `contain`,
      CHANGE COLUMN `quality` `quality` int(3) NULL DEFAULT NULL;
    ");

}
catch (Exception $e) {}


?>