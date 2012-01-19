<?php

/* defines whether the pimcore install process shall be skipped, if you skip you won't run the tests in a
 * clean environment, because neither the files nor the database of the testing unit are cleared
 * default is false
 */
$skipInstall = false;


/*
 *  defines whether the download process from pimcore.org shall be skipped. default is true, because the files
 *  are copied from the current testing instance anyways. Including the download in the tests would just ensure that
 *  the download package is available at pimcore.org
 */
$skipDownload = true;

if(!defined("TESTS_PATH"))  {
    define('TESTS_PATH', realpath(dirname(__FILE__)));
}


// Add pimcore lib of the original development unit to include path - since we have not started up any pimcore yet,
// there is no Zend Framewokr available yet, but need some Zend Framework components before we startup a pimcore instance
$r = realpath(dirname(__FILE__));
//remove /tests
$r = substr($r, 0, -5);
//add pimcore lib
$r = $r . "pimcore/lib";

$includePathBak = get_include_path();
$includePaths = array(get_include_path());
$tempPaths[] = $r;
$tempPaths[] = TESTS_PATH . "/models";
$tempPaths[] = TESTS_PATH . "/lib";
set_include_path(implode(PATH_SEPARATOR, $tempPaths));



//include parts of the Zend Framework - just what we need to read the test config
require_once 'Zend/Config.php';
require_once 'Zend/Config/Xml.php';
$xml = new Zend_Config_Xml(realpath(dirname(__FILE__)) . "/config/testconfig.xml");
$testConfig = $xml->toArray();

//set_include_path(implode(PATH_SEPARATOR, $includePaths));

//the document root of the new pimcore instance (phpunit_pimcore) which is created to run the tests with
$documentRoot = $testConfig["documentRoot"];

if (!$skipInstall) {

    //read DB config from test config. This is the DB connection for the  phpunit_pimcore
    $dbConfig = array(
        'host' => $testConfig["database"]["params"]["host"],
        'username' => $testConfig["database"]["params"]["username"],
        'password' => $testConfig["database"]["params"]["password"],
        'dbname' => $testConfig["database"]["params"]["dbname"],
        "port" => $testConfig["database"]["params"]["port"]
    );

    //the document root of the original development pimcore (the one holding new code changes before check-in)
    $pimcoreRoot = $testConfig["pimcoreRoot"];

    $downloadLink = $testConfig["downloadLink"];
    $tokens = explode("/", $downloadLink);
    $zipFileName = $tokens[count($tokens) - 1];

    if (!is_dir($pimcoreRoot) or !is_dir($documentRoot)) {
        die("pimcore root or document root misconfigured");
    }

    chdir($documentRoot);


    if (!$skipDownload) {
        //execute download - remove all old files in the phpunit_pimcore document root and download the zipped package
        exec("rm -Rf  " . $documentRoot . "/*");
        exec("rm -Rf  " . $documentRoot . "/.htaccess");
        exec("wget " . $downloadLink);
    }
    // extract the download package
    exec("unzip -o " . $zipFileName);

    // remove all pimcore files from the  phpunit_pimcore document root an replace them with the files from the original
    // development pimcore so that new code changes are available, which are not yet checked in
    exec("rm -Rf  " . $documentRoot . "/pimcore  ");
    exec("rm -Rf  " . $documentRoot . "/index.php  ");
    exec("rm -Rf  " . $documentRoot . "/.htaccess  ");

    exec("cp -R " . $pimcoreRoot . "/pimcore  " . $documentRoot . "/");
    exec("cp " . $pimcoreRoot . "/index.php  " . $documentRoot . "/");
    exec("cp " . $pimcoreRoot . "/.htaccess  " . $documentRoot . "/");

    if (!$skipDownload) {
        mkdir($documentRoot . "/website/var/plugins", 0755, true);
    }

    //replace system config of phpunit_pimcore with the system config meant to be used for the tests
    exec("rm -Rf  " . $documentRoot . "/website/var/config/system.xml");
    exec("cp " . $pimcoreRoot . "/tests/config/system.xml  " . $documentRoot . "/website/var/config/system.xml");


}

// startup the phpunit_pimcore
include_once($documentRoot . "/pimcore/config/startup.php");
set_include_path(get_include_path() . PATH_SEPARATOR . $includePathBak);

@ini_set("display_errors", "On");
@ini_set("display_startup_errors", "On");


Pimcore::setSystemRequirements();
Pimcore::initAutoloader();


