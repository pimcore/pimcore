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
use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Model\Site;
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
     * @var Redirect[]
     */
    private $redirects;

    /**
     * @param Request $request
     * @param bool $override
     *
     * @return null|Response
     */
    public function checkForRedirect(Request $request, $override = false)
    {
        // not for admin requests
        if (Tool::isFrontentRequestByAdmin()) {
            return null;
        }

        $matchRequestUri = urldecode($request->getPathInfo());
        $config          = Config::getSystemConfig();

        // get current site if available
        $sourceSite = null;
        if (Site::isSiteRequest()) {
            $sourceSite = Site::getCurrentSite();
        }

        $matchUrl = Tool::getHostUrl() . $matchRequestUri;
        if (!empty($_SERVER["QUERY_STRING"])) {
            $matchUrl .= "?" . $_SERVER["QUERY_STRING"];
        }

        foreach ($this->getFilteredRedirects($override) as $redirect) {
            $matchAgainst = $matchRequestUri;
            if ($redirect->getSourceEntireUrl()) {
                $matchAgainst = $matchUrl;
            }

            if (null !== $response = $this->matchRedirect($redirect, $request, $config, $matchAgainst, $sourceSite)) {
                return $response;
            }
        }
    }

    /**
     * @param Redirect $redirect
     * @param Request $request
     * @param Config\Config $config
     * @param string $matchUri
     *
     * @param Site|null $sourceSite
     *
     * @return null|Response
     */
    private function matchRedirect(Redirect $redirect, Request $request, Config\Config $config, $matchUri, Site $sourceSite = null)
    {
        if (!@preg_match($redirect->getSource(), $matchUri, $matches)) {
            return null;
        }

        // check for a site
        if ($redirect->getSourceSite() || $sourceSite) {
            if (!$sourceSite || $sourceSite->getId() !== $redirect->getSourceSite()) {
                return null;
            }
        }

        array_shift($matches);

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

        // support for pcre backreferences
        $url = replace_pcre_backreferences($target, $matches);

        if ($redirect->getTargetSite() && !preg_match("@http(s)?://@i", $url)) {
            try {
                $targetSite = Site::getById($redirect->getTargetSite());

                // if the target site is specified and and the target-path is starting at root (not absolute to site)
                // the root-path will be replaced so that the page can be shown
                $url = preg_replace("@^" . $targetSite->getRootPath() . "/@", "/", $url);
                $url = $request->getScheme() . "://" . $targetSite->getMainDomain() . $url;
            } catch (\Exception $e) {
                $this->logger->error('Site with ID {targetSite} not found', [
                    'redirect'   => $redirect->getId(),
                    'targetSite' => $redirect->getTargetSite()
                ]);

                return null;
            }
        } elseif (!preg_match("@http(s)?://@i", $url) && $config->general->domain && $redirect->getSourceEntireUrl()) {
            // prepend the host and scheme to avoid infinite loops when using "domain" redirects
            $url = $request->getScheme() . "://" . $config->general->domain . $url;
        }

        // pass-through parameters if specified
        $queryString = $request->getQueryString();
        if ($redirect->getPassThroughParameters() && !empty($queryString)) {
            $glue = "?";
            if (strpos($url, "?")) {
                $glue = "&";
            }

            $url .= $glue;
            $url .= $queryString;
        }

        $statusCode = $redirect->getStatusCode() ?: Response::HTTP_MOVED_PERMANENTLY;
        $response   = new RedirectResponse($url, $statusCode);

        // log all redirects to the redirect log
        \Pimcore\Log\Simple::log(
            "redirect",
            Tool::getAnonymizedClientIp() . " \t Custom-Redirect ID: " . $redirect->getId() . ", Source: " . $_SERVER["REQUEST_URI"] . " -> " . $url
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

        $cacheKey = "system_route_redirect";
        if (!($this->redirects = Cache::load($cacheKey))) {
            $list = new Redirect\Listing();
            $list->setCondition("active = 1");
            $list->setOrder("DESC");
            $list->setOrderKey("priority");

            $this->redirects = $list->load();

            Cache::save($this->redirects, $cacheKey, ["system", "redirect", "route"], null, 998);
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
        return array_filter($this->getRedirects(), function (Redirect $redirect) use ($override) {
            if ($override) {
                // if override is true the priority has to be 99 which means that overriding is ok
                return (int)$redirect->getPriority() === 99;
            } else {
                return (int)$redirect->getPriority() !== 99;
            }
        });
    }
}
