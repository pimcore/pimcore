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

namespace Pimcore\Routing;

use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Model\Site;
use Pimcore\Routing\Redirect\RedirectUrlPartResolver;
use Pimcore\Tool;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var SiteResolver
     */
    private $siteResolver;

    /**
     * @var Redirect[]
     */
    private $redirects;

    /**
     * For BC, this is currently added as extra method call. The required annotation
     * makes sure this is called via autowiring.
     *
     * TODO Pimcore 6 set as constructor dependency
     *
     * @required
     *
     * @param RequestHelper $requestHelper
     */
    public function setRequestHelper(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * For BC, this is currently added as extra method call. The required annotation
     * makes sure this is called via autowiring.
     *
     * TODO Pimcore 6 set as constructor dependency
     *
     * @required
     *
     * @param SiteResolver $siteResolver
     */
    public function setSiteResolver(SiteResolver $siteResolver)
    {
        $this->siteResolver = $siteResolver;
    }

    /**
     * @param Request $request
     * @param bool $override
     *
     * @return null|Response
     */
    public function checkForRedirect(Request $request, $override = false)
    {
        // not for admin requests
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return null;
        }

        // get current site if available
        $sourceSite = null;
        if ($this->siteResolver->isSiteRequest($request)) {
            $sourceSite = $this->siteResolver->getSite($request);
        }

        $config       = Config::getSystemConfig();
        $partResolver = new RedirectUrlPartResolver($request);

        foreach ($this->getFilteredRedirects($override) as $redirect) {
            if (null !== $response = $this->matchRedirect($redirect, $request, $partResolver, $config, $sourceSite)) {
                return $response;
            }
        }
    }

    private function matchRedirect(
        Redirect $redirect,
        Request $request,
        RedirectUrlPartResolver $partResolver,
        Config\Config $config,
        Site $sourceSite = null
    ) {
        if (empty($redirect->getType())) {
            return null;
        }

        $matchPart = $partResolver->getRequestUriPart($redirect->getType());
        $matches   = [];

        $doesMatch = false;
        if ($redirect->isRegex()) {
            $doesMatch = (bool)@preg_match($redirect->getSource(), $matchPart, $matches);
        } else {
            $source    = str_replace('+', ' ', $redirect->getSource()); // see #2202
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

        $target = $redirect->getTarget();
        if (is_numeric($target)) {
            $d = Document::getById($target);
            if ($d instanceof Document\Page || $d instanceof Document\Link || $d instanceof Document\Hardlink) {
                $target = $d->getFullPath();
            } else {
                $this->logger->error('Target of redirect {redirect} not found (Document-ID: {document})', [
                    'redirect' => $redirect->getId(),
                    'document' => $target
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

        if ($redirect->getTargetSite() && !preg_match('@http(s)?://@i', $url)) {
            try {
                $targetSite = Site::getById($redirect->getTargetSite());

                // if the target site is specified and and the target-path is starting at root (not absolute to site)
                // the root-path will be replaced so that the page can be shown
                $url = preg_replace('@^' . $targetSite->getRootPath() . '/@', '/', $url);
                $url = $request->getScheme() . '://' . $targetSite->getMainDomain() . $url;
            } catch (\Exception $e) {
                $this->logger->error('Site with ID {targetSite} not found', [
                    'redirect'   => $redirect->getId(),
                    'targetSite' => $redirect->getTargetSite()
                ]);

                return null;
            }
        } elseif (!preg_match('@http(s)?://@i', $url) && $config->general->domain) {
            // prepend the host and scheme to avoid infinite loops when using "domain" redirects
            $url = $request->getScheme() . '://' . $config->general->domain . $url;
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
        $response   = new RedirectResponse($url, $statusCode);

        // log all redirects to the redirect log
        \Pimcore\Log\Simple::log(
            'redirect',
            Tool::getAnonymizedClientIp() . " \t Custom-Redirect ID: " . $redirect->getId() . ', Source: ' . $_SERVER['REQUEST_URI'] . ' -> ' . $url
        );

        return $response;
    }

    /**
     * @return Redirect[]
     */
    private function getRedirects()
    {
        if (null !== $this->redirects && is_array($this->redirects)) {
            return $this->redirects;
        }

        $cacheKey = 'system_route_redirect';
        if (!($this->redirects = Cache::load($cacheKey))) {
            $list = new Redirect\Listing();
            $list->setCondition('active = 1');
            $list->setOrder('DESC');
            $list->setOrderKey('priority');

            $this->redirects = $list->load();

            Cache::save($this->redirects, $cacheKey, ['system', 'redirect', 'route'], null, 998);
        }

        if (!is_array($this->redirects)) {
            $this->logger->warning('Failed to load redirects', [
                'redirects' => $this->redirects
            ]);

            $this->redirects = [];
        }

        return $this->redirects;
    }

    /**
     * @param bool $override
     *
     * @return Redirect[]
     */
    private function getFilteredRedirects($override = false)
    {
        $now = time();

        return array_filter($this->getRedirects(), function (Redirect $redirect) use ($override, $now) {
            // this is the case when maintenance did't deactivate the redirect yet but it is already expired
            if (!empty($redirect->getExpiry()) && $redirect->getExpiry() < $now) {
                return false;
            }

            if ($override) {
                // if override is true the priority has to be 99 which means that overriding is ok
                return (int)$redirect->getPriority() === 99;
            } else {
                return (int)$redirect->getPriority() !== 99;
            }
        });
    }
}