if (!$skipInstall) {
    // setup the database for the phpunit_pimcore
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
        echo $e->getMessage() . "\n";
        die("Couldn't establish connection to mysql" . "\n");
    }

    $db->getConnection()->exec("DROP database IF EXISTS pimcore_phpunit;");
    $db->getConnection()->exec("CREATE DATABASE pimcore_phpunit CHARACTER SET utf8");
    $db = new Zend_Db_Adapter_Pdo_Mysql($dbConfig);
    $db->getConnection();

    $db->getConnection()->exec("SET NAMES UTF8");
    $db->getConnection()->exec("SET storage_engine=InnoDB;");


    // insert db dump
    //$db = Pimcore_Resource::get();
    $mysqlInstallScript = file_get_contents(PIMCORE_PATH . "/modules/install/mysql/install.sql");

    // remove comments in SQL script
    $mysqlInstallScript = preg_replace("/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/","",$mysqlInstallScript);

    // get every command as single part
    $mysqlInstallScripts = explode(";",$mysqlInstallScript);

    // execute every script with a separate call, otherwise this will end in a PDO_Exception "unbufferd queries, ..."
    foreach ($mysqlInstallScripts as $m) {
        $sql = trim($m);
        if(strlen($sql) > 0) {
            $sql .= ";";
            $db->exec($m);
        }
    }


    // insert data into database
    $db->insert("assets", array(
        "id" => 1,
        "parentId" => 0,
        "type" => "folder",
        "filename" => "",
        "path" => "/",
        "creationDate" => time(),
        "modificationDate" => time(),
        "userOwner" => 1,
        "userModification" => 1
    ));
    $db->insert("documents", array(
        "id" => 1,
        "parentId" => 0,
        "type" => "page",
        "key" => "",
        "path" => "/",
        "index" => 999999,
        "published" => 1,
        "creationDate" => time(),
        "modificationDate" => time(),
        "userOwner" => 1,
        "userModification" => 1
    ));
    $db->insert("documents_page", array(
        "id" => 1,
        "controller" => "",
        "action" => "",
        "template" => "",
        "title" => "",
        "description" => "",
        "keywords" => ""
    ));
    $db->insert("objects", array(
        "o_id" => 1,
        "o_parentId" => 0,
        "o_type" => "folder",
        "o_key" => "",
        "o_path" => "/",
        "o_index" => 999999,
        "o_published" => 1,
        "o_creationDate" => time(),
        "o_modificationDate" => time(),
        "o_userOwner" => 1,
        "o_userModification" => 1
    ));

    $userPermissions = array(
        array("key" => "assets"),
        array("key" => "classes"),
        array("key" => "clear_cache"),
        array("key" => "clear_temp_files"),
        array("key" => "document_types"),
        array("key" => "documents"),
        array("key" => "objects"),
        array("key" => "plugins"),
        array("key" => "predefined_properties"),
        array("key" => "routes"),
        array("key" => "seemode"),
        array("key" => "system_settings"),
        array("key" => "thumbnails"),
        array("key" => "translations"),
        array("key" => "redirects"),
        array("key" => "glossary" ),
        array("key" => "reports")
    );
    foreach ($userPermissions as $up) {
        $db->insert("users_permission_definitions", $up);
    }


}

// complete the pimcore starup tasks (config, framework, modules, plugins ...)
Pimcore::initConfiguration();
sleep(4);

Pimcore::setupFramework();
Pimcore::initLogger();

Pimcore::initModules();
Pimcore::initPlugins();


/*
 * Now the pimcore_phpunit instance is up and running. It is a clean pimcore instance with a fresh database setup and
 * system config. The pimcore source code is identical to the current development unit
 */

//create admin user (normally this would be included in the pimcore install process)
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
// set test config to registry - we might need it later
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


// add the tests, which still reside in the original development unit, not in pimcore_phpunit to the include path
$includePaths = array(
    get_include_path()
);
$includePaths[] = TESTS_PATH . "/models";
$includePaths[] = TESTS_PATH . "/lib";

set_include_path(implode(PATH_SEPARATOR, $includePaths));

// register the tests namespace
$autoloader = Zend_Loader_Autoloader::getInstance();

$autoloader->registerNamespace('Test');

//set the pimcore_phpunit to admin mode
define("PIMCORE_ADMIN", true);

// disable all caching for the tests
// @TODO: Do we really want that? Wouldn't we want to test with cache enabled?
Pimcore_Model_Cache::disable();

/**
 * bootstrap is done, phpunit_pimcore is up and running.
 * It has a database, admin user and a complete config.
 * We can start running our tests against the phpunit_pimcore instance
 */








