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

class Install_IndexController extends Pimcore_Controller_Action {


    public function init() {
        parent::init();
        Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');

        if (is_file(PIMCORE_CONFIGURATION_SYSTEM)) {
            $this->_redirect("/admin");
        }
    }

    public function indexAction() {

        $errors = array();

        // check permissions
        $files = rscandir(PIMCORE_WEBSITE_PATH . "/var/");

        foreach ($files as $file) {
            if (is_dir($file) && !is_writable($file)) {
                $errors[] = "Please ensure that the whole /" . PIMCORE_FRONTEND_MODULE . "/var folder is writeable (recursivly)";
                break;
            }
        }

        $this->view->errors = $errors;
    }

    public function installAction() {

        // try to establish a mysql connection
        try {
            $db = new Zend_Db_Adapter_Pdo_Mysql(array(
                'host' => $this->_getParam("mysql_host"),
                'username' => $this->_getParam("mysql_username"),
                'password' => $this->_getParam("mysql_password"),
                'dbname' => $this->_getParam("mysql_database"),
                "port" => $this->_getParam("mysql_port")
            ));

            $db->getConnection();

            // check utf-8 encoding
            $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
            if ($result['Value'] != "utf8") {
                $errors[] = "Database charset is not utf-8";
            }
        }
        catch (Exception $e) {
            $errors[] = "Couldn't establish connection to mysql";
        }

        // check username & password
        if (strlen($this->_getParam("admin_password")) < 4 || strlen($this->_getParam("admin_username")) < 4) {
            $errors[] = "Username and password should have at least 4 characters";
        }

        if (empty($errors)) {

            // write configuration file
            $settings = array(
                "general" => array(
                    "timezone" => "Europe/Berlin",
                    "domain" => "",
                    "language" => "en",
                    "validLanguages" => "en",
                    "debug" => "1",
                    "devmode" => "",
                    "theme" => "/pimcore/static/js/lib/ext/resources/css/xtheme-blue.css",
                    "welcomescreen" => "1",
                    "loglevel" => array(
                        "debug" => "",
                        "info" => "",
                        "notice" => "",
                        "warning" => "",
                        "error" => "",
                        "critical" => "1",
                        "alert" => "1",
                        "emergency" => "1"
                    )
                ),
                "database" => array(
                    "adapter" => "Pdo_Mysql",
                    "params" => array(
                        "host" => $this->_getParam("mysql_host"),
                        "username" => $this->_getParam("mysql_username"),
                        "password" => $this->_getParam("mysql_password"),
                        "dbname" => $this->_getParam("mysql_database"),
                        "port" => $this->_getParam("mysql_port"),
                    )
                ),
                "documents" => array(
                    "versions" => array(
                        "days" => "",
                        "steps" => "20"
                    ),
                    "default_controller" => "default",
                    "default_action" => "default",
                    "error_page" => "/",
                    "allowtrailingslash" => "no",
                    "allowcapitals" => "no"
                ),
                "objects" => array(
                    "versions" => array(
                        "days" => "",
                        "steps" => "20"
                    )
                ),
                "assets" => array(
                    "webdav" => array(
                        "hostname" => ""
                    ),
                    "versions" => array(
                        "days" => "",
                        "steps" => "20"
                    )
                ),
                "services" => array(
                    "youtube" => array(
                        "apikey" => ""
                    ),
                    "googlemaps" => array(
                        "apikey" => ""
                    ),
                    "translate" => array(
                        "apikey" => ""
                    ),
                    "google" => array(
                        "username" => "",
                        "password" => ""
                    )
                ),
                "plugins" => array(
                    "repositories" => "plugins.pimcore.org"
                ),
                "cache" => array(
                    "enabled" => "",
                    "excludePatterns" => "",
                    "excludeCookie" => "pimcore_admin_sid"
                ),
                "outputfilters" => array(
                    "imagedatauri" => "",
                    "htmlminify" => "",
                    "less" => "",
                    "cssminify" => "",
                    "javascriptminify" => "",
                    "javascriptminifyalgorithm" => "",
                    "cdn" => "",
                    "cdnhostnames" => "",
                    "cdnpatterns" => ""
                ),
                "webservice" => array(
                    "enabled" => ""
                ),
                "httpclient" => array(
                    "adapter" => "Zend_Http_Client_Adapter_Socket",
                    "proxy_host" => "",
                    "proxy_port" => "",
                    "proxy_user" => "",
                    "proxy_pass" => "",
                )
            );


            $config = new Zend_Config($settings, true);
            $writer = new Zend_Config_Writer_Xml(array(
                "config" => $config,
                "filename" => PIMCORE_CONFIGURATION_SYSTEM
            ));
            $writer->write();


            // insert db dump
            $db->getConnection()->exec(file_get_contents(PIMCORE_PATH . "/modules/install/mysql/install.sql"));

            Pimcore::initConfiguration();

            sleep(4);

            $user = User::create(array(
                "parentId" => 0,
                "username" => $this->_getParam("admin_username"),
                "password" => Pimcore_Tool_Authentication::getPasswordHash($this->_getParam("admin_username"),$this->_getParam("admin_password")),
                "hasCredentials" => true,
                "active" => true
            ));
            $user->setAdmin(true);
            $user->save();


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
