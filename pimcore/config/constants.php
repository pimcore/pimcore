<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

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

$resolveConstant = function (string $name, $default, bool $define = true) {
    // return constant if defined
    if (defined($name)) {
        return constant($name);
    }

    // load env var with fallback to REDIRECT_ prefixed env var
    $value = getenv($name) ?: getenv('REDIRECT_' . $name) ?: $default;

    if ($define) {
        define($name, $value);
    }

    return $value;
};

// load .env file if available
$dotEnvFile = PIMCORE_PROJECT_ROOT . '/.env';
if (file_exists($dotEnvFile)) {
    (new Dotenv())->load($dotEnvFile);
}

// load custom constants
$customConstantsFile = PIMCORE_PROJECT_ROOT . '/app/constants.php';
if (file_exists($customConstantsFile)) {
    include_once $customConstantsFile;
}

// basic paths
$resolveConstant('PIMCORE_COMPOSER_PATH', PIMCORE_PROJECT_ROOT . '/vendor');
$resolveConstant('PIMCORE_COMPOSER_FILE_PATH', PIMCORE_PROJECT_ROOT);
$resolveConstant('PIMCORE_PATH', PIMCORE_PROJECT_ROOT . '/pimcore');
$resolveConstant('PIMCORE_APP_ROOT', PIMCORE_PROJECT_ROOT . '/app');
$resolveConstant('PIMCORE_WEB_ROOT', PIMCORE_PROJECT_ROOT . '/web');
$resolveConstant('PIMCORE_PRIVATE_VAR', PIMCORE_PROJECT_ROOT . '/var');
$resolveConstant('PIMCORE_PUBLIC_VAR', PIMCORE_WEB_ROOT . '/var');

// special directories for tests
// test mode can bei either controlled by a constant or an env variable
$testMode = (bool)$resolveConstant('PIMCORE_TEST', false, false);
if ($testMode) {
    // override and initialize directories
    $resolveConstant('PIMCORE_CLASS_DIRECTORY', PIMCORE_PATH . '/tests/_output/var/classes');
    $resolveConstant('PIMCORE_ASSET_DIRECTORY', PIMCORE_WEB_ROOT . '/var/tests/assets');

    if (!defined('PIMCORE_TEST')) {
        define('PIMCORE_TEST', true);
    }
}

// paths relying on basic paths above
$resolveConstant('PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY',PIMCORE_APP_ROOT . '/config/pimcore');
$resolveConstant('PIMCORE_CONFIGURATION_DIRECTORY',PIMCORE_PRIVATE_VAR . '/config');
$resolveConstant('PIMCORE_ASSET_DIRECTORY',PIMCORE_PUBLIC_VAR . '/assets');
$resolveConstant('PIMCORE_VERSION_DIRECTORY',PIMCORE_PRIVATE_VAR . '/versions');
$resolveConstant('PIMCORE_LOG_DIRECTORY',PIMCORE_PRIVATE_VAR . '/logs');
$resolveConstant('PIMCORE_LOG_FILEOBJECT_DIRECTORY',PIMCORE_LOG_DIRECTORY . '/fileobjects');
$resolveConstant('PIMCORE_LOG_MAIL_TEMP',PIMCORE_LOG_DIRECTORY . '/mail');
$resolveConstant('PIMCORE_TEMPORARY_DIRECTORY',PIMCORE_PUBLIC_VAR . '/tmp');
$resolveConstant('PIMCORE_CACHE_DIRECTORY',PIMCORE_PRIVATE_VAR . '/cache/pimcore');
$resolveConstant('PIMCORE_CLASS_DIRECTORY',PIMCORE_PRIVATE_VAR . '/classes');
$resolveConstant('PIMCORE_CUSTOMLAYOUT_DIRECTORY',PIMCORE_CLASS_DIRECTORY . '/customlayouts');
$resolveConstant('PIMCORE_RECYCLEBIN_DIRECTORY',PIMCORE_PRIVATE_VAR . '/recyclebin');
$resolveConstant('PIMCORE_SYSTEM_TEMP_DIRECTORY',PIMCORE_PRIVATE_VAR . '/tmp');
$resolveConstant('PIMCORE_LOG_MAIL_PERMANENT',PIMCORE_PRIVATE_VAR . '/email');
$resolveConstant('PIMCORE_USERIMAGE_DIRECTORY',PIMCORE_PRIVATE_VAR . '/user-image');
