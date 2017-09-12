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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../pimcore/config/bootstrap.php';

$request = Request::createFromGlobals();

// set current request as property on tool as there's no
// request stack available yet
Tool::setCurrentRequest($request);

/** @var \Pimcore\Kernel $kernel */
$kernel = require_once __DIR__ . '/../pimcore/config/kernel.php';

// redirect to installer if pimcore is not installed
if (!is_file(\Pimcore\Config::locateConfigFile('system.php'))) {
    if (file_exists(__DIR__ . '/install.php')) {
        (new RedirectResponse('/install', Response::HTTP_FOUND))->send();
        return;
    }

    throw new RuntimeException('Pimcore is not installed and the installer is not available. Please add the installer or install via command line.');
}

// reset current request - will be read from request stack from now on
Tool::setCurrentRequest(null);

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
