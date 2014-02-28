<?php


function sendQuery ($sql) {
    try {
        $db = Pimcore_Resource::get();
        $db->query($sql);
    } catch (Exception $e) {
        echo $e->getMessage();
        echo "Please execute the following query manually: <br />";
        echo "<pre>" . $sql . "</pre><hr />";
    }
}

sendQuery("DROP TABLE IF EXISTS `website_settings`;");
sendQuery("CREATE TABLE `website_settings` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`type` ENUM('text','document','asset','object','bool') NULL DEFAULT NULL,
	`data` TEXT NULL,
	`siteId` INT(11) UNSIGNED NULL DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `name` (`name`),
	INDEX `siteId` (`siteId`)
)
DEFAULT CHARSET=utf8;");



$configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/website.xml";
$configFileNew = PIMCORE_CONFIGURATION_DIRECTORY . "/website-legacy.xml";

$rawConfig = new Zend_Config_Xml($configFile);
$arrayData = $rawConfig->toArray();
$data = array();

foreach ($arrayData as $key => $value) {

    $setting = new WebsiteSetting();
    $setting->setName($key);
    $type = $value["type"];
    $setting->setType($type);

    $data = $value["data"];
    if ($type == "bool") {
        $data = (bool) $data;
    } else if($type == "document") {
        $data = Document::getByPath($value["data"]);
    } else if($type == "asset") {
        $data = Asset::getByPath($value["data"]);
    }  else if($type == "object") {
        $data = Object_Abstract::getByPath($value["data"]);
    }

    if ($data instanceof Element_Interface) {
        $data = $data->getId();
    }


    $setting->setData($data);

    $siteId = ($value["siteId"] > 0) ? (int)$value["siteId"] : null;
    $setting->setSiteId($siteId);
    $setting->save();

}

@rename($configFile, $configFileNew);
