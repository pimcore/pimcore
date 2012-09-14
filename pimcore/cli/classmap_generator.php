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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

include("startup.php");



try {
    $opts = new Zend_Console_Getopt(array(
        'core|c' => 'generate class map for all core files in /pimcore (usually used by the core team)',
        'website|w' => 'generate class map for all classes in include path (usually for you ;-) ) - this is the default',
        'help|h' => 'display this help'
    ));
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    echo $e->getMessage();
}


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}


if($opts->getOption("core")) {
    $paths = array(
        PIMCORE_PATH . "/lib",
        PIMCORE_PATH . "/models",
        PIMCORE_PATH . "/modules/searchadmin/models",
        PIMCORE_PATH . "/lib/_deprecated"
    );
    $output = PIMCORE_PATH . "/config/autoload-classmap.php";
} else {
    $paths = explode(":", get_include_path());
    $output = PIMCORE_CONFIGURATION_DIRECTORY . "/autoload-classmap.php";
}

$globalMap = array();

function createMap(Iterator $i, $map) {
    $file      = $i->current();
    $namespace = empty($file->namespace) ? '' : $file->namespace . '\\';
    $filename  = str_replace(PIMCORE_DOCUMENT_ROOT, "' . \$pdr . '", $file->getRealpath());

    // Windows portability
    $filename  = str_replace(array('/', '\\'), "' . \$dsp . '", $filename);

    $map->{$namespace . $file->classname} = $filename;

    return true;
}

foreach ($paths as $path) {

    // Get the ClassFileLocator, and pass it the library path
    $l = new Zend_File_ClassFileLocator($path);

    // Iterate over each element in the path, and create a map of
    // classname => filename, where the filename is relative to the library path
    $map    = new stdClass;
    iterator_apply($l, 'createMap', array($l, $map));

    $globalMap = array_merge($globalMap, (array) $map);
}

// Create a file with the class/file map.
// Stupid syntax highlighters make separating < from PHP declaration necessary
$content = '<' . "?php\n"
         . '$pdr = PIMCORE_DOCUMENT_ROOT;' . "\n" . '$dsp = DIRECTORY_SEPARATOR;' . "\n"
         . 'return ' . var_export((array) $globalMap, true) . ';';

// Prefix with dirname(__FILE__); modify the generated content
$content = str_replace("\\'", "'", $content);

// Write the contents to disk
file_put_contents($output, $content);
