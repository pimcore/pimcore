<?php

define("PIMCORE_WEBSITE_VAR",  realpath(dirname(__FILE__)). "/tmp/var");
mkdir(PIMCORE_WEBSITE_VAR, 0777, true);
include_once(realpath(dirname(__FILE__)) . "/../pimcore/cli/startup.php");

$xml = new Zend_Config_Xml(realpath(dirname(__FILE__)) . "/config/testconfig.xml");
$testConfig = $xml->toArray();
$systemDbConfig = array(
    'host' => $testConfig["systemdatabase"]["params"]["host"],
    'username' => $testConfig["systemdatabase"]["params"]["username"],
    'password' => $testConfig["systemdatabase"]["params"]["password"],
    'dbname' => $testConfig["systemdatabase"]["params"]["dbname"],
    "port" => $testConfig["systemdatabase"]["params"]["port"]
);

$testDbConfig = array(
    'host' => $testConfig["testdatabase"]["params"]["host"],
    'username' => $testConfig["testdatabase"]["params"]["username"],
    'password' => $testConfig["testdatabase"]["params"]["password"],
    'dbname' => $testConfig["testdatabase"]["params"]["dbname"],
    "port" => $testConfig["testdatabase"]["params"]["port"]
);
$testDbName = $testDbConfig["dbname"];


@ini_set("display_errors", "On");
@ini_set("display_startup_errors", "On");


if(!defined("TESTS_PATH"))  {
    define('TESTS_PATH', realpath(dirname(__FILE__)));
}

$includePathBak = get_include_path();
$includePaths = array(get_include_path());
//$includePaths[] = $r;
$includePaths[] = TESTS_PATH . "/TestSuite";
$includePaths[] = TESTS_PATH . "/lib";
set_include_path(implode(PATH_SEPARATOR, $includePaths));



//set the pimcore_phpunit to admin mode
define("PIMCORE_ADMIN", true);

// set test config to registry - we might need it later
$conf = new Zend_Config_Xml(TESTS_PATH . "/config/testconfig.xml");
Zend_Registry::set("pimcore_config_test", $conf);


// set timezone
if ($conf instanceof Zend_Config) {
    if ($conf->general->timezone) {
        date_default_timezone_set($conf->general->timezone);
    }
}

// complete the pimcore starup tasks (config, framework, modules, plugins ...)
//sleep(4);



try {
    var_dump($systemDbConfig);

    $db = new Zend_Db_Adapter_Pdo_Mysql($systemDbConfig);

    $db->getConnection()->exec("DROP database IF EXISTS " . $testDbName . ";");
    $db->getConnection()->exec("CREATE DATABASE " . $testDbName . " CHARACTER SET utf8");
    $db = new Zend_Db_Adapter_Pdo_Mysql($testDbConfig);
    $db->getConnection();

    $db->getConnection()->exec("SET NAMES UTF8");
    $db->getConnection()->exec("SET storage_engine=InnoDB;");

    // check utf-8 encoding
    $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
    if ($result['Value'] != "utf8") {
        die("Database charset is not utf-8");
    }
}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
    die("Couldn't establish connection to mysql" . "\n");
}



$setup = new Tool_Setup();

$setup->config(array(
    "database" => array(
        "adapter" => $testConfig["testdatabase"]["adapter"],
        "params" => array(
            "host" => $testConfig["testdatabase"]["params"]["host"],
            "username" => $testConfig["testdatabase"]["params"]["username"],
            "password" => $testConfig["testdatabase"]["params"]["password"],
            "dbname" => $testConfig["testdatabase"]["params"]["dbname"],
            "port" => $testConfig["testdatabase"]["params"]["port"],
        )
    ),
));

$setup->database();
Pimcore::initConfiguration();
$setup->contents(array(
    "username" => "admin",
    "password" => "admin"
));

Pimcore_Model_Cache::clearAll();

// disable all caching for the tests
// @TODO: Do we really want that? Wouldn't we want to test with cache enabled?
//Pimcore_Model_Cache::disable();



// TODO this is probably not needed
try {
    $conf = Zend_Registry::get("pimcore_config_system");
} catch (Exception $e) {

    die("config not present");
}



// add the tests, which still reside in the original development unit, not in pimcore_phpunit to the include path
$includePaths = array(
    get_include_path()
);

$includePaths[] = TESTS_PATH;
$includePaths[] = TESTS_PATH . "/TestSuite";
$includePaths[] = TESTS_PATH . "/lib";

set_include_path(implode(PATH_SEPARATOR, $includePaths));

// register the tests namespace
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Test');
$autoloader->registerNamespace('Rest');
$autoloader->registerNamespace('TestSuite');



/**
 * bootstrap is done, phpunit_pimcore is up and running.
 * It has a database, admin user and a complete config.
 * We can start running our tests against the phpunit_pimcore instance
 */
var_dump(array(get_include_path()));



