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

namespace PimcoreLegacyBundle;

use Pimcore\Cache;
use PimcoreLegacyBundle\ClassLoader\LegacyClassLoader;
use PimcoreLegacyBundle\DependencyInjection\Compiler\FallbackRouterPass;
use PimcoreLegacyBundle\DependencyInjection\Compiler\LegacyAreaHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreLegacyBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LegacyAreaHandlerPass());
    }

    public function boot()
    {
        $this->setupIncludePaths();

        $loader = new LegacyClassLoader();
        $loader->register();

        $this->defineConstants();

        if(php_sapi_name() == "cli") {
            $this->setupCliEnvironment();
        }

        \Zend_Registry::_unsetInstance();
        \Zend_Registry::setClassName("\\PimcoreLegacyBundle\\Zend\\Registry\\Proxy");
    }

    protected function defineConstants()
    {
        if (!defined('PIMCORE_DOCUMENT_ROOT')) {
            /**
             * @deprecated
             */
            define('PIMCORE_DOCUMENT_ROOT', PIMCORE_PROJECT_ROOT);
        }

        if (!defined('PIMCORE_LEGACY_ROOT')) {
            /**
             * @deprecated
             */
            define('PIMCORE_LEGACY_ROOT', PIMCORE_PROJECT_ROOT . '/legacy');
        }

        if (!defined('PIMCORE_FRONTEND_MODULE')) {
            /**
             * @deprecated
             */
            define('PIMCORE_FRONTEND_MODULE', 'website');
        }

        if (!defined('PIMCORE_PLUGINS_PATH')) {
            /**
             * @deprecated
             */
            define('PIMCORE_PLUGINS_PATH', PIMCORE_LEGACY_ROOT . '/plugins');
        }

        if (!defined('PIMCORE_WEBSITE_PATH')) {
            /**
             * @deprecated
             */
            define('PIMCORE_WEBSITE_PATH', PIMCORE_LEGACY_ROOT . '/' . PIMCORE_FRONTEND_MODULE);
        }

        if (!defined('PIMCORE_WEBSITE_VAR')) {
            /**
             * @deprecated
             */
            define('PIMCORE_WEBSITE_VAR', PIMCORE_DOCUMENT_ROOT . '/var');
        }

        if (!defined('PIMCORE_LOG_DEBUG')) {
            /**
             * @deprecated
             */
            define('PIMCORE_LOG_DEBUG', PIMCORE_LOG_DIRECTORY . '/debug.log');
        }
    }

    protected function setupIncludePaths() {
        // include paths defined in php.ini are ignored because they're causing problems with open_basedir, see PIMCORE-1233
        // it also improves the performance when reducing the amount of include paths, you can of course add additional paths anywhere in your code (/website)
        $includePaths = [
            PIMCORE_PATH . "/lib",
            PIMCORE_PATH . "/models",
            PIMCORE_CLASS_DIRECTORY,
            // we need to include the path to the ZF1, because we cannot remove all require_once() out of the source
            // see also: Pimcore\Composer::zendFrameworkOptimization()
            // actually the problem is 'require_once 'Zend/Loader.php';' in Zend/Loader/Autoloader.php
            PIMCORE_PROJECT_ROOT . "/vendor/zendframework/zendframework1/library/",
        ];
        set_include_path(implode(PATH_SEPARATOR, $includePaths) . PATH_SEPARATOR);
    }

    protected function setupCliEnvironment() {
        // CLI \Zend_Controller_Front Setup, this is required to make it possible to make use of all rendering features
        // this includes $this->action() in templates, ...
        $front = \Zend_Controller_Front::getInstance();
        \Pimcore\Legacy::initControllerFront($front);

        $request = new \Zend_Controller_Request_Http();
        $request->setModuleName(PIMCORE_FRONTEND_MODULE);
        $request->setControllerName('default');
        $request->setActionName('default');
        $front->setRequest($request);
        $front->setResponse(new \Zend_Controller_Response_Cli());
    }
}
