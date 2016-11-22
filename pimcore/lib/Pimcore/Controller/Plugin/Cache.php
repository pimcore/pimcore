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

namespace Pimcore\Controller\Plugin;

use Pimcore\Tool;
use Pimcore\Cache as CacheManager;
use Pimcore\Logger;

class Cache extends \Zend_Controller_Plugin_Abstract
{

    /**
     * @var string
     */
    protected $defaultCacheKey;

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
     * @var string|null
     */
    protected $disableReason;

    /**
     *
     */
    public function disableExpireHeader()
    {
        $this->addExpireHeader = false;
    }

    /**
     *
     */
    public function enableExpireHeader()
    {
        $this->addExpireHeader = true;
    }

    /**
     * @return bool
     */
    public function disable($reason = null)
    {
        if ($reason) {
            $this->disableReason = $reason;
        }

        $this->enabled = false;

        return true;
    }

    /**
     * @return bool
     */
    public function enable()
    {
        $this->enabled = true;

        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request)
    {
        $requestUri = $request->getRequestUri();
        $excludePatterns = [];

        // only enable GET method
        if (!$request->isGet()) {
            return $this->disable();
        }

        // disable the output-cache if browser wants the most recent version
        // unfortunately only Chrome + Firefox if not using SSL
        if (!$request->isSecure()) {
            if (isset($_SERVER["HTTP_CACHE_CONTROL"]) && $_SERVER["HTTP_CACHE_CONTROL"] == "no-cache") {
                return $this->disable("HTTP Header Cache-Control: no-cache was sent");
            }

            if (isset($_SERVER["HTTP_PRAGMA"]) && $_SERVER["HTTP_PRAGMA"] == "no-cache") {
                return $this->disable("HTTP Header Pragma: no-cache was sent");
            }
        }

        try {
            $conf = \Pimcore\Config::getSystemConfig();
            if ($conf->cache) {
                $conf = $conf->cache;

                if (!$conf->enabled) {
                    return $this->disable();
                }

                if (\Pimcore::inDebugMode()) {
                    return $this->disable("in debug mode");
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
                            return $this->disable("exclude cookie in system-settings matches");
                        }
                    }
                }

                // output-cache is always disabled when logged in at the admin ui
                if (isset($_COOKIE["pimcore_admin_sid"])) {
                    return $this->disable("backend user is logged in");
                }
            } else {
                return $this->disable();
            }
        } catch (\Exception $e) {
            Logger::error($e);

            return $this->disable("ERROR: Exception (see debug.log)");
        }

        foreach ($excludePatterns as $pattern) {
            if (@preg_match($pattern, $requestUri)) {
                return $this->disable("exclude path pattern in system-settings matches");
            }
        }

        $deviceDetector = Tool\DeviceDetector::getInstance();
        $device = $deviceDetector->getDevice();
        $deviceDetector->setWasUsed(false);

        $appendKey = "";
        // this is for example for the image-data-uri plugin
        if (isset($_REQUEST["pimcore_cache_tag_suffix"])) {
            $tags = $_REQUEST["pimcore_cache_tag_suffix"];
            if (is_array($tags)) {
                $appendKey = "_" . implode("_", $tags);
            }
        }

        $this->defaultCacheKey = "output_" . md5(\Pimcore\Tool::getHostname() . $requestUri . $appendKey);
        $cacheKeys = [
            $this->defaultCacheKey . "_" . $device,
            $this->defaultCacheKey,
        ];

        $cacheItem = null;
        foreach ($cacheKeys as $cacheKey) {
            $cacheItem = CacheManager::load($cacheKey, true);
            if ($cacheItem) {
                break;
            }
        }

        if (is_array($cacheItem) && !empty($cacheItem)) {
            header("X-Pimcore-Output-Cache-Tag: " . $cacheKey, true, 200);
            header("X-Pimcore-Output-Cache-Date: " . $cacheItem["date"]);

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
            $date = new \DateTime();
            $date->setTimestamp(1);
            $this->getResponse()->setHeader("Expires", $date->format(\DateTime::RFC1123), true);
            $this->getResponse()->setHeader("Cache-Control", "max-age=0, no-cache", true);
        }
    }

    /**
     *
     */
    public function dispatchLoopShutdown()
    {
        if ($this->enabled && session_id()) {
            $this->disable("session in use");
        }

        if ($this->disableReason) {
            $this->getResponse()->setHeader("X-Pimcore-Output-Cache-Disable-Reason", $this->disableReason, true);
        }

        if ($this->enabled && $this->getResponse()->getHttpResponseCode() == 200) {
            try {
                if ($this->lifetime && $this->addExpireHeader) {
                    // add cache control for proxies and http-caches like varnish, ...
                    $this->getResponse()->setHeader("Cache-Control", "public, max-age=" . $this->lifetime, true);

                    // add expire header
                    $date = new \DateTime("now");
                    $date->add(new \DateInterval("PT" . $this->lifetime . "S"));
                    $this->getResponse()->setHeader("Expires", $date->format(\DateTime::RFC1123), true);
                }

                $now = new \DateTime("now");
                $cacheItem = [
                    "headers" => $this->getResponse()->getHeaders(),
                    "rawHeaders" => $this->getResponse()->getRawHeaders(),
                    "content" => $this->getResponse()->getBody(),
                    "date" => $now->format(\DateTime::ISO8601)
                ];

                $cacheKey = $this->defaultCacheKey;
                $deviceDetector = Tool\DeviceDetector::getInstance();
                if ($deviceDetector->wasUsed()) {
                    $cacheKey .= "_" . $deviceDetector->getDevice();
                }

                $tags = ["output"];
                if ($this->lifetime) {
                    $tags = ["output_lifetime"];
                }

                CacheManager::save($cacheItem, $cacheKey, $tags, $this->lifetime, 1000, true);
            } catch (\Exception $e) {
                Logger::error($e);

                return;
            }
        } else {
            // output-cache was disabled, add "output" as cleared tag to ensure that no other "output" tagged elements
            // like the inc and snippet cache get into the cache
            CacheManager::addClearedTag("output_inline");
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
