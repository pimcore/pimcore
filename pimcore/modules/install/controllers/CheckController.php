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

class Install_CheckController extends Pimcore_Controller_Action {


    public function init() {
        parent::init();

        if (is_file(PIMCORE_CONFIGURATION_SYSTEM)) {
            // session authentication, only possible if user is logged in
            $user = Pimcore_Tool_Authentication::authenticateSession();
            if(!$user instanceof User) {
               die("Authentication failed!<br />If you don't have access to the admin interface any more, and you want to find out if the server configuration matches the requirements you have to rename the the system.xml for the time of the check.");
            }
        } else if ($this->getParam("mysql_adapter")) {

        } else {
            die("Not possible... no database settings given.<br />Parameters: mysql_adapter,mysql_host,mysql_username,mysql_password,mysql_database");
        }
    }

    public function indexAction() {

        $checksPHP = array();
        $checksMySQL = array();
        $checksFS = array();

        // check for memory limit
        $memoryLimit = ini_get("memory_limit");
        $memoryLimit = filesize2bytes($memoryLimit . "B");
        $state = "ok";

        if($memoryLimit < 134217728) {
            $state = "error";
        } else if ($memoryLimit < 268435456) {
            $state = "warning";
        }

        $checksPHP[] = array(
            "name" => "memory_limit (in php.ini)",
            "link" => "http://www.php.net/memory_limit",
            "state" => $state
        );


        // check for safe_mode
        $safemode = strtolower(ini_get("safe_mode"));
        $checksPHP[] = array(
            "name" => "safe_mode (in php.ini)",
            "link" => "http://www.php.net/safe_mode",
            "state" => $safemode == "on" ? "error" : "ok"
        );

        // mcrypt
        $checksPHP[] = array(
            "name" => "mcrypt",
            "link" => "http://www.php.net/mcrypt",
            "state" => function_exists("mcrypt_encrypt") ? "ok" : "error"
        );

        // pdo_mysql
        $checksPHP[] = array(
            "name" => "PDO_Mysql",
            "link" => "http://www.php.net/pdo_mysql",
            "state" => @constant("PDO::MYSQL_ATTR_FOUND_ROWS") ? "ok" : "error"
        );

        // pdo_mysql
        $checksPHP[] = array(
            "name" => "Mysqli",
            "link" => "http://www.php.net/mysqli",
            "state" => class_exists("mysqli") ? "ok" : "error"
        );

        // iconv
        $checksPHP[] = array(
            "name" => "iconv",
            "link" => "http://www.php.net/iconv",
            "state" => function_exists("iconv") ? "ok" : "error"
        );

        // dom
        $checksPHP[] = array(
            "name" => "dom",
            "link" => "http://www.php.net/dom",
            "state" => class_exists("DOMDocument") ? "ok" : "error"
        );

        // simplexml
        $checksPHP[] = array(
            "name" => "SimpleXML",
            "link" => "http://www.php.net/simplexml",
            "state" => class_exists("SimpleXMLElement") ? "ok" : "error"
        );

        // gd
        $checksPHP[] = array(
            "name" => "GD",
            "link" => "http://www.php.net/gd",
            "state" => function_exists("gd_info") ? "ok" : "error"
        );

        // multibyte support
        $checksPHP[] = array(
            "name" => "Multibyte String (mbstring)",
            "link" => "http://www.php.net/mbstring",
            "state" => function_exists("mb_get_info") ? "ok" : "error"
        );

        // zip
        $checksPHP[] = array(
            "name" => "zip",
            "link" => "http://www.php.net/zip",
            "state" => class_exists("ZipArchive") ? "ok" : "error"
        );

        // gzip
        $checksPHP[] = array(
            "name" => "zlib / gzip",
            "link" => "http://www.php.net/zlib",
            "state" => function_exists("gzcompress") ? "ok" : "error"
        );

        // bzip
        $checksPHP[] = array(
            "name" => "Bzip2",
            "link" => "http://www.php.net/bzip2",
            "state" => function_exists("bzcompress") ? "ok" : "error"
        );

        // openssl
        $checksPHP[] = array(
            "name" => "OpenSSL",
            "link" => "http://www.php.net/openssl",
            "state" => function_exists("openssl_open") ? "ok" : "error"
        );

        // Imagick
        $checksPHP[] = array(
            "name" => "Imagick",
            "link" => "http://www.php.net/imagick",
            "state" => class_exists("Imagick") ? "ok" : "warning"
        );

        // APC
        $checksPHP[] = array(
            "name" => "APC",
            "link" => "http://www.php.net/apc",
            "state" => function_exists("apc_add") ? "ok" : "warning"
        );

        // memcache
        $checksPHP[] = array(
            "name" => "Memcache",
            "link" => "http://www.php.net/memcache",
            "state" => class_exists("Memcache") ? "ok" : "warning"
        );

        // PCNTL
        $checksPHP[] = array(
            "name" => "PCNTL",
            "link" => "http://www.php.net/pcntl",
            "state" => function_exists("pcntl_exec") ? "ok" : "warning"
        );

        // soap
        $checksPHP[] = array(
            "name" => "Soap",
            "link" => "http://www.php.net/soap",
            "state" => class_exists("SoapClient") ? "ok" : "warning"
        );

        // curl for google api sdk
        $checksPHP[] = array(
            "name" => "curl",
            "link" => "http://www.php.net/curl",
            "state" => function_exists("curl_init") ? "ok" : "warning"
        );



        $db = null;

        if($this->getParam("mysql_adapter")) {
            // this is before installing
            try {
                $db = Zend_Db::factory($this->getParam("mysql_adapter"),array(
                    'host' => $this->getParam("mysql_host"),
                    'username' => $this->getParam("mysql_username"),
                    'password' => $this->getParam("mysql_password"),
                    'dbname' => $this->getParam("mysql_database"),
                    "port" => $this->getParam("mysql_port")
                ));

                $db->getConnection();
            } catch (Exception $e) {
                $db = null;
            }
        } else {
            // this is after installing, eg. after a migration, ...
            $db = Pimcore_Resource::get();
        }

        if($db) {

            // storage engines
            $engines = array();
            $enginesRaw = $db->fetchAll("SHOW ENGINES;");
            foreach ($enginesRaw as $engineRaw) {
                $engines[] = strtolower($engineRaw["Engine"]);
            }

            // innodb
            $checksMySQL[] = array(
                "name" => "InnoDB Support",
                "state" => in_array("innodb", $engines) ? "ok" : "error"
            );

            // myisam
            $checksMySQL[] = array(
                "name" => "MyISAM Support",
                "state" => in_array("myisam", $engines) ? "ok" : "error"
            );

            // memory
            $checksMySQL[] = array(
                "name" => "MEMORY Support",
                "state" => in_array("memory", $engines) ? "ok" : "error"
            );

            // check database charset =>  utf-8 encoding
            $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
            $checksMySQL[] = array(
                "name" => "Database Charset UTF8",
                "state" => ($result['Value'] == "utf8") ? "ok" : "error"
            );

            // create table
            $queryCheck = true;
            try {
                $db->query("CREATE TABLE __pimcore_req_check (
                  id int(11) NOT NULL AUTO_INCREMENT,
                  field varchar(255) CHARACTER SET latin1 NULL DEFAULT NULL,
                  PRIMARY KEY (id)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "CREATE TABLE",
                "state" => $queryCheck ? "ok" : "error"
            );

            // alter table
            $queryCheck = true;
            try {
                $db->query("ALTER TABLE __pimcore_req_check ADD COLUMN alter_field varchar(255) NULL DEFAULT NULL");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "ALTER TABLE",
                "state" => $queryCheck ? "ok" : "error"
            );

            // Manage indexes
            $queryCheck = true;
            try {
                $db->query("ALTER TABLE __pimcore_req_check
                  CHANGE COLUMN id id int(11) NOT NULL,
                  CHANGE COLUMN field field varchar(255) NULL DEFAULT NULL,
                  CHANGE COLUMN alter_field alter_field varchar(255) NULL DEFAULT NULL,
                  ADD INDEX field (field(255)),
                  DROP PRIMARY KEY ,
                 DEFAULT CHARSET=utf8");

                $db->query("ALTER TABLE __pimcore_req_check
                  CHANGE COLUMN id id int(11) NOT NULL AUTO_INCREMENT,
                  CHANGE COLUMN field field varchar(255) NULL DEFAULT NULL,
                  CHANGE COLUMN alter_field alter_field varchar(255) NULL DEFAULT NULL,
                  ADD PRIMARY KEY (id) ,
                 DEFAULT CHARSET=utf8");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "Manage Indexes",
                "state" => $queryCheck ? "ok" : "error"
            );

            // insert data
            $queryCheck = true;
            try {
                $db->insert("__pimcore_req_check", array(
                    "field" => uniqid(),
                    "alter_field" => uniqid()
                ));
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "INSERT",
                "state" => $queryCheck ? "ok" : "error"
            );

            // update
            $queryCheck = true;
            try {
                $db->update("__pimcore_req_check", array(
                    "field" => uniqid(),
                    "alter_field" => uniqid()
                ));
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "UPDATE",
                "state" => $queryCheck ? "ok" : "error"
            );

            // select
            $queryCheck = true;
            try {
                $db->fetchAll("SELECT * FROM __pimcore_req_check");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "SELECT",
                "state" => $queryCheck ? "ok" : "error"
            );


            // create view
            $queryCheck = true;
            try {
                $db->query("CREATE OR REPLACE VIEW __pimcore_req_check_view AS SELECT * FROM __pimcore_req_check");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "CREATE VIEW",
                "state" => $queryCheck ? "ok" : "error"
            );

            // select from view
            $queryCheck = true;
            try {
                $db->fetchAll("SELECT * FROM __pimcore_req_check_view");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "SELECT (from view)",
                "state" => $queryCheck ? "ok" : "error"
            );


            // delete
            $queryCheck = true;
            try {
                $db->delete("__pimcore_req_check");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "DELETE",
                "state" => $queryCheck ? "ok" : "error"
            );

            // show create view
            $queryCheck = true;
            try {
                $db->query("SHOW CREATE VIEW __pimcore_req_check_view");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "SHOW CREATE VIEW",
                "state" => $queryCheck ? "ok" : "error"
            );

            // show create table
            $queryCheck = true;
            try {
                $db->query("SHOW CREATE TABLE __pimcore_req_check");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "SHOW CREATE TABLE",
                "state" => $queryCheck ? "ok" : "error"
            );

            // drop view
            $queryCheck = true;
            try {
                $db->query("DROP VIEW __pimcore_req_check_view");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "DROP VIEW",
                "state" => $queryCheck ? "ok" : "error"
            );

            // drop table
            $queryCheck = true;
            try {
                $db->query("DROP TABLE __pimcore_req_check");
            } catch (Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = array(
                "name" => "DROP TABLE",
                "state" => $queryCheck ? "ok" : "error"
            );

        } else {
            die("Not possible... no or wrong database settings given.<br />Please fill out the MySQL Settings in the install form an click again on `Check Requirements´");
        }


        // filesystem checks

        // website/var writable
        $websiteVarWritable = true;
        $files = rscandir(PIMCORE_WEBSITE_PATH . "/var");

        foreach ($files as $file) {
            if (!is_writable($file)) {
                $websiteVarWritable = false;
            }
        }

        $checksFS[] = array(
            "name" => "/website/var/ writeable",
            "state" => $websiteVarWritable ? "ok" : "error"
        );

        // pimcore writeable
        $checksFS[] = array(
            "name" => "/pimcore/ writeable",
            "state" => Pimcore_Update::isWriteable() ? "ok" : "warning"
        );


        $this->view->checksPHP = $checksPHP;
        $this->view->checksMySQL = $checksMySQL;
        $this->view->checksFS = $checksFS;
    }
}
