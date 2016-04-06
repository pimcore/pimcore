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

/**
 * THIS FILE ONLY EXISTS FOR COMPATIBILITY REASONS
 * PLEASE USE THE FOLLOWING COMMAND INSTEAD:
 *
 * php pimcore/cli/console.php maintenance
 *
 */

chdir(__DIR__);

$arguments = $_SERVER['argv'];
array_splice($arguments, 1, 0, "maintenance");
$_SERVER['argv'] = $arguments;

ob_start();

include_once("console.php");
