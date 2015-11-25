<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

chdir(__DIR__);

include_once("startup.php");

try {
    $opts = new \Zend_Console_Getopt(array(
        'verbose|v' => 'show detailed information (for debug, ...)',
        'help|h' => 'display this help',
        "mode|m=s" => "optimize,warmup"
    ));
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    $opts->parse();
} catch (\Zend_Console_Getopt_Exception $e) {
    echo $e->getMessage();
}


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

// display error message
if(!$opts->getOption("mode")) {
    echo "Please specify the mode! See: \n";
    echo $opts->getUsageMessage();
    exit;
}


if($opts->getOption("verbose")) {
    $writer = new \Zend_Log_Writer_Stream('php://output');
    $logger = new \Zend_Log($writer);
    \Logger::addLogger($logger);

    // set all priorities
    \Logger::setVerbosePriorities();
}

$db = \Pimcore\Db::get();

if($opts->getOption("mode") == "optimize") {
    $tables = $db->fetchAll("SHOW TABLES");

    foreach ($tables as $table) {
        $t = current($table);
        try {
            \Logger::debug("Running: OPTIMIZE TABLE " . $t);
            $db->query("OPTIMIZE TABLE " . $t);
        } catch (Exception $e) {
            \Logger::error($e);
        }
    }
} else if ($opts->getOption("mode") == "warmup") {
    $tables = $db->fetchAll("SHOW TABLES");

    foreach ($tables as $table) {
        $t = current($table);
        try {
            \Logger::debug("Running: SELECT COUNT(*) FROM $t");
            $res = $db->fetchOne("SELECT COUNT(*) FROM $t");
            \Logger::debug("Result: " . $res);
        } catch (Exception $e) {
            \Logger::error($e);
        }
    }
}
