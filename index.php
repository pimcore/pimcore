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
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/pimcore/config/constants.php';
require_once __DIR__ . '/app/autoload.php';

$debug = Pimcore::inDebugMode();
if ($debug) {
    Debug::enable();
}

$kernel = new AppKernel(Config::getEnvironment(), $debug);
$kernel->loadClassCache();

if (defined('PIMCORE_SYMFONY_MODE') && PIMCORE_SYMFONY_MODE) {
    $request  = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();

    $kernel->terminate($request, $response);
} else {
    try {
        // initialize environment
        $kernel->boot();

        Pimcore::run();
    } catch (Exception $e) {
        // handle exceptions, log to file
        if (class_exists("Pimcore\\Logger")) {
            \Pimcore\Logger::emerg($e);
        }

        throw $e;
    }
}
