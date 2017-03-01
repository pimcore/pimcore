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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Logger;

class HybridAuth
{
    /**
     * @throws \Zend_Loader_Exception
     */
    public static function init()
    {
        $cacheService = \Pimcore::getContainer()->get("pimcore.event_listener.frontend.full_page_cache");
        $cacheService->disable("HybridAuth");
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public static function getConfiguration()
    {
        $config = null;
        $configFile = \Pimcore\Config::locateConfigFile("hybridauth.php");
        if (is_file($configFile)) {
            $config = include($configFile);
            $config["base_url"] = \Pimcore\Tool::getHostUrl() . "/hybridauth/endpoint";
        } else {
            throw new \Exception("HybridAuth configuration not found. Please place it into this file: $configFile");
        }

        return $config;
    }

    /**
     * Initialize hybrid auth from configuration
     */
    public static function initializeHybridAuth()
    {
        \Hybrid_Auth::initialize(static::getConfiguration());
    }

    /**
     * @return \Hybrid_Auth
     */
    public static function getHybridAuth()
    {
        return new \Hybrid_Auth(static::getConfiguration());
    }

    /**
     * @param string $provider
     * @param array|null $params
     * @return \Hybrid_Provider_Adapter
     */
    public static function authenticate($provider, $params = null)
    {
        self::init();

        $adapter = null;
        try {
            static::initializeHybridAuth();

            $provider = @trim(strip_tags($provider));
            $adapter = \Hybrid_Auth::authenticate($provider, $params);
        } catch (\Exception $e) {
            Logger::info($e);
        }

        return $adapter;
    }

    /**
     *
     */
    public static function process()
    {
        self::init();

        \Hybrid_Endpoint::process();
        exit;
    }
}
