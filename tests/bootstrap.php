<?php
$date = date('m/d/Y h:i:s a', time());
print($date . "\n");

@ini_set("display_errors", "On");
@ini_set("display_startup_errors", "On");


if (!defined("TESTS_PATH")) {
    define('TESTS_PATH', realpath(dirname(__FILE__)));
}

// some general pimcore definition overwrites
define("PIMCORE_ADMIN", true);
define("PIMCORE_DEBUG", true);
define("PIMCORE_DEVMODE", true);
define("PIMCORE_WEBSITE_VAR", TESTS_PATH . "/tmp/var");

@mkdir(TESTS_PATH . "/output", 0777, true);

// include pimcore bootstrap
include_once(realpath(dirname(__FILE__)) . "/../pimcore/cli/startup.php");


// empty temporary var directory
recursiveDelete(PIMCORE_WEBSITE_VAR);
mkdir(PIMCORE_WEBSITE_VAR, 0777, true);

// get default configuration for the test
$testConfig = new Zend_Config_Xml(TESTS_PATH . "/config/testconfig.xml");
Zend_Registry::set("pimcore_config_test", $testConfig);
$testConfig = $testConfig->toArray();

// get configuration from main project
$systemConfigFile = realpath(__DIR__ . "/../website/var/config/system.php");
$systemConfig = null;
if (is_file($systemConfigFile)) {
    $systemConfig = new \Pimcore\Config\Config(include $systemConfigFile);
    $systemConfig = $systemConfig->toArray();

    // this is to allow localhost tests
    $testConfig["rest"]["host"] = "pimcore-local-unittest";
}

$includePathBak = get_include_path();
$includePaths = [get_include_path()];
$includePaths[] = TESTS_PATH . "/TestSuite";
array_unshift($includePaths, "/lib");
set_include_path(implode(PATH_SEPARATOR, $includePaths));

try {

    // use the default db configuration if there's no main project (eg. travis automated builds)
    $dbConfig = $testConfig["database"];
    if (is_array($systemConfig) && array_key_exists("database", $systemConfig)) {
        // if there's a configuration for the main project, use that one and replace the database name
        $dbConfig = $systemConfig["database"];
        $dbConfig["params"]["dbname"] = $dbConfig["params"]["dbname"] . "___phpunit";

        // remove write only config
        if (isset($dbConfig["writeOnly"])) {
            unset($dbConfig["writeOnly"]);
        }
    }

    // use mysqli for that, because Zend_Db requires a DB for a connection
    $db = new PDO('mysql:host=' . $dbConfig["params"]["host"] . ';port=' . (int) $dbConfig["params"]["port"] . ';', $dbConfig["params"]["username"], $dbConfig["params"]["password"]);
    $db->query("SET NAMES utf8");

    $db->query("DROP database IF EXISTS " . $dbConfig["params"]["dbname"] . ";");
    $db->query("CREATE DATABASE " . $dbConfig["params"]["dbname"] . " charset=utf8");
    $db = null;
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
    die("Couldn't establish connection to mysql" . "\n");
}


if (defined('HHVM_VERSION')) {
    // always use PDO in hhvm environment (mysqli is not supported)
    $dbConfig["adapter"] = "Pdo_Mysql";
}

echo "\n\nDatabase Config: ". print_r($dbConfig, true) . "\n\n";

$setup = new Tool_Setup();
$setup->config([
    "database" => $dbConfig,
    "webservice" => ["enabled" => 1],
    "general" => ["validLanguages" => "en,de"]
]);

Pimcore::initConfiguration();

// force the db wrapper to use only one connection, regardless if read/write
if (is_array($systemConfig)) {
    $db = \Pimcore\Db::get();
    $db->setWriteResource($db->getResource());
}

$setup->database();


$setup->contents([
    "username" => "admin",
    "password" => microtime()
]);

echo "\nSetup done...\n";

// to be sure => reset the database
Pimcore_Resource::reset();

Pimcore_Model_Cache::disable();

// add the tests, which still reside in the original development unit, not in pimcore_phpunit to the include path
$includePaths = [
    get_include_path()
];

$includePaths[] = TESTS_PATH;
$includePaths[] = TESTS_PATH . "/TestSuite";
$includePaths[] = TESTS_PATH . "/lib";

set_include_path(implode(PATH_SEPARATOR, $includePaths));

// register the tests namespace
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Test');
$autoloader->registerNamespace('Rest');
$autoloader->registerNamespace('TestSuite');

// dummy change

/**
 * bootstrap is done, phpunit_pimcore is up and running.
 * It has a database, admin user and a complete config.
 * We can start running our tests against the phpunit_pimcore instance
 */

Test_BaseRest::setTestConfig($testConfig);

print("include path: " . get_include_path() . "\n");
print("bootstrap    done\n");
