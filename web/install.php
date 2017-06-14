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
use Pimcore\Install\InstallerKernel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

$maxExecutionTime = 300;
@ini_set('max_execution_time', $maxExecutionTime);
set_time_limit($maxExecutionTime);

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
@ini_set('display_errors', 'On');

require_once __DIR__ . '/../pimcore/config/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
include_once(__DIR__ . '/../pimcore/lib/helper-functions.php');

// no installer if Pimcore is already installed
if (is_file(Config::locateConfigFile('system.php'))) {
    $response = new RedirectResponse('/admin?_dc=' . microtime(true), Response::HTTP_FOUND);
    $response->send();

    return;
}

$kernel   = new InstallerKernel(Config::getEnvironment(), true);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
