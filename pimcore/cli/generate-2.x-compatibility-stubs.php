<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

include("startup.php");


$paths = array(
    PIMCORE_PATH . "/lib/Pimcore",
    PIMCORE_PATH . "/models",
    PIMCORE_CLASS_DIRECTORY,
);
$output = PIMCORE_WEBSITE_VAR . "/compatibility-2.x-stubs.php";

$excludePatterns = [
    "/^Google_/",
    "/^Zend_/",
    "/^Hybrid/",
    "/^lessc/",
    "/^Csv/",
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
            $filename  = str_replace(PIMCORE_DOCUMENT_ROOT, "\$pdr . '", $file->getRealpath());

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

foreach($globalMap as $class => $file) {

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

    if($class != $alias) {
        $line = "class " . $alias . " extends \\" . $class . " {} \n";
    }

    $content .= $line;
}

// Write the contents to disk
file_put_contents($output, $content);
