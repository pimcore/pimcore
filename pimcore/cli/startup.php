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
error_reporting(E_ALL ^ E_NOTICE);

?>