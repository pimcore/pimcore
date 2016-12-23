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

use Pimcore\Config;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Symfony\Component\Console\Input\ArgvInput;

// determines if we're in Pimcore\Console mode
$pimcoreConsole = (defined('PIMCORE_CONSOLE') && true === PIMCORE_CONSOLE);
$symfonyMode    = (defined('PIMCORE_SYMFONY_MODE') && PIMCORE_SYMFONY_MODE);

$workingDirectory = getcwd();
chdir(__DIR__);

include_once __DIR__ . '/../config/constants.php';
include_once __DIR__ . '/../config/autoload.php';

if ($pimcoreConsole) {
    $input = new ArgvInput();
    $env   = $input->getParameterOption(['--env', '-e'], Config::getEnvironment());
    $debug = Pimcore::inDebugMode() && !$input->hasParameterOption(['--no-debug', '']);

    Config::setEnvironment($env);
    if (!defined('PIMCORE_DEBUG')) {
        define('PIMCORE_DEBUG', $debug);
    }
}

$kernel = include_once __DIR__ . '/../config/kernel.php';

chdir($workingDirectory);

if (!$symfonyMode) {
    // CLI \Zend_Controller_Front Setup, this is required to make it possible to make use of all rendering features
    // this includes $this->action() in templates, ...
    $front = \Zend_Controller_Front::getInstance();
    Pimcore::initControllerFront($front);

    $request = new \Zend_Controller_Request_Http();
    $request->setModuleName(PIMCORE_FRONTEND_MODULE);
    $request->setControllerName("default");
    $request->setActionName("default");
    $front->setRequest($request);
    $front->setResponse(new \Zend_Controller_Response_Cli());
}

//Activate Inheritance for cli-scripts
\Pimcore::unsetAdminMode();
Document::setHideUnpublished(true);
Object\AbstractObject::setHideUnpublished(true);
Object\AbstractObject::setGetInheritedValues(true);
Object\Localizedfield::setGetFallbackValues(true);

// CLI has no memory/time limits
@ini_set('memory_limit', -1);
@ini_set('max_execution_time', -1);
@ini_set('max_input_time', -1);

// Error reporting is enabled in CLI
@ini_set("display_errors", "On");
@ini_set("display_startup_errors", "On");
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// Pimcore\Console handles maintenance mode through the AbstractCommand
if (!$pimcoreConsole) {
    // skip if maintenance mode is on and the flag is not set
    // we cannot use \Zend_Console_Getopt here because it doesn't allow to be called twice (unrecognized parameter, ...)
    if (\Pimcore\Tool\Admin::isInMaintenanceMode() && !in_array("--ignore-maintenance-mode", $_SERVER['argv'])) {
        die("in maintenance mode -> skip\nset the flag --ignore-maintenance-mode to force execution \n");
    }
}

return $kernel;
