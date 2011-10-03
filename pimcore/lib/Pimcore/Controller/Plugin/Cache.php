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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

require_once 'Zend/Controller/Plugin/Abstract.php';

class Pimcore_Controller_Plugin_Cache extends Zend_Controller_Plugin_Abstract {

    protected $cacheKey;
    protected $enabled = true;
    protected $lifetime = null;

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
                    $this->lifetime = (int) $conf->lifetime;
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
                        if (isset($_COOKIE[trim($cookie)])) {
                            return $this->disable();
                        }
                    }
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
            if (preg_match($pattern, $requestUri)) {
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

        $this->cacheKey = "output_" . md5($_SERVER["HTTP_HOST"] . $requestUri) . $appendKey;

        if ($cacheItem = Pimcore_Model_Cache::load($this->cacheKey, true)) {
            header("X-Pimcore-Cache-Tag: " . $this->cacheKey, true, 200);
            
            foreach ($cacheItem["rawHeaders"] as $header) {
                header($header);
            }
    
            foreach ($cacheItem["headers"] as $header) {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
            
            echo $cacheItem["content"];
            exit;
        }
    }

    public function disable() {
        $this->enabled = false;
        return true;
    }

    public function dispatchLoopShutdown() {
        if ($this->enabled && $this->getResponse()->getHttpResponseCode() == 200) {
            try {
                $cacheItem = array(
                    "headers" => $this->getResponse()->getHeaders(),
                    "rawHeaders" => $this->getResponse()->getRawHeaders(),
                    "content" => $this->getResponse()->getBody()
                );
                
                Pimcore_Model_Cache::save($cacheItem, $this->cacheKey, array("output"), $this->lifetime, 1000);
            }
            catch (Exception $e) {
                return;
            }
        }
    }
}
