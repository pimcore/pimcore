<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

chdir(__DIR__);

include("startup.php");


$paths = array(
    PIMCORE_CLASS_DIRECTORY,
);
$output = PIMCORE_WEBSITE_VAR . "/compatibility-2.x-stubs.php";

$excludePatterns = [

];

$globalMap = array();
$map = new stdClass();

foreach ($paths as $path) {

    if(!empty($path)) {
        // Get the ClassFileLocator, and pass it the library path
        echo "current path: " . $path . "\n";
        $l = new \Zend_File_ClassFileLocator($path);

        // Iterate over each element in the path, and create a map of
        // classname => filename, where the filename is relative to the library path
        //$map    = new stdClass;
        //iterator_apply($l, 'createMap', array($l, $map));

        foreach ($l as $file) {
            $filename  = $file->getRealpath();

            // Windows portability
            $filename  = str_replace(DIRECTORY_SEPARATOR, "/", $filename);

            foreach ($file->getClasses() as $class) {
                $allowed = true;
                foreach($excludePatterns as $excludePattern) {
                    if(preg_match($excludePattern, $class)) {
                        $allowed = false;
                        break;
                    }
                }

                if($allowed) {
                    $map->{$class} = $filename;
                }
            }
        }

        $globalMap = array_merge($globalMap, (array) $map);
    }
}

$globalMap = (array) $globalMap;

$content = '<' . "?" . "php \n\n";

$processedClasses = [];

foreach($globalMap as $class => $file) {

    $contents = file_get_contents($file);
    $definition = "";
    if(strpos($contents, "abstract class")) {
        $definition = "abstract class";
    } else if (strpos($contents, "class ")) {
        $definition = "class";
    } else if (strpos($contents, "interface ")) {
        $definition = "interface";
    } else {
        continue;
    }

    $alias = str_replace("\\", "_", $class);
    $alias = preg_replace("/_Abstract(.*)/", "_Abstract", $alias);
    $alias = preg_replace("/_[^_]+Interface/", "_Interface", $alias);
    $alias = str_replace("_Listing_", "_List_", $alias);
    $alias = preg_replace("/_Listing$/", "_List", $alias);
    $alias = str_replace("Object_ClassDefinition", "Object_Class", $alias);

    if(strpos($alias, "Pimcore_Model") === 0) {
        if(!preg_match("/^Pimcore_Model_(Abstract|List|Resource|Cache)/", $alias)) {
            $alias = str_replace("Pimcore_Model_", "", $alias);
        }
    }

    $line = "";
    if($class != $alias && !in_array($alias, $processedClasses)) {
        $line .= "/**\n * @deprecated \n */\n";
        $line .= $definition . " " . $alias . " extends \\" . $class . " {} \n\n";
    }

    $content .= $line;

    $processedClasses[] = $alias;
}

// Write the contents to disk
file_put_contents($output, $content);
