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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Cache as CacheManager;
use Pimcore\Logger;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;

class FullPageCacheListener extends AbstractFrontendListener
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var bool
     */
    protected $stopResponsePropagation = false;

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
     * @var string
     */
    protected $defaultCacheKey;

    /**
     * @param null $reason
     *
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
     * @param $lifetime
     *
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

    public function disableExpireHeader()
    {
        $this->addExpireHeader = false;
    }

    public function enableExpireHeader()
    {
        $this->addExpireHeader = true;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @return mixed
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!\Pimcore\Tool::useFrontendOutputFilters()) {
            return false;
        }

        $requestUri = $request->getRequestUri();
        $excludePatterns = [];

        // only enable GET method
        if (!$request->isMethod('GET')) {
            return $this->disable();
        }

        // disable the output-cache if browser wants the most recent version
        // unfortunately only Chrome + Firefox if not using SSL
        if (!$request->isSecure()) {
            if (isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache') {
                return $this->disable('HTTP Header Cache-Control: no-cache was sent');
            }

            if (isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache') {
                return $this->disable('HTTP Header Pragma: no-cache was sent');
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
                    return $this->disable('in debug mode');
                }

                if ($conf->lifetime) {
                    $this->setLifetime((int) $conf->lifetime);
                }

                if ($conf->excludePatterns) {
                    $confExcludePatterns = explode(',', $conf->excludePatterns);
                    if (!empty($confExcludePatterns)) {
                        $excludePatterns = $confExcludePatterns;
                    }
                }

                if ($conf->excludeCookie) {
                    $cookies = explode(',', strval($conf->excludeCookie));

                    foreach ($cookies as $cookie) {
                        if (!empty($cookie) && isset($_COOKIE[trim($cookie)])) {
                            return $this->disable('exclude cookie in system-settings matches');
                        }
                    }
                }

                // output-cache is always disabled when logged in at the admin ui
                if (null !== $pimcoreUser = Tool\Authentication::authenticateSession($request)) {
                    return $this->disable('backend user is logged in');
                }
            } else {
                return $this->disable();
            }
        } catch (\Exception $e) {
            Logger::error($e);

            return $this->disable('ERROR: Exception (see debug.log)');
        }

        foreach ($excludePatterns as $pattern) {
            if (@preg_match($pattern, $requestUri)) {
                return $this->disable('exclude path pattern in system-settings matches');
            }
        }

        $deviceDetector = Tool\DeviceDetector::getInstance();
        $device = $deviceDetector->getDevice();
        $deviceDetector->setWasUsed(false);

        $appendKey = '';
        // this is for example for the image-data-uri plugin
        if (isset($_REQUEST['pimcore_cache_tag_suffix'])) {
            $tags = $_REQUEST['pimcore_cache_tag_suffix'];
            if (is_array($tags)) {
                $appendKey = '_' . implode('_', $tags);
            }
        }

        $this->defaultCacheKey = 'output_' . md5(\Pimcore\Tool::getHostname() . $requestUri . $appendKey);
        $cacheKeys = [
            $this->defaultCacheKey . '_' . $device,
            $this->defaultCacheKey,
        ];

        $cacheKey = null;
        $cacheItem = null;
        foreach ($cacheKeys as $cacheKey) {
            $cacheItem = CacheManager::load($cacheKey, true);
            if ($cacheItem) {
                break;
            }
        }

        if ($cacheItem) {
            /**
             * @var $response Response
             */
            $response = $cacheItem;
            $response->headers->set('X-Pimcore-Output-Cache-Tag', $cacheKey, true);
            $cacheItemDate = strtotime($response->headers->get('X-Pimcore-Cache-Date'));
            $response->headers->set('Age', (time() - $cacheItemDate));

            $event->setResponse($response);
            $this->stopResponsePropagation = true;
        }
    }

    /**
     * @param KernelEvent $event
     */
    public function stopPropagationCheck(KernelEvent $event) {
        if($this->stopResponsePropagation) {
            $event->stopPropagation();
        }
    }

    /**
     * @param KernelEvent $event
     *
     * @return bool|void
     */
    public function onKernelResponse(KernelEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return false;
        }

        $request = $event->getRequest();
        if (!\Pimcore\Tool::isFrontend() || \Pimcore\Tool::isFrontendRequestByAdmin($request)) {
            return false;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return false;
        }

        $response = $event->getResponse();

        if (!$response) {
            return false;
        }

        if ($this->enabled && ($request->hasSession() && !empty($request->getSession()->getId()))) {
            $this->disable('session in use');
        }

        if ($this->disableReason) {
            $response->headers->set('X-Pimcore-Output-Cache-Disable-Reason', $this->disableReason, true);
        }

        if ($this->enabled && $response->getStatusCode() == 200 && $this->defaultCacheKey) {
            try {
                if ($this->lifetime && $this->addExpireHeader) {
                    // add cache control for proxies and http-caches like varnish, ...
                    $response->headers->set('Cache-Control', 'public, max-age=' . $this->lifetime, true);

                    // add expire header
                    $date = new \DateTime('now');
                    $date->add(new \DateInterval('PT' . $this->lifetime . 'S'));
                    $response->headers->set('Expires', $date->format(\DateTime::RFC1123), true);
                }

                $now = new \DateTime('now');
                $response->headers->set('X-Pimcore-Cache-Date', $now->format(\DateTime::ISO8601));

                $cacheKey = $this->defaultCacheKey;
                $deviceDetector = Tool\DeviceDetector::getInstance();
                if ($deviceDetector->wasUsed()) {
                    $cacheKey .= '_' . $deviceDetector->getDevice();
                }

                $cacheItem = $response;

                $tags = ['output'];
                if ($this->lifetime) {
                    $tags = ['output_lifetime'];
                }

                CacheManager::save($cacheItem, $cacheKey, $tags, $this->lifetime, 1000, true);
            } catch (\Exception $e) {
                Logger::error($e);

                return;
            }
        } else {
            // output-cache was disabled, add "output" as cleared tag to ensure that no other "output" tagged elements
            // like the inc and snippet cache get into the cache
            CacheManager::addIgnoredTagOnSave('output_inline');
        }
    }
}
