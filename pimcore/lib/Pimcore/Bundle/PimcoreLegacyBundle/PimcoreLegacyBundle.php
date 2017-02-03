<?php

namespace Pimcore\Bundle\PimcoreLegacyBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Pimcore\Bundle\PimcoreLegacyBundle\ClassLoader\LegacyClassLoader;

class PimcoreLegacyBundle extends Bundle
{
    public function boot()
    {
        $loader = new LegacyClassLoader();
        $loader->register();

        $this->defineConstants();
    }

    protected function defineConstants() {


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
    }

}
