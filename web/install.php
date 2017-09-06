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
@ini_set('display_errors', 'On');

$maxExecutionTime = 300;
@ini_set('max_execution_time', $maxExecutionTime);
set_time_limit($maxExecutionTime);

if (!defined('PIMCORE_PROJECT_ROOT')) {
    define(
        'PIMCORE_PROJECT_ROOT',
        getenv('PIMCORE_PROJECT_ROOT')
            ?: getenv('REDIRECT_PIMCORE_PROJECT_ROOT')
            ?: realpath(__DIR__ . '/..')
    );
}

/** @var $loader \Composer\Autoload\ClassLoader */
$loader = include PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
Pimcore::setAutoloader($loader);

require_once PIMCORE_PROJECT_ROOT . '/pimcore/config/constants.php';
require_once PIMCORE_PROJECT_ROOT . '/pimcore/lib/helper-functions.php';

// no installer if Pimcore is already installed
if (is_file(Config::locateConfigFile('system.php'))) {
    $response = new RedirectResponse('/admin?_dc=' . microtime(true), Response::HTTP_FOUND);
    $response->send();

    return;
}

$request = Request::createFromGlobals();

// always redirect to /install instead of /install.php
if ($request->getRequestUri() === '/install.php') {
    (new RedirectResponse('/install', Response::HTTP_MOVED_PERMANENTLY))->send();
    return;
}

$kernel = new InstallerKernel(Config::getEnvironment(), true);

$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
