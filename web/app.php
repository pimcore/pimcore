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

use Pimcore\Tool;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('PIMCORE_PROJECT_ROOT')) {
    define(
        'PIMCORE_PROJECT_ROOT',
        getenv('PIMCORE_PROJECT_ROOT')
            ?: getenv('REDIRECT_PIMCORE_PROJECT_ROOT')
            ?: realpath(__DIR__ . '/..')
    );
}

require_once PIMCORE_PROJECT_ROOT . '/pimcore/config/bootstrap.php';

$request = Request::createFromGlobals();

// set current request as property on tool as there's no
// request stack available yet
Tool::setCurrentRequest($request);

// redirect to installer if pimcore is not installed
if (!is_file(\Pimcore\Config::locateConfigFile('system.php'))) {
    if (file_exists(__DIR__ . '/install.php')) {
        (new RedirectResponse('/install', Response::HTTP_FOUND))->send();
        return;
    }

    Debug::enable(E_ALL, true);
    throw new RuntimeException('Pimcore is not installed and the installer is not available. Please add the installer or install via command line.');
}

/** @var \Pimcore\Kernel $kernel */
$kernel = require_once PIMCORE_PROJECT_ROOT . '/pimcore/config/kernel.php';

// reset current request - will be read from request stack from now on
Tool::setCurrentRequest(null);

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
