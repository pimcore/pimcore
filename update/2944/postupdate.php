<?php

Pimcore_Model_Cache::disable();

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `sites` ADD COLUMN `mainDomain` varchar(255) NULL DEFAULT NULL AFTER `id`;");
$db->query("ALTER TABLE `sites` ADD COLUMN `errorDocument` varchar(255) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `sites` ADD COLUMN `redirectToMainDomain` tinyint(1) NULL DEFAULT NULL;");


$sites = new Site_List();
$sites->load();

foreach ($sites->getSites() as $site) {
    $domains = $site->getDomains();
    $mainDomain = "";
    if(is_array($domains)) {
        $mainDomain = array_shift($domains);
    }

    $site->setMainDomain($mainDomain);
    $site->setDomains($domains);

    $siteKey = Pimcore_Tool_Frontend::getSiteKey($site);
    $errorPath = Pimcore_Config::getSystemConfig()->documents->error_pages->$siteKey;
    if($errorPath) {
        $site->setErrorDocument($errorPath);
    }

    $site->save();
}

