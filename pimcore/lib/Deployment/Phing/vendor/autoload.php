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

/**
 * initializes Pimcore before Phing is executed
 * required to get the autoloader...
 */
require_once dirname(__FILE__) . '/../../../../cli/startup.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->suppressNotFoundWarnings(true); //disable warning because Zend_Loader tries to load Phing classes...
// do some general deployment setup stuff
ini_set('display_errors','on');
$deploymentStartup = PIMCORE_CONFIGURATION_DIRECTORY . "/deployment-startup.php";
if(@is_file($deploymentStartup)) {
    include_once($deploymentStartup);
}