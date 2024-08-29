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

namespace Pimcore\Bundle\SeoBundle\Redirect;

use Exception;
use Pimcore\Bundle\SeoBundle\Event\Model\RedirectEvent;
use Pimcore\Bundle\SeoBundle\Event\RedirectEvents;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tool;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 */
final class RedirectHandler
{
    use RecursionBlockingEventDispatchHelperTrait;

    const RESPONSE_HEADER_NAME_ID = 'X-Pimcore-Redirect-ID';

    private LoggerInterface $logger;

    private LoggerInterface $redirectLogger;

    private RequestHelper $requestHelper;

    private SiteResolver $siteResolver;

    /**
     * @var Redirect[]|null
     */
    private ?array $redirects = null;

    private Config $config;

    private ?LockInterface $lock = null;

    public function __construct(RequestHelper $requestHelper, SiteResolver $siteResolver, Config $config, LockFactory $lockFactory, LoggerInterface $routingLogger, LoggerInterface $redirectLogger)
    {
        $this->requestHelper = $requestHelper;
        $this->siteResolver = $siteResolver;
        $this->config = $config;
        $this->lock = $lockFactory->createLock(self::class);
        $this->logger = $routingLogger;
        $this->redirectLogger = $redirectLogger;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function checkForRedirect(Request $request, bool $override = false, Site $sourceSite = null): ?Response
    {
        // not for admin requests
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return null;
        }

        // get current site if available
        if (!$sourceSite && $this->siteResolver->isSiteRequest($request)) {
            $sourceSite = $this->siteResolver->getSite($request);
        }

        if ($redirect = Redirect::getByExactMatch($request, $sourceSite, $override)) {
            if (null !== $response = $this->buildRedirectResponse($redirect, $request)) {
                return $response;
            }
        }

        $partResolver = new RedirectUrlPartResolver($request);
        foreach ($this->getRegexFilteredRedirects($override) as $redirect) {
            if (null !== $response = $this->matchRegexRedirect($redirect, $request, $partResolver, $sourceSite)) {
                return $response;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function matchRegexRedirect(
        Redirect $redirect,
        Request $request,
        RedirectUrlPartResolver $partResolver,
        Site $sourceSite = null
    ): ?Response {
        if (empty($redirect->getType())) {
            return null;
        }

        $matchPart = $partResolver->getRequestUriPart($redirect->getType());
        $matches = [];

        $doesMatch = false;
        if ($redirect->isRegex()) {
            $doesMatch = (bool)@preg_match($redirect->getSource(), $matchPart, $matches);
        } else {
            $source = str_replace('+', ' ', $redirect->getSource()); // see #2202
            $doesMatch = $source === $matchPart;
        }

        if (!$doesMatch) {
            return null;
        }

        // check for a site
        if ($redirect->getSourceSite() || $sourceSite) {
            if (!$sourceSite || $sourceSite->getId() !== $redirect->getSourceSite()) {
                return null;
            }
        }

        return $this->buildRedirectResponse($redirect, $request, $matches);
    }

    /**
     *
     *
     * @throws Exception
     */
    protected function buildRedirectResponse(Redirect $redirect, Request $request, array $matches = []): ?Response
    {
        $this->dispatchEvent(new RedirectEvent($redirect), RedirectEvents::PRE_BUILD);
        $target = $redirect->getTarget();
        if (is_numeric($target)) {
            $d = Document::getById((int) $target);
            if ($d instanceof Document\Page || $d instanceof Document\Link || $d instanceof Document\Hardlink) {
                $target = $d->getFullPath();
            } else {
                $this->logger->error('Target of redirect {redirect} not found (Document-ID: {document})', [
                    'redirect' => $redirect->getId(),
                    'document' => $target,
                ]);

                return null;
            }
        }

        $url = $target;
        if ($redirect->isRegex()) {
            array_shift($matches);

            // support for pcre backreferences
            $url = replace_pcre_backreferences($url, $matches);
        }

        if (!preg_match('@http(s)?://@i', $url)) {
            if ($redirect->getTargetSite()) {
                if ($targetSite = Site::getById($redirect->getTargetSite())) {
                    // if the target site is specified and and the target-path is starting at root (not absolute to site)
                    // the root-path will be replaced so that the page can be shown
                    $url = preg_replace('@^' . $targetSite->getRootPath() . '/@', '/', $url);
                    $url = $request->getScheme() . '://' . $targetSite->getMainDomain() . $url;
                } else {
                    $this->logger->error('Site with ID {targetSite} not found', [
                        'redirect' => $redirect->getId(),
                        'targetSite' => $redirect->getTargetSite(),
                    ]);

                    return null;
                }
            } else {
                $site = Site::getByDomain($request->getHost());
                if ($site instanceof Site) {
                    $redirectDomain = $request->getHost();
                } else {
                    $redirectDomain = $this->config['general']['domain'];
                }

                if ($redirectDomain) {
                    // prepend the host and scheme to avoid infinite loops when using "domain" redirects
                    $url = $request->getScheme().'://'.$redirectDomain.$url;
                }
            }
        }

        // pass-through parameters if specified
        $queryString = $request->getQueryString();
        if ($redirect->getPassThroughParameters() && !empty($queryString)) {
            $glue = '?';
            if (strpos($url, '?')) {
                $glue = '&';
            }

            $url .= $glue;
            $url .= $queryString;
        }

        $statusCode = $redirect->getStatusCode() ?: Response::HTTP_MOVED_PERMANENTLY;
        $response = new Response(null, $statusCode);

        if ($response->isRedirect()) {
            $response = new RedirectResponse($url, $statusCode);
        }

        $response->headers->set(self::RESPONSE_HEADER_NAME_ID, (string) $redirect->getId());

        $this->redirectLogger->info(Tool::getAnonymizedClientIp() ?? 'Anonymous', ['Custom-Redirect ID: ' . $redirect->getId() . ', Source: ' . $request->getRequestUri() . ' -> ' . $url]);

        return $response;
    }

    /**
     * @return Redirect[]
     */
    private function getRegexRedirects(): array
    {
        if (is_array($this->redirects)) {
            return $this->redirects;
        }

        $cacheKey = 'system_route_redirect';
        $valueFromCache = Cache::load($cacheKey);
        $this->redirects = $valueFromCache === false ? null : $valueFromCache;
        if (!isset($this->redirects)) {
            // acquire lock to avoid concurrent redirect cache warm-up
            $this->lock->acquire(true);

            //check again if redirects are cached to avoid re-warming cache
            $valueFromCache = Cache::load($cacheKey);
            $this->redirects = $valueFromCache === false ? null : $valueFromCache;
            if (!isset($this->redirects)) {
                try {
                    $list = new Redirect\Listing();
                    $list->setCondition('active = 1 AND regex = 1');
                    $list->setOrder('DESC');
                    $list->setOrderKey('priority');

                    $this->redirects = $list->load();

                    Cache::save($this->redirects, $cacheKey, ['system', 'redirect', 'route'], null, 998, true);
                } catch (Exception $e) {
                    $this->logger->error('Failed to load redirects');
                }
            }

            $this->lock->release();
        }

        if (!is_array($this->redirects)) {
            $this->logger->warning('Failed to load redirects', [
                'redirects' => $this->redirects,
            ]);

            $this->redirects = [];
        }

        return $this->redirects;
    }

    /**
     * @return Redirect[]
     */
    private function getRegexFilteredRedirects(bool $override = false): array
    {
        $now = time();

        return array_filter($this->getRegexRedirects(), function (Redirect $redirect) use ($override, $now) {
            // this is the case when maintenance did't deactivate the redirect yet but it is already expired
            if (!empty($redirect->getExpiry()) && $redirect->getExpiry() < $now) {
                return false;
            }

            if ($override) {
                // if override is true the priority has to be 99 which means that overriding is ok
                return $redirect->getPriority() === 99;
            } else {
                return $redirect->getPriority() !== 99;
            }
        });
    }
}
