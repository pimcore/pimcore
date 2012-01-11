<?php

include_once("../../../pimcore/cli/startup.php");

$page = 0;
$pageSize = 100;
$count = $pageSize;
$products = array();

$updater = OnlineShop_Framework_Factory::getInstance()->getIndexService();
$updater->createOrUpdateTable();

while($count > 0) {
    echo "=========================\n";
    echo "Page: " . $page ."\n";
    echo "=========================\n";

    $products = Object_Product::getList(array(
        "unpublished" => true,
        "offset" => $page * $pageSize,
        "limit" => $pageSize,
        "objectTypes" => array("object", "folder", "variant")
    ));

    foreach($products as $p) {

        echo "Updating product " . $p->getId() . "\n";

        $updater->updateIndex($p);
    }
    $page++;

    $count = count($products->getObjects());

    Pimcore::collectGarbage();
}