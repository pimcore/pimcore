<?php

$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE `classificationstore_keys`
	CHANGE COLUMN `type` `type` ENUM('input','textarea','wysiwyg','checkbox','numeric','slider','select','multiselect','date','datetime','language','languagemultiselect','country','countrymultiselect','table','quantityValue','calculatedValue') NULL DEFAULT NULL AFTER `description`;
");


