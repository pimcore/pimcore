<?php


$newRootNamespace = 'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\\';
$oldRootNamespace = 'OnlineShop\Framework\\';

$baseDirs = ["/home/fash/pimcore5/pimcore/lib/Pimcore/Bundle/PimcoreEcommerceFrameworkBundle/models/"];


foreach($baseDirs as $baseDir) {

    $dir = $baseDir . $argv[1];
    $files = scandir($dir);

    $namespacePrefix = str_replace("/", "\\", $argv[1]);
    $namespacePrefix = str_replace($oldRootNamespace, "", $namespacePrefix) . "\\";

    foreach($files as $file) {

        if($file != "." && $file != "..") {

            $filename = str_replace(".php", "", $file);
            echo "'" . $newRootNamespace . $namespacePrefix . str_replace('/', '\\', $filename) . "'";
            echo " => ";
            echo "'" . $oldRootNamespace . $namespacePrefix . str_replace('/', '\\', $filename) . "'";
            echo ",\n";

        }

    }

}




echo "done\n\n";