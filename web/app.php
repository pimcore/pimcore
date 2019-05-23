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
use Symfony\Component\HttpFoundation\Request;

include __DIR__ . "/../vendor/autoload.php";

\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::boostrap();

$request = Request::createFromGlobals();

// set current request as property on tool as there's no
// request stack available yet
Tool::setCurrentRequest($request);

// redirect to installer if pimcore is not installed
if (!is_file(\Pimcore\Config::locateConfigFile('system.php'))) {
    Debug::enable(E_ALL, true);
    throw new RuntimeException('Pimcore is not installed! Please install via command line.');
}

/** @var \Pimcore\Kernel $kernel */
$kernel = \Pimcore\Bootstrap::kernel();

// reset current request - will be read from request stack from now on
Tool::setCurrentRequest(null);

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
