<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
