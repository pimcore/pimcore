<?php

$skipInstall = false;
$skipDownload = true;

/* */


$r = realpath(dirname(__FILE__));
//remove /tests
$r = substr($r, 0, -5);
//add pimcore lib
$r = $r . "pimcore/lib";

$includePaths = array(get_include_path());
$tempPaths[] = $r;
set_include_path(implode(PATH_SEPARATOR, $tempPaths));


require_once 'Zend/Config.php';
require_once 'Zend/Config/Xml.php';
$xml = new Zend_Config_Xml(realpath(dirname(__FILE__)) . "/config/testconfig.xml");
$testConfig = $xml->toArray();

set_include_path(implode(PATH_SEPARATOR, $includePaths));

$documentRoot = $testConfig["documentRoot"];

if (!$skipInstall) {

    $dbConfig = array(
        'host' => $testConfig["database"]["params"]["host"],
        'username' => $testConfig["database"]["params"]["username"],
        'password' => $testConfig["database"]["params"]["password"],
        'dbname' => $testConfig["database"]["params"]["dbname"],
        "port" => $testConfig["database"]["params"]["port"]
    );


    $pimcoreRoot = $testConfig["pimcoreRoot"];
    $downloadLink = $testConfig["downloadLink"];
    $tokens = explode("/", $downloadLink);
    $zipFileName = $tokens[count($tokens) - 1];

    if (!is_dir($pimcoreRoot) or !is_dir($documentRoot)) {
        die("pimcore root or document root misconfigured");
    }


    //prepare fresh pimcore install
    chdir($documentRoot);


    if (!$skipDownload) {
        exec("rm -Rf  " . $documentRoot . "/*");
        exec("rm -Rf  " . $documentRoot . "/.htaccess");
        exec("wget " . $downloadLink);
    }
    exec("unzip -o " . $zipFileName);

    exec("rm -Rf  " . $documentRoot . "/pimcore  ");
    exec("rm -Rf  " . $documentRoot . "/index.php  ");
    exec("rm -Rf  " . $documentRoot . "/.htaccess  ");

    exec("cp -R " . $pimcoreRoot . "/pimcore  " . $documentRoot . "/");
    exec("cp " . $pimcoreRoot . "/index.php  " . $documentRoot . "/");
    exec("cp " . $pimcoreRoot . "/.htaccess  " . $documentRoot . "/");

    if (!$skipDownload) {
        mkdir($documentRoot . "/website/var/plugins", 0755, true);
    }
    //replace system config
    exec("rm -Rf  " . $documentRoot . "/website/var/config/system.xml");
    exec("cp " . $pimcoreRoot . "/tests/config/system.xml  " . $documentRoot . "/website/var/config/system.xml");


}

include_once($documentRoot . "/pimcore/config/startup.php");
define('TESTS_PATH', realpath(dirname(__FILE__)));

@ini_set("display_errors", "On");
@ini_set("display_startup_errors", "On");


Pimcore::setSystemRequirements();
Pimcore::initAutoloader();

// insert db dump
if (!$skipInstall) {
    try {
        $db = new Zend_Db_Adapter_Pdo_Mysql($dbConfig);
        $db->getConnection();

        // check utf-8 encoding
        $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
        if ($result['Value'] != "utf8") {
            die("Database charset is not utf-8");
        }
    }
    catch (Exception $e) {
        die("Couldn't establish connection to mysql");
    }

    $db->getConnection()->exec("DROP database IF EXISTS pimcore_phpunit;");
    $db->getConnection()->exec("CREATE DATABASE pimcore_phpunit CHARACTER SET utf8");
    $db = new Zend_Db_Adapter_Pdo_Mysql($dbConfig);
    $db->getConnection();
    $db->getConnection()->exec(file_get_contents($pimcoreRoot . "/pimcore/modules/install/mysql/install.sql"));
}
Pimcore::initConfiguration();
sleep(4);

Pimcore::setupFramework();
// config is loaded now init the real logger
Pimcore::initLogger();

Pimcore::initModules();
Pimcore::initPlugins();


//create admin
if (!$skipInstall) {
    $user = User::create(array(
        "parentId" => 0,
        "username" => "admin",
        "password" => Pimcore_Tool_Authentication::getPasswordHash("admin", "admin"),
        "hasCredentials" => true,
        "active" => true
    ));
    $user->setAdmin(true);
    $user->save();

    chdir($pimcoreRoot . "/tests");

}
// test config
$conf = new Zend_Config_Xml(TESTS_PATH . "/config/testconfig.xml");
Zend_Registry::set("pimcore_config_test", $conf);


try {
    $conf = Zend_Registry::get("pimcore_config_system");
} catch (Exception $e) {

    die("config not present");
}

// set timezone
if ($conf instanceof Zend_Config) {
    if ($conf->general->timezone) {
        date_default_timezone_set($conf->general->timezone);
    }
}

$includePaths = array(
    get_include_path()
);
$includePaths[] = TESTS_PATH . "/models";
$includePaths[] = TESTS_PATH . "/lib";

set_include_path(implode(PATH_SEPARATOR, $includePaths));


$autoloader = Zend_Loader_Autoloader::getInstance();

$autoloader->registerNamespace('Test');

define("PIMCORE_ADMIN", true);

Pimcore_Model_Cache::disable();




        










