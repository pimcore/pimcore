<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore;

use const PHP_SAPI;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Tool\Admin;
use Pimcore\Tool\MaintenanceModeHelperInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class Bootstrap
{
    /**
     * @internal
     */
    public static bool $isInstaller = false;

    public static function startup(): Kernel|\App\Kernel|KernelInterface
    {
        self::setProjectRoot();
        self::bootstrap();
        $kernel = self::kernel();

        return $kernel;
    }

    public static function startupCli(): Kernel|KernelInterface
    {
        // ensure the cli arguments are set
        if (!isset($_SERVER['argv'])) {
            $_SERVER['argv'] = [];
        }

        self::setProjectRoot();
        self::bootstrap();

        $workingDirectory = getcwd();
        chdir(__DIR__);

        // init shell verbosity as 0 - this would normally be handled by the console application,
        // but as we boot the kernel early the kernel initializes this to 3 (verbose) by default
        putenv('SHELL_VERBOSITY=0');
        $_ENV['SHELL_VERBOSITY'] = 0;
        $_SERVER['SHELL_VERBOSITY'] = 0;

        /** @var \Pimcore\Kernel $kernel */
        $kernel = self::kernel();

        if (is_readable($workingDirectory)) {
            chdir($workingDirectory);
        }

        // activate inheritance for cli-scripts
        Pimcore::unsetAdminMode();
        Document::setHideUnpublished(true);
        DataObject::setHideUnpublished(true);
        DataObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(true);

        // Pimcore\Console handles maintenance mode through the AbstractCommand
        $pimcoreConsole = (defined('PIMCORE_CONSOLE') && true === PIMCORE_CONSOLE);
        if (!$pimcoreConsole) {
            $maintenanceModeHelper = $kernel->getContainer()->get(MaintenanceModeHelperInterface::class);
            // skip if maintenance mode is on and the flag is not set
            if (($maintenanceModeHelper->isActive() || Admin::isInMaintenanceMode()) &&
                !in_array('--ignore-maintenance-mode', $_SERVER['argv'])) {
                die("in maintenance mode -> skip\nset the flag --ignore-maintenance-mode to force execution\n");
            }
        }

        return $kernel;
    }

    public static function setProjectRoot(): void
    {
        // this should already be defined at this point, but we include a fallback for backwards compatibility here
        if (!defined('PIMCORE_PROJECT_ROOT')) {
            define(
                'PIMCORE_PROJECT_ROOT',
                $_SERVER['PIMCORE_PROJECT_ROOT'] ?? $_ENV['PIMCORE_PROJECT_ROOT'] ??
                $_SERVER['REDIRECT_PIMCORE_PROJECT_ROOT'] ?? $_ENV['REDIRECT_PIMCORE_PROJECT_ROOT'] ??
                realpath(__DIR__ . '/../../../..')
            );
        }
    }

    public static function bootstrap(): void
    {
        $isCli = in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true);

        // BC Layer when using the public/index.php without symfony runtime pimcore/skeleton #128 OR without pimcore/skeleton #183 (< 11.0.4)
        if (!Tool::hasCurrentRequest() && !$isCli && !isset($_ENV['SYMFONY_DOTENV_VARS'])) {
            trigger_deprecation(
                'pimcore/skeleton',
                '11.2.0',
                'For consistency purpose, it is recommended to use the autoload from Symfony Runtime.
                When using it, the line "Bootstrap::bootstrap();" in `public/index.php` should be moved just above "$kernel = Bootstrap::kernel();" and within the closure'
            );
            self::bootDotEnvVariables();
        }

        // BC Layer when using bin/console without symfony runtime, exclude installer script
        if ($isCli && !isset($_ENV['SYMFONY_DOTENV_VARS']) && !self::$isInstaller) {
            trigger_deprecation(
                'pimcore/skeleton',
                '11.2.0',
                'For consistency purpose, it is recommended to use the autoload from Symfony Runtime in project root "bin/console"'
            );
            self::bootDotEnvVariables();
        }

        // Installer
        // Keep this block unless core is requiring symfony runtime as mandatory and pimcore-install is adapted
        if ($isCli && !isset($_ENV['SYMFONY_DOTENV_VARS']) && self::$isInstaller) {
            self::bootDotEnvVariables();
        }

        self::defineConstants();

        // load a startup file if it exists - this is a good place to preconfigure the system
        // before the kernel is loaded - e.g. to set trusted proxies on the request object
        $startupFile = PIMCORE_PROJECT_ROOT . '/config/pimcore/startup.php';
        if (file_exists($startupFile)) {
            include_once $startupFile;
        }

        if (false === $isCli) {
            self::setTrustedProxies();
        }
    }

    /**
     * @deprecated only for compatibility reasons, will be removed in Pimcore 12
     */
    private static function bootDotEnvVariables(): void
    {
        if (class_exists('Symfony\Component\Dotenv\Dotenv')) {
            (new Dotenv())->bootEnv(PIMCORE_PROJECT_ROOT . '/.env');
        }
    }

    private static function setTrustedProxies(): void
    {
        // see https://github.com/symfony/recipes/blob/master/symfony/framework-bundle/4.2/public/index.php#L15
        if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
            Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        }
        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
            Request::setTrustedHosts([$trustedHosts]);
        }
    }

    public static function defineConstants(): void
    {
        // make sure $_SERVER contains all values of $_ENV
        $_SERVER += $_ENV;

        // load custom constants
        $customConstantsFile = PIMCORE_PROJECT_ROOT . '/config/pimcore/constants.php';
        if (file_exists($customConstantsFile)) {
            include_once $customConstantsFile;
        }

        $resolveConstant = function (string $name, $default, bool $define = true) {
            // return constant if defined
            if (defined($name)) {
                return constant($name);
            }

            $value = $_SERVER[$name] ?? $default;
            if ($define) {
                define($name, $value);
            }

            return $value;
        };

        // basic paths
        $resolveConstant('PIMCORE_COMPOSER_PATH', PIMCORE_PROJECT_ROOT . '/vendor');
        $resolveConstant('PIMCORE_COMPOSER_FILE_PATH', PIMCORE_PROJECT_ROOT);
        $resolveConstant('PIMCORE_PATH', realpath(__DIR__ . '/..'));
        $resolveConstant('PIMCORE_WEB_ROOT', PIMCORE_PROJECT_ROOT . '/public');
        $resolveConstant('PIMCORE_PRIVATE_VAR', PIMCORE_PROJECT_ROOT . '/var');

        // special directories for tests
        // test mode can bei either controlled by a constant or an env variable
        $testMode = (bool)$resolveConstant('PIMCORE_TEST', false, false);
        if ($testMode) {
            // override and initialize directories
            $resolveConstant('PIMCORE_CLASS_DIRECTORY', PIMCORE_PATH . '/tests/_output/var/classes');

            if (!defined('PIMCORE_TEST')) {
                define('PIMCORE_TEST', true);
            }
        }

        // paths relying on basic paths above
        $resolveConstant('PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY', PIMCORE_PROJECT_ROOT . '/config/pimcore');
        $resolveConstant('PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY', PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . '/classes');
        $resolveConstant('PIMCORE_CONFIGURATION_DIRECTORY', PIMCORE_PRIVATE_VAR . '/config');
        $resolveConstant('PIMCORE_LOG_DIRECTORY', PIMCORE_PRIVATE_VAR . '/log');
        $resolveConstant('PIMCORE_CACHE_DIRECTORY', PIMCORE_PRIVATE_VAR . '/cache/pimcore');
        $resolveConstant('PIMCORE_SYMFONY_CACHE_DIRECTORY', PIMCORE_PRIVATE_VAR . '/cache');
        $resolveConstant('PIMCORE_CLASS_DIRECTORY', PIMCORE_PRIVATE_VAR . '/classes');
        $resolveConstant('PIMCORE_CLASS_DEFINITION_DIRECTORY', PIMCORE_CLASS_DIRECTORY);
        $resolveConstant('PIMCORE_SYSTEM_TEMP_DIRECTORY', PIMCORE_PRIVATE_VAR . '/tmp');

        // configure PHP's error logging
        $resolveConstant('PIMCORE_KERNEL_CLASS', '\App\Kernel');
    }

    public static function kernel(): Kernel|\App\Kernel|KernelInterface
    {
        $environment = Config::getEnvironment();

        $debug = (bool) ($_SERVER['APP_DEBUG'] ?? false);
        if ($debug) {
            umask(0000);
            Debug::enable();
        }

        if (defined('PIMCORE_KERNEL_CLASS')) {
            $kernelClass = PIMCORE_KERNEL_CLASS;
        } else {
            $kernelClass = '\App\Kernel';
        }

        if (!class_exists($kernelClass)) {
            throw new InvalidArgumentException(sprintf('Defined Kernel Class %s not found', $kernelClass));
        }

        if (!is_subclass_of($kernelClass, Kernel::class)) {
            throw new InvalidArgumentException(sprintf('Defined Kernel Class %s needs to extend the \Pimcore\Kernel Class', $kernelClass));
        }

        $kernel = new $kernelClass($environment, $debug);
        Pimcore::setKernel($kernel);
        $kernel->boot();

        $conf = Config::getSystemConfiguration();

        if ($conf['general']['timezone']) {
            date_default_timezone_set($conf['general']['timezone']);
        }

        return $kernel;
    }
}
