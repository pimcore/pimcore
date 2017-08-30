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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */
use Pimcore\Config;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Symfony\Component\Console\Input\ArgvInput;

// determines if we're in Pimcore\Console mode
$pimcoreConsole = (defined('PIMCORE_CONSOLE') && true === PIMCORE_CONSOLE);

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

/** @var \Pimcore\Kernel $kernel */
$kernel = include_once __DIR__ . '/../config/kernel.php';
$kernel->boot();

chdir($workingDirectory);

//Activate Inheritance for cli-scripts
\Pimcore::unsetAdminMode();
Document::setHideUnpublished(true);
DataObject\AbstractObject::setHideUnpublished(true);
DataObject\AbstractObject::setGetInheritedValues(true);
DataObject\Localizedfield::setGetFallbackValues(true);

// CLI has no memory/time limits
@ini_set('memory_limit', -1);
@ini_set('max_execution_time', -1);
@ini_set('max_input_time', -1);

// Error reporting is enabled in CLI
@ini_set('display_errors', 'On');
@ini_set('display_startup_errors', 'On');
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// Pimcore\Console handles maintenance mode through the AbstractCommand
if (!$pimcoreConsole) {
    // skip if maintenance mode is on and the flag is not set
    if (\Pimcore\Tool\Admin::isInMaintenanceMode() && !in_array('--ignore-maintenance-mode', $_SERVER['argv'])) {
        die("in maintenance mode -> skip\nset the flag --ignore-maintenance-mode to force execution \n");
    }
}

return $kernel;
