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

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/pimcore/config/constants.php';

if (defined('PIMCORE_SYMFONY_MODE') && PIMCORE_SYMFONY_MODE) {
    require __DIR__ . '/app/autoload.php';
    require_once __DIR__ . '/pimcore/config/setup.php';

    $debug = Pimcore::inDebugMode();
    if ($debug) {
        Debug::enable();
    }

    $kernel = new AppKernel(\Pimcore\Config::getEnvironment(), $debug);
    $kernel->loadClassCache();

    $request  = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();

    $kernel->terminate($request, $response);
} else {
    include_once __DIR__ . '/pimcore/config/startup.php';

    try {
        Pimcore::run();
    } catch (Exception $e) {
        // handle exceptions, log to file
        if (class_exists("Pimcore\\Logger")) {
            \Pimcore\Logger::emerg($e);
        }

        throw $e;
    }
}
