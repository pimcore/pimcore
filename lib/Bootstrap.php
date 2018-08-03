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

namespace Pimcore;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;

class Bootstrap
{
    public static function startup()
    {
        self::setProjectRoot();
        self::boostrap();
        $kernel = self::kernel();

        return $kernel;
    }

    public static function startupCli()
    {

        // ensure the cli arguments are set
        if (!isset($_SERVER['argv'])) {
            $_SERVER['argv'] = [];
        }

        self::setProjectRoot();

        // determines if we're in Pimcore\Console mode
        $pimcoreConsole = (defined('PIMCORE_CONSOLE') && true === PIMCORE_CONSOLE);

        self::boostrap();

        $workingDirectory = getcwd();
        chdir(__DIR__);

        // init shell verbosity as 0 - this would normally be handled by the console application,
        // but as we boot the kernel early the kernel initializes this to 3 (verbose) by default
        putenv('SHELL_VERBOSITY=0');
        $_ENV['SHELL_VERBOSITY'] = 0;
        $_SERVER['SHELL_VERBOSITY'] = 0;

        if ($pimcoreConsole) {
            $input = new ArgvInput();
            $env   = $input->getParameterOption(['--env', '-e'], Config::getEnvironment());
            $debug = \Pimcore::inDebugMode() && !$input->hasParameterOption(['--no-debug', '']);

            Config::setEnvironment($env);
            if (!defined('PIMCORE_DEBUG')) {
                define('PIMCORE_DEBUG', $debug);
            }
        }

        /** @var \Pimcore\Kernel $kernel */
        $kernel = self::kernel();

        chdir($workingDirectory);

        // activate inheritance for cli-scripts
        \Pimcore::unsetAdminMode();
        Document::setHideUnpublished(true);
        DataObject\AbstractObject::setHideUnpublished(true);
        DataObject\AbstractObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(true);

        // CLI has no memory/time limits
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', -1);
        @ini_set('max_input_time', -1);

        // Error reporting is enabled in CLI
        @ini_set('display_errors', 'On');
        @ini_set('display_startup_errors', 'On');
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

        // Pimcore\Console handles maintenance mode through the AbstractCommand
        if (!$pimcoreConsole) {
            // skip if maintenance mode is on and the flag is not set
            if (\Pimcore\Tool\Admin::isInMaintenanceMode() && !in_array('--ignore-maintenance-mode', $_SERVER['argv'])) {
                die("in maintenance mode -> skip\nset the flag --ignore-maintenance-mode to force execution \n");
            }
        }

        return $kernel;
    }

    public static function setProjectRoot()
    {
        // this should already be defined at this point, but we include a fallback here
        // fot backwards compatibility
        if (!defined('PIMCORE_PROJECT_ROOT')) {
            define(
                'PIMCORE_PROJECT_ROOT',
                getenv('PIMCORE_PROJECT_ROOT')
                    ?: getenv('REDIRECT_PIMCORE_PROJECT_ROOT')
                    ?: realpath(__DIR__ . '/../../../..')
            );
        }
    }

    public static function boostrap()
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

        /** @var $loader \Composer\Autoload\ClassLoader */
        $loader = include __DIR__ . '/../../../../vendor/autoload.php';
        self::defineConstants();

        if (is_integer(PIMCORE_PHP_ERROR_REPORTING)) {
            error_reporting(PIMCORE_PHP_ERROR_REPORTING);
        }

        \Pimcore::setAutoloader($loader);
        self::autoload();

        if ('syslog' === PIMCORE_PHP_ERROR_LOG || is_writable(dirname(PIMCORE_PHP_ERROR_LOG))) {
            ini_set('error_log', PIMCORE_PHP_ERROR_LOG);
            ini_set('log_errors', '1');
        }

