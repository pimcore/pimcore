<?php

$workingDirectory = getcwd();
chdir(__DIR__);
include_once("../../../pimcore/cli/startup.php");
chdir($workingDirectory);


$page = 0;
$pageSize = 100;
$count = $pageSize;
$products = array();

$updater = OnlineShop_Framework_Factory::getInstance()->getIndexService();
$updater->createOrUpdateTable();

if(!Zend_Registry::isRegistered('Zend_Locale')) {
    $config = OnlineShop_Framework_Factory::getInstance()->getConfig();

    if($locale = (string)$config->onlineshop->environment->config->config->defaultlocale) {
        Zend_Registry::set('Zend_Locale', new Zend_Locale($locale));
    }
}

while($count > 0) {
    echo "=========================\n";
    echo "Page: " . $page ."\n";
    echo "=========================\n";

    $products = new Object_Product_List();
    $products->setUnpublished(true);
    $products->setOffset($page * $pageSize);
    $products->setLimit($pageSize);
    $products->setObjectTypes(array("object", "folder", "variant"));
    $products->setIgnoreLocalizedFields(true);


    foreach($products as $p) {
        echo "Updating product " . $p->getId() . "\n";
        $updater->updateIndex($p);
    }
    $page++;

    $count = count($products->getObjects());

    Pimcore::collectGarbage();
}