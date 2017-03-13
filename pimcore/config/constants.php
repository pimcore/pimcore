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

// configure some constants needed by pimcore
$projectRoot = realpath(__DIR__ . '/../..');

$customConstants = $projectRoot . '/app/constants.php';
if (file_exists($customConstants)) {
    include_once $customConstants;
}

if (!defined('PIMCORE_PROJECT_ROOT')) {
    define('PIMCORE_PROJECT_ROOT', $projectRoot);
}

if (!defined('PIMCORE_APP_ROOT')) {
    define('PIMCORE_APP_ROOT', PIMCORE_PROJECT_ROOT . '/app');
}

if (!defined('PIMCORE_WEB_ROOT')) {
    define('PIMCORE_WEB_ROOT', PIMCORE_PROJECT_ROOT . '/web');
}

if (!defined('PIMCORE_PATH')) {
    define('PIMCORE_PATH', PIMCORE_PROJECT_ROOT . '/pimcore');
}

// var directory
if (is_array($_SERVER)
    && array_key_exists('HTTP_X_PIMCORE_UNIT_TEST_REQUEST', $_SERVER)
    && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', $_SERVER['SERVER_ADDR']])
) {
    // change the var directory for unit tests
    if (!defined('PIMCORE_PRIVATE_VAR')) {
        define('PIMCORE_PRIVATE_VAR', PIMCORE_PROJECT_ROOT . '/var/tests/tmp/var');
    }

    // change the var directory for unit tests
    if (!defined('PIMCORE_PUBLIC_VAR')) {
        define('PIMCORE_PUBLIC_VAR', PIMCORE_PROJECT_ROOT . '/var/tests/tmp/var');
    }
} else {
    if (!defined('PIMCORE_PRIVATE_VAR')) {
        define('PIMCORE_PRIVATE_VAR', PIMCORE_PROJECT_ROOT . '/var');
    }

    if (!defined('PIMCORE_PUBLIC_VAR')) {
        define('PIMCORE_PUBLIC_VAR', PIMCORE_WEB_ROOT . '/var');
    }
}

// pimcore config files
if (!defined('PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY')) {
    define('PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY', PIMCORE_APP_ROOT . '/config/pimcore');
}

if (!defined('PIMCORE_CONFIGURATION_DIRECTORY')) {
    define('PIMCORE_CONFIGURATION_DIRECTORY', PIMCORE_PRIVATE_VAR . '/config');
}

if (!defined('PIMCORE_ASSET_DIRECTORY')) {
    define('PIMCORE_ASSET_DIRECTORY', PIMCORE_PUBLIC_VAR . '/assets');
}
if (!defined('PIMCORE_VERSION_DIRECTORY')) {
    define('PIMCORE_VERSION_DIRECTORY', PIMCORE_PRIVATE_VAR . '/versions');
}
if (!defined('PIMCORE_WEBDAV_TEMP')) {
    define('PIMCORE_WEBDAV_TEMP', PIMCORE_PRIVATE_VAR . '/webdav');
}
if (!defined('PIMCORE_LOG_DIRECTORY')) {
    define('PIMCORE_LOG_DIRECTORY', PIMCORE_PRIVATE_VAR . '/logs');
}
if (!defined('PIMCORE_LOG_FILEOBJECT_DIRECTORY')) {
    define('PIMCORE_LOG_FILEOBJECT_DIRECTORY', PIMCORE_LOG_DIRECTORY . '/fileobjects');
}
if (!defined('PIMCORE_LOG_MAIL_TEMP')) {
    define('PIMCORE_LOG_MAIL_TEMP', PIMCORE_LOG_DIRECTORY . '/mail');
}
if (!defined('PIMCORE_TEMPORARY_DIRECTORY')) {
    define('PIMCORE_TEMPORARY_DIRECTORY', PIMCORE_PUBLIC_VAR . '/tmp');
}
if (!defined('PIMCORE_CACHE_DIRECTORY')) {
    define('PIMCORE_CACHE_DIRECTORY', PIMCORE_PRIVATE_VAR . '/cache/pimcore');
}
if (!defined('PIMCORE_CLASS_DIRECTORY')) {
    define('PIMCORE_CLASS_DIRECTORY', PIMCORE_PRIVATE_VAR . '/classes');
}
if (!defined('PIMCORE_CUSTOMLAYOUT_DIRECTORY')) {
    define('PIMCORE_CUSTOMLAYOUT_DIRECTORY', PIMCORE_CLASS_DIRECTORY . '/customlayouts');
}
if (!defined('PIMCORE_BACKUP_DIRECTORY')) {
    define('PIMCORE_BACKUP_DIRECTORY', PIMCORE_PRIVATE_VAR . '/backup');
}
if (!defined('PIMCORE_RECYCLEBIN_DIRECTORY')) {
    define('PIMCORE_RECYCLEBIN_DIRECTORY', PIMCORE_PRIVATE_VAR . '/recyclebin');
}
if (!defined('PIMCORE_SYSTEM_TEMP_DIRECTORY')) {
    define('PIMCORE_SYSTEM_TEMP_DIRECTORY', PIMCORE_PRIVATE_VAR . '/system');
}
if (!defined('PIMCORE_LOG_MAIL_PERMANENT')) {
    define('PIMCORE_LOG_MAIL_PERMANENT', PIMCORE_PRIVATE_VAR . '/email');
}



