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
$projectRoot = getenv('PIMCORE_PROJECT_ROOT')
    ?: getenv('REDIRECT_PIMCORE_PROJECT_ROOT')
    ?: realpath(__DIR__ . '/../..');

$customConstants = $projectRoot . '/app/constants.php';
if (file_exists($customConstants)) {
    include_once $customConstants;
}

if (!defined('PIMCORE_PROJECT_ROOT')) {
    define('PIMCORE_PROJECT_ROOT', $projectRoot);
}

if (!defined('PIMCORE_COMPOSER_PATH')) {
    define('PIMCORE_COMPOSER_PATH', PIMCORE_PROJECT_ROOT . '/vendor');
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

if (!defined('PIMCORE_PRIVATE_VAR')) {
    define('PIMCORE_PRIVATE_VAR', PIMCORE_PROJECT_ROOT . '/var');
}

if (!defined('PIMCORE_PUBLIC_VAR')) {
    define('PIMCORE_PUBLIC_VAR', PIMCORE_WEB_ROOT . '/var');
}

// special directories for tests
// test mode can bei either controlled by a constant or an env variable
if ((defined('PIMCORE_TEST') && PIMCORE_TEST) || getenv('PIMCORE_TEST') === '1') {
    // override and initialize directories
    define('PIMCORE_CLASS_DIRECTORY', PIMCORE_PATH . '/tests/_output/var/classes');
    define('PIMCORE_ASSET_DIRECTORY', PIMCORE_WEB_ROOT . '/var/tests/assets');

    if (!defined('PIMCORE_TEST')) {
        define('PIMCORE_TEST', true);
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
if (!defined('PIMCORE_RECYCLEBIN_DIRECTORY')) {
    define('PIMCORE_RECYCLEBIN_DIRECTORY', PIMCORE_PRIVATE_VAR . '/recyclebin');
}
if (!defined('PIMCORE_SYSTEM_TEMP_DIRECTORY')) {
    define('PIMCORE_SYSTEM_TEMP_DIRECTORY', PIMCORE_PRIVATE_VAR . '/system');
}
if (!defined('PIMCORE_LOG_MAIL_PERMANENT')) {
    define('PIMCORE_LOG_MAIL_PERMANENT', PIMCORE_PRIVATE_VAR . '/email');
}
if (!defined('PIMCORE_USERIMAGE_DIRECTORY')) {
    define('PIMCORE_USERIMAGE_DIRECTORY', PIMCORE_PRIVATE_VAR . '/user-image');
}
