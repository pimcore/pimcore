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
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

/** @var $loader \Composer\Autoload\ClassLoader */
$loader = include PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
require_once __DIR__ . '/constants.php';
if ( is_integer(PIMCORE_PHP_ERROR_REPORTING) )
    error_reporting(PIMCORE_PHP_ERROR_REPORTING);
// needs to be included manually since the updater invalidates the classmap and therefore cannot find the class anymore during an update
require_once PIMCORE_PATH . '/lib/Pimcore.php';
Pimcore::setAutoloader($loader);
require_once __DIR__ . '/autoload.php';

if ( "syslog" === PIMCORE_PHP_ERROR_LOG
     || is_writable( dirname(PIMCORE_PHP_ERROR_LOG) )
   ) {
    ini_set('error_log', PIMCORE_PHP_ERROR_LOG);
    ini_set('log_errors', '1');
}
