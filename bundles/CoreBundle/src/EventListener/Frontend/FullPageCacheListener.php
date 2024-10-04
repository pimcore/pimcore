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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\StaticPageContextAwareTrait;
use Pimcore\Cache;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Config;
use Pimcore\Event\Cache\FullPage\CacheResponseEvent;
use Pimcore\Event\Cache\FullPage\PrepareResponseEvent;
use Pimcore\Event\FullPageCacheEvents;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Logger;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FullPageCacheListener
{
    use PimcoreContextAwareTrait;
    use StaticPageContextAwareTrait;

    protected bool $enabled = true;

    protected bool $stopResponsePropagation = false;

    protected ?int $lifetime = null;

    protected bool $addExpireHeader = true;

    protected ?string $disableReason = null;

    protected ?string $defaultCacheKey = null;

    public function __construct(
        protected SessionStatus $sessionStatus,
        protected EventDispatcherInterface $eventDispatcher,
        protected Config $config
    ) {
    }

    public function disable(string $reason = null): bool
    {
        if ($reason) {
            $this->disableReason = $reason;
        }

        $this->enabled = false;

        return true;
    }

    public function enable(): bool
    {
        $this->enabled = true;

        return true;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return $this
     */
    public function setLifetime(?int $lifetime): static
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    public function getLifetime(): ?int
    {
        return $this->lifetime;
    }

    public function disableExpireHeader(): void
    {
        $this->addExpireHeader = false;
    }

    public function enableExpireHeader(): void
    {
        $this->addExpireHeader = true;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!\Pimcore\Tool::useFrontendOutputFilters()) {
            return;
        }

        $requestUri = $request->getRequestUri();
        $excludePatterns = [];

        // disable the output-cache if the client sends an authorization header
        if ($request->headers->has('authorization')) {
            $this->disable('authorization header in use');

            return;
        }

        // only enable GET method
        if (!$request->isMethodCacheable()) {
            $this->disable();

            return;
        }

        // disable the output-cache if browser wants the most recent version
        // unfortunately only Chrome + Firefox if not using SSL
        if (!$request->isSecure()) {
            if (isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] === 'no-cache') {
                $this->disable('HTTP Header Cache-Control: no-cache was sent');

                return;
            }

            if (isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] === 'no-cache') {
                $this->disable('HTTP Header Pragma: no-cache was sent');

                return;
            }
        }

        try {
            if ($conf = $this->config['full_page_cache']) {
                if (empty($conf['enabled'])) {
                    $this->disable();

                    return;
                }

                if (Pimcore::inDebugMode()) {
                    $this->disable('Debug flag DISABLE_FULL_PAGE_CACHE is enabled');

                    return;
                }

                if (!empty($conf['lifetime'])) {
                    $this->setLifetime((int) $conf['lifetime']);
                }

                if (!empty($conf['exclude_patterns'])) {
                    $confExcludePatterns = explode(',', $conf['exclude_patterns']);
                    $excludePatterns = $confExcludePatterns;
                }

                if (!empty($conf['exclude_cookie'])) {
                    $cookies = explode(',', (string)$conf['exclude_cookie']);

                    foreach ($cookies as $cookie) {
                        if (!empty($cookie) && isset($_COOKIE[trim($cookie)])) {
                            $this->disable('exclude cookie in system-settings matches');

                            return;
                        }
                    }
                }

                if ($this->sessionStatus->isDisabledBySession($request)) {
                    $this->disable('Session in use');

                    return;
                }

                // output-cache is always disabled when logged in at the admin ui
                if (null !== $pimcoreUser = Tool\Authentication::authenticateSession($request)) {
                    $this->disable('backend user is logged in');

                    return;
                }
            } else {
                $this->disable();

                return;
            }
        } catch (Exception $e) {
            Logger::error((string) $e);

            $this->disable('ERROR: Exception (see log files in /var/log)');

            return;
        }

        foreach ($excludePatterns as $pattern) {
            if (@preg_match($pattern, $requestUri)) {
                $this->disable('exclude path pattern in system-settings matches');

                return;
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

        if ($request->isXmlHttpRequest()) {
            $appendKey .= 'xhr';
        }

        $appendKey .= $request->getMethod();

        $this->defaultCacheKey = 'output_' . md5(\Pimcore\Tool::getHostname() . $requestUri . $appendKey);
        $cacheKeys = [
            $this->defaultCacheKey . '_' . $device,
            $this->defaultCacheKey,
        ];

        $cacheKey = null;
        $cacheItem = null;
        foreach ($cacheKeys as $cacheKey) {
            $cacheItem = Cache::load($cacheKey);
            if ($cacheItem) {
                break;
            }
        }

        if ($cacheItem) {
            /** @var Response $response */
            $response = $cacheItem;
            $response->headers->set('X-Pimcore-Output-Cache-Tag', $cacheKey, true);
            $cacheItemDate = strtotime($response->headers->get('X-Pimcore-Cache-Date'));
            $response->headers->set('Age', (string) (time() - $cacheItemDate));

            $event->setResponse($response);
            $this->stopResponsePropagation = true;
        }
    }

    public function stopPropagationCheck(KernelEvent $event): void
    {
        if ($this->stopResponsePropagation) {
            $event->stopPropagation();
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!\Pimcore\Tool::isFrontend() || \Pimcore\Tool::isFrontendRequestByAdmin($request)) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($this->matchesStaticPageContext($request)) {
            $this->disable('Response can\'t be cached for static pages');
        }

        $response = $event->getResponse();

        if (!$this->responseCanBeCached($response)) {
            $this->disable('Response can\'t be cached');
        }

        if ($this->enabled && $this->sessionStatus->isDisabledBySession($request)) {
            $this->disable('Session in use');
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
                    $date = new DateTime('now');
                    $date->add(new DateInterval('PT' . $this->lifetime . 'S'));
                    $response->headers->set('Expires', $date->format(DateTimeInterface::RFC1123), true);
                }

                $now = new DateTime('now');
                $response->headers->set('X-Pimcore-Cache-Date', $now->format(DateTimeInterface::ATOM));

                $cacheKey = $this->defaultCacheKey;
                $deviceDetector = Tool\DeviceDetector::getInstance();
                if ($deviceDetector->wasUsed()) {
                    $cacheKey .= '_' . $deviceDetector->getDevice();
                }

                $event = new PrepareResponseEvent($request, $response);
                $this->eventDispatcher->dispatch($event, FullPageCacheEvents::PREPARE_RESPONSE);

                $cacheItem = $event->getResponse();

                $tags = ['output'];
                if ($this->lifetime) {
                    $tags = ['output_lifetime'];
                }

                Cache::save($cacheItem, $cacheKey, $tags, $this->lifetime, 1000, true);
            } catch (Exception $e) {
                Logger::error((string) $e);

                return;
            }
        } else {
            // output-cache was disabled, add "output" as cleared tag to ensure that no other "output" tagged elements
            // like the inc and snippet cache get into the cache
            Cache::addIgnoredTagOnSave('output_inline');
        }
    }

    private function responseCanBeCached(Response $response): bool
    {
        $cache = true;

        // do not cache when the application indicated the 'no-store' directives in the response Cache-Control header
        if ($response->headers->hasCacheControlDirective('no-store')) {
            $cache = false;
        }

        // do not cache common responses
        if ($response instanceof BinaryFileResponse) {
            $cache = false;
        }

        if ($response instanceof StreamedResponse) {
            $cache = false;
        }

        // fire an event to allow full customozations
        $event = new CacheResponseEvent($response, $cache);
        $this->eventDispatcher->dispatch($event, FullPageCacheEvents::CACHE_RESPONSE);

        return $event->getCache();
    }
}
