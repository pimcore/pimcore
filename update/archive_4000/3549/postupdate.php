<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("CREATE TABLE `quantityvalue_units` (
              `id` bigint(20) NOT NULL AUTO_INCREMENT,
              `group` varchar(50) COLLATE utf8_bin DEFAULT NULL,
              `abbreviation` varchar(10) COLLATE utf8_bin NOT NULL,
              `longname` varchar(250) COLLATE utf8_bin DEFAULT NULL,
              `baseunit` varchar(10) COLLATE utf8_bin DEFAULT NULL,
              `factor` double DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
        ");

$db->query("ALTER TABLE `classificationstore_keys`
	CHANGE COLUMN `type` `type` ENUM('input','textarea','wysiwyg','checkbox','numeric','slider','select','multiselect','date','datetime','language','languagemultiselect','country','countrymultiselect','table','quantityValue') NULL DEFAULT NULL AFTER `description`;
");

