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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Model\Tool;

class Install_IndexController extends \Pimcore\Controller\Action {


    public function init() {
        parent::init();

        $maxExecutionTime = 300;
        @ini_set("max_execution_time", $maxExecutionTime);
        set_time_limit($maxExecutionTime);

		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
		@ini_set("display_errors", "On");
		$front = \Zend_Controller_Front::getInstance();
		$front->throwExceptions(true);
		
        \Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');

        if (is_file(PIMCORE_CONFIGURATION_SYSTEM)) {
            $this->redirect("/admin");
        }
    }

    public function indexAction() {

        $errors = array();

        // check permissions
        $files = rscandir(PIMCORE_WEBSITE_VAR . "/");

        foreach ($files as $file) {
            if (is_dir($file) && !is_writable($file)) {
                $errors[] = "Please ensure that the whole /" . PIMCORE_WEBSITE_VAR . " folder is writeable (recursivly)";
                break;
            }
        }

        $this->view->errors = $errors;
    }

    public function installAction() {

        // database configuration host/unix socket
        $dbConfig = [
            'username' => $this->getParam("mysql_username"),
            'password' => $this->getParam("mysql_password"),
            'dbname' => $this->getParam("mysql_database")
        ];

        $hostSocketValue = $this->getParam("mysql_host_socket");
        if(file_exists($hostSocketValue)) {
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
            if ($result['Value'] != "utf8") {
                $errors[] = "Database charset is not utf-8";
            }
        }
        catch (\Exception $e) {
            $errors[] = "Couldn't establish connection to mysql: " . $e->getMessage();
        }

        // check username & password
        if (strlen($this->getParam("admin_password")) < 4 || strlen($this->getParam("admin_username")) < 4) {
            $errors[] = "Username and password should have at least 4 characters";
        }

        if (empty($errors)) {

            $setup = new Tool\Setup();

            // check if /website folder already exists, if not, look for /website_demo & /website_example
            // /website_install is just for testing in dev environment
            if(!is_dir(PIMCORE_WEBSITE_PATH)) {
                foreach(["website_install", "website_demo", "website_example"] as $websiteDir) {
                    $dir = PIMCORE_DOCUMENT_ROOT . "/" . $websiteDir;
                    if(is_dir($dir)) {
                        rename($dir, PIMCORE_WEBSITE_PATH);
                        break;
                    }
                }
            }

            $setup->config(array(
                "database" => array(
                    "adapter" => $this->getParam("mysql_adapter"),
                    "params" => $dbConfig
                ),
            ));

			// look for a template dump
			// eg. for use with demo installer
			$dbDataFile = PIMCORE_WEBSITE_PATH . "/dump/data.sql";
			$contentConfig = array(
				"username" => $this->getParam("admin_username"),
				"password" => $this->getParam("admin_password")
			);

			if(!file_exists($dbDataFile)) {
                $setup->database();
                \Pimcore::initConfiguration();
				$setup->contents($contentConfig);
			} else {
				$setup->insertDump($dbDataFile);
                \Pimcore::initConfiguration();
				$setup->createOrUpdateUser($contentConfig);
			}

            $this->_helper->json(array(
                "success" => true
            ));
        }

        else {
            echo implode("<br />", $errors);
            die();
        }

    }
} 
