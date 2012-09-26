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


$workingDirectory = getcwd();
chdir(__DIR__);
include_once("../config/startup.php");
chdir($workingDirectory);


// CLI Zend_Controller_Front Setup, this is required to make it possible to make use of all rendering features
// this includes $this->action() in templates, ...
$front = Zend_Controller_Front::getInstance();
Pimcore::initControllerFront($front);

$request = new Zend_Controller_Request_Http();
$request->setModuleName(PIMCORE_FRONTEND_MODULE);
$request->setControllerName("default");
$request->setActionName("default");
$front->setRequest($request);
$front->setResponse(new Zend_Controller_Response_Cli());

// generic pimcore setup
Pimcore::setSystemRequirements();
Pimcore::initAutoloader();
Pimcore::initConfiguration();
Pimcore::setupFramework();
Pimcore::initLogger();
Pimcore::initModules();
Pimcore::initPlugins();

//Activate Inheritance for cli-scripts
Pimcore::unsetAdminMode();
Document::setHideUnpublished(true);
Object_Abstract::setHideUnpublished(true);
Object_Abstract::setGetInheritedValues(true);

// Error reporting is enabled in CLI
@ini_set("display_errors", "On");
@ini_set("display_startup_errors", "On");
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// skip if maintenance mode is on and the flag is not set
// we cannot use Zend_Console_Getopt here because it doesn't allow to be called twice (unrecognized parameter, ...)
if(Pimcore_Tool_Admin::isInMaintenanceMode() && !in_array("--ignore-maintenance-mode", $_SERVER['argv'])) {
    die("in maintenance mode -> skip\nset the falg --ignore-maintenance-mode to force execution \n");
}
