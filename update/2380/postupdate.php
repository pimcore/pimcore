<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

// key/value datatype

$db->query("CREATE TABLE IF NOT EXISTS `keyvalue_groups` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL ,
    `description` VARCHAR(255),
    PRIMARY KEY  (`id`)
    ) DEFAULT CHARSET=utf8;");


$db->query(
    "CREATE TABLE IF NOT EXISTS `keyvalue_keys` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL ,
    `description` TEXT,
    `type` enum('bool','number','select','text') DEFAULT NULL,
    `unit` VARCHAR(255),
    `possiblevalues` TEXT,
    `group` INT,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`group`) REFERENCES keyvalue_groups(`id`) ON DELETE SET NULL
    ) DEFAULT CHARSET=utf8;");
