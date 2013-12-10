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

sendQuery("CREATE TABLE `versions` (
`id` bigint(20) unsigned NOT NULL auto_increment,
  `cid` int(11) unsigned default NULL,
  `ctype` enum('document','asset','object') default NULL,
  `userId` int(11) unsigned default NULL,
  `note` text,
  `date` bigint(1) unsigned default NULL,
  `public` tinyint(1) unsigned NOT NULL default '0',
  `serialized` tinyint(1) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `ctype` (`ctype`)
) DEFAULT CHARSET=utf8;");



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
