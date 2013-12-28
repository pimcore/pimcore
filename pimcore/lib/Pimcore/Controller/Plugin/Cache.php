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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Controller_Plugin_Cache extends Zend_Controller_Plugin_Abstract {

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var null|int
     */
    protected $lifetime = null;

    /**
     * @var bool
     */
    protected $addExpireHeader = true;

    /**
     *
     */
    public function disableExpireHeader() {
        $this->addExpireHeader = false;
    }

    /**
     *
     */
    public function enableExpireHeader() {
        $this->addExpireHeader = true;
    }

    /**
     * @return bool
     */
    public function disable() {
        $this->enabled = false;
        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * @param Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        $requestUri = $request->getRequestUri();
        $excludePatterns = array();

        // only enable GET method
        if (!$request->isGet()) {
            return $this->disable();
        }

        try {
            $conf = Pimcore_Config::getSystemConfig();
            if ($conf->cache) {

                $conf = $conf->cache;

                if (!$conf->enabled) {
                    return $this->disable();
                }

                if ($conf->lifetime) {
                    $this->setLifetime((int) $conf->lifetime);
                }

                if ($conf->excludePatterns) {
                    $confExcludePatterns = explode(",", $conf->excludePatterns);
                    if (!empty($confExcludePatterns)) {
                        $excludePatterns = $confExcludePatterns;
                    }
                }

                if ($conf->excludeCookie) {
                    $cookies = explode(",", strval($conf->excludeCookie));

                    foreach ($cookies as $cookie) {
                        if (!empty($cookie) && isset($_COOKIE[trim($cookie)])) {
                            return $this->disable();
                        }
                    }
                }

                // output-cache is always disabled when logged in at the admin ui
                if(isset($_COOKIE["pimcore_admin_sid"])) {
                    return $this->disable();
                }
            }
            else {
                return $this->disable();
            }
        }
        catch (Exception $e) {
            return $this->disable();
        }

        foreach ($excludePatterns as $pattern) {
            if (@preg_match($pattern, $requestUri)) {
                return $this->disable();
            }
        }

        $appendKey = "";
        // this is for example for the image-data-uri plugin
        if ($request->getParam("pimcore_cache_tag_suffix")) {
            $tags = $request->getParam("pimcore_cache_tag_suffix");
            if (is_array($tags)) {
                $appendKey = "_" . implode("_", $tags);
            }
        }

        $this->cacheKey = "output_" . md5(Pimcore_Tool::getHostname() . $requestUri) . $appendKey;

        if ($cacheItem = Pimcore_Model_Cache::load($this->cacheKey, true)) {
            header("X-Pimcore-Cache-Tag: " . $this->cacheKey, true, 200);
            header("X-Pimcore-Cache-Date: " . $cacheItem["date"]);
            
            foreach ($cacheItem["rawHeaders"] as $header) {
                header($header);
            }
    
            foreach ($cacheItem["headers"] as $header) {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
            
            echo $cacheItem["content"];
            exit;
        } else {
            // set headers to tell the client to not cache the contents
            // this can/will be overwritten in $this->dispatchLoopShutdown() if the cache is enabled
            $date = new Zend_Date(1);
            $this->getResponse()->setHeader("Expires", $date->get(Zend_Date::RFC_1123), true);
            $this->getResponse()->setHeader("Cache-Control", "max-age=0, no-cache", true);
        }
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {
        if ($this->enabled && $this->getResponse()->getHttpResponseCode() == 200 && !session_id()) {
            try {

                if($this->lifetime && $this->addExpireHeader) {
                    // add cache control for proxies and http-caches like varnish, ...
                    $this->getResponse()->setHeader("Cache-Control", "public, max-age=" . $this->lifetime, true);

                    // add expire header
                    $this->getResponse()->setHeader("Expires", Zend_Date::now()->add($this->lifetime)->get(Zend_Date::RFC_1123), true);
                }

                $cacheItem = array(
                    "headers" => $this->getResponse()->getHeaders(),
                    "rawHeaders" => $this->getResponse()->getRawHeaders(),
                    "content" => $this->getResponse()->getBody(),
                    "date" => Zend_Date::now()->getIso()
                );
                
                Pimcore_Model_Cache::save($cacheItem, $this->cacheKey, array("output"), $this->lifetime, 1000);
            }
            catch (Exception $e) {
                Logger::error($e);
                return;
            }
        } else {
            // output-cache was disabled, add "output" as cleared tag to ensure that no other "output" tagged elements
            // like the inc and snippet cache get into the cache
            Pimcore_Model_Cache::addClearedTag("output");
        }
    }

    /**
     * @param $lifetime
     * @return $this
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }
}
