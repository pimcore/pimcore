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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

/**
 * initializes Pimcore before Phing is executed
 * required to get the autoloader...
 */
require_once dirname(__FILE__) . '/../../../../../../cli/startup.php';
set_include_path(get_include_path() . PATH_SEPARATOR . PIMCORE_PATH . '/lib/PEAR');

if(Pimcore_API_Plugin_Broker::getInstance()->hasModule('Deployment_Module')){
    //add system and ext path to inlude path to prevent autoloading warning, that PropertyPromptTask.php and VersionTask.php could not be loaded
    set_include_path(get_include_path() . PATH_SEPARATOR . PIMCORE_PATH . '/modules/deployment/lib/Deployment/Phing/classes/phing/tasks/system/');
    set_include_path(get_include_path() . PATH_SEPARATOR . PIMCORE_PATH . '/modules/deployment/lib/Deployment/Phing/classes/phing/tasks/ext/');

    $autoloader = Zend_Loader_Autoloader::getInstance();
    #$autoloader->suppressNotFoundWarnings(true); //disable warning because Zend_Loader tries to load Phing classes...

    // do some general deployment setup stuff
    $deploymentAdapter = Deployment_Factory::getInstance()->getAdapter();
    $deploymentAdapter->setCommandLineParams();

    $deploymentStartup = PIMCORE_CONFIGURATION_DIRECTORY . "/deployment-startup.php";
    if(@is_file($deploymentStartup)) {
        include_once($deploymentStartup);
    }else{
        $argv = $deploymentAdapter->getCleanedArgv($argv);
    }
}
