<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");

// redirects
$db->getConnection()->exec("CREATE TABLE `redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(255) COLLATE utf8_general_ci NULL DEFAULT NULL,
  `target` varchar(255) COLLATE utf8_general_ci NULL DEFAULT NULL,
  `statusCode` varchar(3) NULL DEFAULT NULL,
  `priority` int(2) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `priority` (`priority`)
) COLLATE utf8_bin;");

$db->getConnection()->exec("INSERT INTO `users_permission_definitions` SET `key`='redirects', `translation`='permissions_redirects';");

// glossary
$db->getConnection()->exec("INSERT INTO `users_permission_definitions` SET `key`='glossary', `translation`='permissions_glossary';");

$db->getConnection()->exec("CREATE TABLE `glossary` (
  `id` int(11) NOT NULL auto_increment,
  `language` varchar(2) collate utf8_bin default NULL,
  `text` varchar(255) collate utf8_bin default NULL,
  `link` varchar(255) collate utf8_bin default NULL,
  `abbr` varchar(255) collate utf8_bin default NULL,
  `acronym` varchar(255) collate utf8_bin default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");





// valid languages is new in system config
$configArray = Zend_Registry::get("pimcore_config_system")->toArray();
$configArray["general"] = array(
    "validLanguages"=> ""
);


$config = new Zend_Config($configArray,true);
$writer = new Zend_Config_Writer_Xml(array(
	"config" => $config,
	"filename" => PIMCORE_CONFIGURATION_SYSTEM
));
$writer->write();







echo "<b>Release Notes</b>:<br />";
echo '
    <ul>
        <li>Define frontend languages</li>
        <li>Redirects</li>
        <li>Glossary</li>
    </ul>
';

?>