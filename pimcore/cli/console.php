#!/usr/bin/env php
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
use Symfony\Component\Console\Input\ArgvInput;

ob_get_clean();

define('PIMCORE_CONSOLE', true);

/** @var \Pimcore\Kernel $kernel */
$kernel = require_once __DIR__ . '/startup.php';

$application = new Pimcore\Console\Application($kernel);
$application->run();
