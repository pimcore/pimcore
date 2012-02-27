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
        'verbose|v' => 'show detailed information (for debug, ...)',
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


$db = Pimcore_Resource::get();
$tables = $db->fetchAll("SHOW TABLES");

foreach ($tables as $table) {
    $t = current($table);
    try {
        Logger::debug("Running: OPTIMIZE TABLE " . $t);
        $db->query("OPTIMIZE TABLE " . $t);
    } catch (Exception $e) {
        Logger::error($e);
    }
}
