<?php

$db = \Pimcore\Resource::get();

$db->query("DROP TABLE IF EXISTS `content_index`;");
$db->query("DROP TABLE IF EXISTS `content_analysis`;");

