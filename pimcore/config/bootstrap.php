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

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/autoload.php';

$phpLog = PIMCORE_LOG_DIRECTORY . '/php.log';
if (is_writable(PIMCORE_LOG_DIRECTORY)) {
    ini_set('error_log', $phpLog);
    ini_set('log_errors', '1');
}
