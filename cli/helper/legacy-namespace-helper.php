<?php


$newRootNamespace = 'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\\';
$oldRootNamespace = 'OnlineShop\Framework\\';

$baseDirs = ["/home/fash/pimcore5/pimcore/lib/Pimcore/Bundle/PimcoreEcommerceFrameworkBundle/models/"];


foreach($baseDirs as $baseDir) {

    $dir = $baseDir . $argv[1];
    $files = scandir($dir);

    foreach($files as $file) {

        if($file != "." && $file != "..") {

            $filename = str_replace(".php", "", $file);
            echo "'" . $newRootNamespace . str_replace('/', '\\', $filename) . "'";
            echo " => ";
            echo "'" . $oldRootNamespace . str_replace('/', '\\', $filename) . "'";
            echo ",\n";

        }

    }

}




echo "done\n\n";