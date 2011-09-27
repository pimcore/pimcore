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

include_once("startup.php");


try {
    $opts = new Zend_Console_Getopt(array(
        'types|t=s' => 'perform warming only for this types of elements (comma separated), valid arguments: document,asset,object (default: all types)',
        "documentTypes|dt=s" => "only for these types of documents (comma seperated), valid arguments: page,snippet,folder,link (default: all types)",
        "assetTypes|at=s" => "only for these types of assets (comma seperated), valid arguments: folder,image,text,audio,video,document,archive,unknown (default: all types)",
        "objectTypes|ot=s" => "only for these types of objects (comma seperated), valid arguments: object,folder,variant (default: all types)",
        "classes|c=s" => "this is only for objects! filter by class (comma seperated), valid arguments: class-names of your classes defined in pimcore",
        "maintenanceMode|m" => "enable maintenance mode during cache warming",
        'verbose|v' => 'show detailed information during the maintenance (for debug, ...)',
        'help|h' => 'display this help'
    ));
} catch (Exception $e) {
    echo $e->getMessage();
}


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

// enable maintenance mode if requested
if($opts->getOption("maintenanceMode")) {
    Pimcore_Tool_Admin::activateMaintenanceMode("cache-warming-dummy-session-id");

    // set the timeout between each iteration to 0 if maintenance mode is on, because we don't have to care about the load on the server
    Pimcore_Cache_Tool_Warming::setTimoutBetweenIteration(0);
}

if($opts->getOption("verbose")) {
    $writer = new Zend_Log_Writer_Stream('php://output');
    $logger = new Zend_Log($writer);
    Logger::addLogger($logger);

    // set all priorities
    Logger::setPriorities(array(
        Zend_Log::DEBUG,
        Zend_Log::INFO,
        Zend_Log::NOTICE,
        Zend_Log::WARN,
        Zend_Log::ERR,
        Zend_Log::CRIT,
        Zend_Log::ALERT,
        Zend_Log::EMERG
    ));
}

// get valid types (default all types)
$types = array("document","asset","object");
if($opts->getOption("types")) {
    $types = explode(",", $opts->getOption("types"));
}

if(in_array("document", $types)) {

    $docTypes = null;
    if($opts->getOption("documentTypes")) {
        $docTypes = explode(",", $opts->getOption("documentTypes"));
    }
    Pimcore_Cache_Tool_Warming::documents($docTypes);
}

if(in_array("asset", $types)) {

    $assetTypes = null;
    if($opts->getOption("assetTypes")) {
        $assetTypes = explode(",", $opts->getOption("assetTypes"));
    }

    Pimcore_Cache_Tool_Warming::assets($assetTypes);
}

if(in_array("object", $types)) {

    $objectTypes = null;
    if($opts->getOption("objectTypes")) {
        $objectTypes = explode(",", $opts->getOption("objectTypes"));
    }

    $classes = null;
    if($opts->getOption("classes")) {
        $classes = explode(",", $opts->getOption("classes"));
    }

    Pimcore_Cache_Tool_Warming::objects($objectTypes, $classes);
}




// disable maintenance mode if requested
if($opts->getOption("maintenanceMode")) {
    Pimcore_Tool_Admin::deactivateMaintenanceMode();
}