        // load a startup file if it exists - this is a good place to preconfigure the system
        // before the kernel is loaded - e.g. to set trusted proxies on the request object
        $startupFile = PIMCORE_PROJECT_ROOT . '/app/startup.php';
        if (file_exists($startupFile)) {
            include_once $startupFile;
        }
    }

    public static function defineConstants()
    {
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
        $resolveConstant('PIMCORE_PATH', realpath(__DIR__ . '/..'));
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
        $resolveConstant('PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY', PIMCORE_APP_ROOT . '/config/pimcore');
        $resolveConstant('PIMCORE_CONFIGURATION_DIRECTORY', PIMCORE_PRIVATE_VAR . '/config');
        $resolveConstant('PIMCORE_ASSET_DIRECTORY', PIMCORE_PUBLIC_VAR . '/assets');
        $resolveConstant('PIMCORE_VERSION_DIRECTORY', PIMCORE_PRIVATE_VAR . '/versions');
        $resolveConstant('PIMCORE_LOG_DIRECTORY', PIMCORE_PRIVATE_VAR . '/logs');
        $resolveConstant('PIMCORE_LOG_FILEOBJECT_DIRECTORY', PIMCORE_PRIVATE_VAR . '/application-logger');
        $resolveConstant('PIMCORE_TEMPORARY_DIRECTORY', PIMCORE_PUBLIC_VAR . '/tmp');
        $resolveConstant('PIMCORE_CACHE_DIRECTORY', PIMCORE_PRIVATE_VAR . '/cache/pimcore');
        $resolveConstant('PIMCORE_SYMFONY_CACHE_DIRECTORY', PIMCORE_PRIVATE_VAR . '/cache');
        $resolveConstant('PIMCORE_CLASS_DIRECTORY', PIMCORE_PRIVATE_VAR . '/classes');
        $resolveConstant('PIMCORE_CUSTOMLAYOUT_DIRECTORY', PIMCORE_CLASS_DIRECTORY . '/customlayouts');
        $resolveConstant('PIMCORE_RECYCLEBIN_DIRECTORY', PIMCORE_PRIVATE_VAR . '/recyclebin');
        $resolveConstant('PIMCORE_SYSTEM_TEMP_DIRECTORY', PIMCORE_PRIVATE_VAR . '/tmp');
        $resolveConstant('PIMCORE_LOG_MAIL_PERMANENT', PIMCORE_PRIVATE_VAR . '/email');
        $resolveConstant('PIMCORE_USERIMAGE_DIRECTORY', PIMCORE_PRIVATE_VAR . '/user-image');

        // configure PHP's error logging
        $resolveConstant('PIMCORE_PHP_ERROR_REPORTING', E_ALL & ~E_NOTICE & ~E_STRICT);
        $resolveConstant('PIMCORE_PHP_ERROR_LOG', PIMCORE_LOG_DIRECTORY . '/php.log');
    }

    public static function autoload()
    {
        $loader = \Pimcore::getAutoloader();

        // tell the autoloader where to find Pimcore's generated class stubs
        // this is primarily necessary for tests and custom class directories, which are not covered in composer.json
        $loader->addPsr4('Pimcore\\Model\\DataObject\\', PIMCORE_CLASS_DIRECTORY . '/DataObject');

        // compatibility autoloader for the \Pimcore\Model\Object\* namespace (seems to work with PHP 7.2 as well, tested with 7.2.3)
        $dataObjectCompatibilityLoader = new \Pimcore\Loader\Autoloader\DataObjectCompatibility($loader);
        $dataObjectCompatibilityLoader->register(true);

        // legacy mapping loader creates aliases for renamed classes
        $legacyMappingLoader = new \Pimcore\Loader\Autoloader\AliasMapper($loader);
        $legacyMappingLoader->createAliases();

        // the following code is out of `app/autoload.php`
        // see also: https://github.com/symfony/symfony-demo/blob/master/app/autoload.php
        AnnotationRegistry::registerLoader([$loader, 'loadClass']);

        // ignore apiDoc params (see http://apidocjs.com/) as we use apiDoc in webservice
        $apiDocAnnotations = [
            'api', 'apiDefine',
            'apiDeprecated', 'apiDescription', 'apiError',  'apiErrorExample', 'apiExample', 'apiGroup', 'apiHeader',
            'apiHeaderExample', 'apiIgnore', 'apiName', 'apiParam', 'apiParamExample', 'apiPermission', 'apiSampleRequest',
            'apiSuccess', 'apiSuccessExample', 'apiUse', 'apiVersion',
        ];

        foreach ($apiDocAnnotations as $apiDocAnnotation) {
            AnnotationReader::addGlobalIgnoredName($apiDocAnnotation);
        }

        self::includes();

        if (defined('PIMCORE_APP_BUNDLE_CLASS_FILE')) {
            require_once PIMCORE_APP_BUNDLE_CLASS_FILE;
        }

        self::zendCompatibility();
    }

    public static function zendCompatibility()
    {
        if (!class_exists('Zend_Date')) {
            // if ZF is not loaded, we need to provide some compatibility stubs
            // for a detailed description see the included file
            require_once __DIR__ . '/../stubs/compatibility-v4.php';
        }
    }

    public static function includes()
    {
        // some pimcore specific generic includes
        // includes not covered by composer autoloader
        require_once __DIR__ . '/helper-functions.php';
    }

    public static function kernel()
    {
        $environment = Config::getEnvironment();
        $debug       = Config::getEnvironmentConfig()->activatesKernelDebugMode($environment);

        if ($debug) {
            Debug::enable();
            @ini_set('display_errors', 'On');
        }

        $debug = true;
        $kernel = new \AppKernel($environment, $debug);
        \Pimcore::setKernel($kernel);
        $kernel->boot();

        return $kernel;
    }
}
