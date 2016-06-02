<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Model\Tool;

class Install_IndexController extends \Pimcore\Controller\Action
{
    public function init()
    {
        parent::init();

        $maxExecutionTime = 300;
        @ini_set("max_execution_time", $maxExecutionTime);
        set_time_limit($maxExecutionTime);

        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
        @ini_set("display_errors", "On");
        $front = \Zend_Controller_Front::getInstance();
        $front->throwExceptions(true);
        
        \Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');

        if (is_file(\Pimcore\Config::locateConfigFile("system.php"))) {
            $this->redirect("/admin");
        }
    }

    public function indexAction()
    {
        $errors = [];

        // check permissions
        $files = rscandir(PIMCORE_WEBSITE_VAR . "/");

        foreach ($files as $file) {
            if (is_dir($file) && !is_writable($file)) {
                $errors[] = "Please ensure that the entire /" . PIMCORE_WEBSITE_VAR . " directory is recursively writeable.";
                break;
            }
        }

        $this->view->errors = $errors;
    }

    public function installAction()
    {

        // database configuration host/unix socket
        $dbConfig = [
            'username' => $this->getParam("mysql_username"),
            'password' => $this->getParam("mysql_password"),
            'dbname' => $this->getParam("mysql_database")
        ];

        $hostSocketValue = $this->getParam("mysql_host_socket");
        if (file_exists($hostSocketValue)) {
            $dbConfig["unix_socket"] = $hostSocketValue;
        } else {
            $dbConfig["host"] = $hostSocketValue;
            $dbConfig["port"] = $this->getParam("mysql_port");
        }

        // try to establish a mysql connection
        try {
            $db = \Zend_Db::factory($this->getParam("mysql_adapter"), $dbConfig);

            $db->getConnection();

            // check utf-8 encoding
            $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
            if (!in_array($result['Value'], ["utf8", "utf8mb4"])) {
                $errors[] = "Database charset is not utf-8";
            }
        } catch (\Exception $e) {
            $errors[] = "Couldn't establish connection to MySQL: " . $e->getMessage();
        }

        // check username & password
        if (strlen($this->getParam("admin_password")) < 4 || strlen($this->getParam("admin_username")) < 4) {
            $errors[] = "Username and password should have at least 4 characters";
        }

        if (empty($errors)) {
            $setup = new Tool\Setup();

            // check if /website folder already exists, if not, look for /website_demo & /website_example
            // /website_install is just for testing in dev environment
            if (!is_dir(PIMCORE_WEBSITE_PATH)) {
                foreach (["website_install", "website_demo", "website_example"] as $websiteDir) {
                    $dir = PIMCORE_DOCUMENT_ROOT . "/" . $websiteDir;
                    if (is_dir($dir)) {
                        rename($dir, PIMCORE_WEBSITE_PATH);
                        break;
                    }
                }
            }

            $setup->config([
                "database" => [
                    "adapter" => $this->getParam("mysql_adapter"),
                    "params" => $dbConfig
                ],
            ]);

            // look for a template dump
            // eg. for use with demo installer
            $dbDataFile = PIMCORE_WEBSITE_PATH . "/dump/data.sql";
            $contentConfig = [
                "username" => $this->getParam("admin_username"),
                "password" => $this->getParam("admin_password")
            ];

            if (!file_exists($dbDataFile)) {
                $setup->database();
                \Pimcore::initConfiguration();
                $setup->contents($contentConfig);
            } else {
                $setup->database();
                $setup->insertDump($dbDataFile);
                \Pimcore::initConfiguration();
                $setup->createOrUpdateUser($contentConfig);
            }

            $this->_helper->json([
                "success" => true
            ]);
        } else {
            echo implode("<br />", $errors);
            die();
        }
    }
}
