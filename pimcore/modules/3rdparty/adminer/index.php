<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

// adminer isn'T fully php 5.4 compatible
error_reporting(E_ERROR);

$workingDirectory = getcwd();
include("../../../cli/startup.php");
chdir($workingDirectory);

// start global session an keep it open (this is needed for the CSRF protections from adminer)
\Pimcore\Tool\Session::get();

// only for logged in users
$user = \Pimcore\Tool\Authentication::authenticateSession();
if(!$user instanceof User) {
    die("Authentication failed!");
}

if(!$user->isAllowed("database"))
{
	die("Permission denied!");
}

$conf = \Pimcore\Config::getSystemConfig()->database->params;
if(empty($_SERVER["QUERY_STRING"])) {
    header("Location: /pimcore/modules/3rdparty/adminer/index.php?username=" . $conf->username . "&db=" . $conf->dbname);
    exit;
}

// adminer plugin
function adminer_object() {

    // required to run any plugin
    include_once "./plugins/plugin.php";

    // autoloader
    foreach (glob("plugins/*.php") as $filename) {
        include_once "./$filename";
    }

    $plugins = array(
        new AdminerFrames(),
    );

	class AdminerPimcore extends AdminerPlugin {

        function name () {
            return "pimcore Adminer";
        }

		function permanentLogin() {
			// key used for permanent login
			return \Zend_Session::getId();
		}

        function login($login, $password) {
            return true;
        }

		function credentials() {
            $conf = \Pimcore\Config::getSystemConfig()->database->params;

            $host = $conf->host;
            if($conf->port) {
                $host .= ":" . $conf->port;
            }

			// server, username and password for connecting to database
			return array(
                $host, $conf->username, $conf->password
			);
		}

		function database() {
            $conf = \Pimcore\Config::getSystemConfig()->database->params;
			// database name, will be escaped by Adminer
			return $conf->dbname;
		}
    }

	return new AdminerPimcore($plugins);
}

include("./adminer.php");
