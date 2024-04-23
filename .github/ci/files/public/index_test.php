<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

use Pimcore\Bootstrap;
use Pimcore\Tool;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

include __DIR__ . "/../vendor/autoload.php";

define('PIMCORE_PROJECT_ROOT', __DIR__ . '/..');
define('APP_ENV', 'test');

Bootstrap::setProjectRoot();
Bootstrap::bootstrap();

$request = Request::createFromGlobals();

// set current request as property on tool as there's no
// request stack available yet
Tool::setCurrentRequest($request);

/** @var \Pimcore\Kernel $kernel */
$kernel = Bootstrap::kernel();

// reset current request - will be read from request stack from now on
Tool::setCurrentRequest(null);

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);

