<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");

$db->getConnection()->exec("ALTER TABLE `translations` CHANGE COLUMN `language` `language` varchar(10) NULL DEFAULT NULL;");
$db->getConnection()->exec("ALTER TABLE `translations` ADD COLUMN `id` int(11) unsigned NOT NULL AUTO_INCREMENT FIRST, ADD COLUMN `tid` int(11) unsigned NOT NULL DEFAULT 0 AFTER `id`, ADD PRIMARY KEY (`id`), ADD INDEX `tid` (`tid`);");

$groups = $db->fetchAll("SELECT `id`,`key` FROM translations GROUP BY `key`");
foreach ($groups as $group) {
    $db->update("translations", array("tid" => $group["id"]), "`key` = '".$group["key"]."'");
}

$db->getConnection()->exec("ALTER TABLE `translations` ADD UNIQUE INDEX (`tid`,`language`(10));");

?>

<b>Build 216 Release Notes:</b>
<br />
- Edit keys of translations<br />
- Support for formatting-characters in translation-keys <a href="http://framework.zend.com/manual/en/zend.view.helpers.html#zend.view.helpers.initial.translate" target="_blank">read more about it here</a><br />
- Support for locales in translations not only languages
