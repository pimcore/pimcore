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
$oldErrorReporting = error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

/** @var $loader \Composer\Autoload\ClassLoader */
$loader = include PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
require_once __DIR__ . '/constants.php';
if (false === PIMCORE_ALLOW_PHP_ERROR_LOG_OVERRIDE) {
    error_reporting($oldErrorReporting);
}
// needs to be included manually since the updater invalidates the classmap and therefore cannot find the class anymore during an update
require_once PIMCORE_PATH . '/lib/Pimcore.php';
Pimcore::setAutoloader($loader);
require_once __DIR__ . '/autoload.php';

$phpLog = PIMCORE_LOG_DIRECTORY . '/php.log';
if (PIMCORE_ALLOW_PHP_ERROR_LOG_OVERRIDE && is_writable(PIMCORE_LOG_DIRECTORY)) {
    ini_set('error_log', $phpLog);
    ini_set('log_errors', '1');
}
