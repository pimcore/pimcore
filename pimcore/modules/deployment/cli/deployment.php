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

include_once("../../../cli/startup.php");
Pimcore_Tool_Console::checkExecutingUser();

$deploymentEnabled = false;
if(is_readable(PIMCORE_DEPLOYMENT_CONFIG_FILE)){
    $deploymentConfig = new Zend_Config_Xml(PIMCORE_DEPLOYMENT_CONFIG_FILE);
    if($deploymentConfig->enabled){
        $deploymentEnabled = true;
    }
}
if(!$deploymentEnabled){
    die("\nDeployment is not enabled - Please enable it in the config file: " . PIMCORE_DEPLOYMENT_CONFIG_FILE . "\n");
}

$lockKey = 'pimcore_deployment';
Tool_Lock::acquire($lockKey);
Deployment_Factory::getInstance()->getAdapter()->executeTask();
Tool_Lock::release($lockKey);