<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Tool;

class HybridAuth {

    /**
     * @throws \Zend_Loader_Exception
     */
    public function init() {
        // register HybridAuth
        $autoloader = \Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Hybrid');

        // disable output-cache
        $front = \Zend_Controller_Front::getInstance();
        $front->unregisterPlugin("Pimcore\\Controller\\Plugin\\Cache");
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public static function getConfiguration() {
        $config = null;
        $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/hybridauth.php";
        if(is_file($configFile) ){
            $config = include($configFile);
            $config["base_url"] = "http://" . \Pimcore\Tool::getHostname() . "/hybridauth/endpoint";
        } else {
            throw new \Exception("HybridAuth configuration not found. Please place it into this file: $configFile");
        }

        return $config;
    }

    /**
     * @param $provider
     * @return \Hybrid_Provider_Adapter
     */
    public static function authenticate($provider) {
        self::init();

        $adapter = null;
        try {
            $hybridauth = new \Hybrid_Auth(self::getConfiguration());
            $provider = @trim(strip_tags($provider));
            $adapter = $hybridauth->authenticate( $provider );
        } catch (\Exception $e) {
            \Logger::info($e);
        }
        return $adapter;
    }

    /**
     *
     */
    public static function process() {
        self::init();

        \Hybrid_Endpoint::process();
        exit;
    }
}