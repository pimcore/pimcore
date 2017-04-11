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
use Symfony\Component\Debug\Debug;

$debug = Pimcore::inDebugMode() || in_array(Config::getEnvironment(), ['dev', 'test']);
if ($debug) {
    Debug::enable();
    @ini_set("display_errors", "On");
}

$kernel = new AppKernel(Config::getEnvironment(), $debug);
Pimcore::setKernel($kernel);

$kernel->boot();

return $kernel;
