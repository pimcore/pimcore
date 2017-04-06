<?php

declare(strict_types=1);

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

namespace Pimcore\Routing\Dynamic;

use Pimcore\Config;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Routing\DocumentRoute;
use Pimcore\Service\Document\NearestPathResolver;
use Pimcore\Service\MvcConfigNormalizer;
use Pimcore\Service\Request\SiteResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DocumentRouteHandler implements DynamicRouteHandler
{
    /**
     * @var Document\Service
     */
    private $documentService;

    /**
     * @var SiteResolver
     */
    private $siteResolver;

    /**
     * @var NearestPathResolver
     */
    private $nearestPathResolver;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var MvcConfigNormalizer
     */
    private $configNormalizer;

    /**
     * @var array
     */
    private $directRouteDocumentTypes = ['page', 'snippet', 'email', 'newsletter', 'printpage', 'printcontainer'];

    /**
     * @param Document\Service $documentService
     * @param SiteResolver $siteResolver
     * @param NearestPathResolver $nearestPathResolver
     * @param RequestHelper $requestHelper
     * @param MvcConfigNormalizer $configNormalizer
     */
    public function __construct(
        Document\Service $documentService,
        SiteResolver $siteResolver,
        NearestPathResolver $nearestPathResolver,
        RequestHelper $requestHelper,
        MvcConfigNormalizer $configNormalizer
    )
    {
        $this->documentService     = $documentService;
        $this->siteResolver        = $siteResolver;
        $this->nearestPathResolver = $nearestPathResolver;
        $this->requestHelper       = $requestHelper;
        $this->configNormalizer    = $configNormalizer;
    }

    /**
     * @return array
     */
    public function getDirectRouteDocumentTypes()
    {
        return $this->directRouteDocumentTypes;
    }

    /**
     * @param string $type
     */
    public function addDirectRouteDocumentType($type)
    {
        if (!in_array($type, $this->directRouteDocumentTypes)) {
            $this->directRouteDocumentTypes[] = $type;
        }
    }

    /**
     * @inheritDoc
     */
    public function getRouteByName(string $name)
    {
        if (preg_match('/^document_(\d+)$/', $name, $match)) {
            $document = Document::getById($match[1]);

            if ($this->isDirectRouteDocument($document) && $this->isDocumentSupported($document)) {
                return $this->buildRouteForDocument($document);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function matchRequest(RouteCollection $collection, DynamicRequestContext $context)
    {
        $document = Document::getByPath($context->getPath());

        // check for a pretty url inside a site
        if (!$document && $this->siteResolver->isSiteRequest($context->getRequest())) {
            $site = $this->siteResolver->getSite($context->getRequest());

            $sitePrettyDocId = $this->documentService->getDao()->getDocumentIdByPrettyUrlInSite($site, $context->getOriginalPath());
            if ($sitePrettyDocId) {
                if ($sitePrettyDoc = Document::getById($sitePrettyDocId)) {
                    $document = $sitePrettyDoc;

                    // TODO set pretty path via siteResolver?
                    // undo the modification of the path by the site detection (prefixing with site root path)
                    // this is not necessary when using pretty-urls and will cause problems when validating the
                    // prettyUrl later (redirecting to the prettyUrl in the case the page was called by the real path)
                    $path = $context->getOriginalPath();
                }
            }
        }

        // check for a parent hardlink with childs
        if (!$document instanceof Document) {
            $hardlinkedParentDocument = $this->nearestPathResolver->getNearestDocumentByPath($context->getPath(), true);
            if ($hardlinkedParentDocument instanceof Document\Hardlink) {
                if ($hardLinkedDocument = Document\Hardlink\Service::getChildByPath($hardlinkedParentDocument, $context->getPath())) {
                    $document = $hardLinkedDocument;
                }
            }
        }

        if ($document && $document instanceof Document) {
            if ($route = $this->buildRouteForDocument($document, $context)) {
                $collection->add($route->getRouteKey(), $route);
            }
        }
    }

    /**
     * Build a route for a document. Context is only set from match mode, not when generating URLs.
     *
     * @param Document $document
     * @param DynamicRequestContext|null $context
     *
     * @return DocumentRoute|null
     */
    private function buildRouteForDocument(Document $document, DynamicRequestContext $context = null)
    {
        // check for direct hardlink
        if ($document instanceof Document\Hardlink) {
            $document = Document\Hardlink\Service::wrap($document);

            if (!$document) {
                return null;
            }
        }

        // check if document should be handled (not legacy)
        if (!$this->isDocumentSupported($document)) {
            return null;
        }

        $locale = $document->getProperty('language');

        $route = new DocumentRoute($document->getFullPath());

        // coming from matching -> set route path the currently matched one
        if (null !== $context) {
            $route->setPath($context->getOriginalPath());
        }

        $route->setDefault('_locale', $locale);
        $route->setDocument($document);

        if ($this->isDirectRouteDocument($document)) {
            /** @var Document\PageSnippet $document */
            $route = $this->handleDirectRouteDocument($document, $route, $context);
        } else if ($document->getType() === 'link') {
            /** @var Document\Link $document */
            $route = $this->handleLinkDocument($document, $route);
        }

        return $route;
    }

    /**
     * Handle route params for link document
     *
     * @param Document\Link $document
     * @param DocumentRoute $route
     *
     * @return DocumentRoute
     */
    private function handleLinkDocument(Document\Link $document, DocumentRoute $route)
    {
        $route->setDefault('_controller', 'FrameworkBundle:Redirect:urlRedirect');
        $route->setDefault('path', $document->getHref());
        $route->setDefault('permanent', true);

        return $route;
    }

    /**
     * Handle direct route documents (not link)
     *
     * @param Document\PageSnippet $document
     * @param DocumentRoute $route
     * @param DynamicRequestContext|null $context
     *
     * @return DocumentRoute|null
     */
    private function handleDirectRouteDocument(
        Document\PageSnippet $document,
        DocumentRoute $route,
        DynamicRequestContext $context = null
    )
    {
        // if we have a request we're currently in match mode (not generating URLs) -> only match when frontend request by admin
        $isAdminRequest = null !== $context && $this->requestHelper->isFrontendRequestByAdmin($context->getRequest());

        // abort if document is not published and the request is no admin request
        if (!$document->isPublished() && !$isAdminRequest) {
            return null;
        }

        if (!$isAdminRequest && null !== $context) {
            // check for redirects (pretty URL, SEO) when not in admin mode and while matching (not generating route)
            if ($redirectRoute = $this->handleDirectRouteRedirect($document, $route, $context)) {
                return $redirectRoute;
            }
        }

        return $this->buildRouteForPageSnippetDocument($document, $route);
    }

    /**
     * Handle document redirects (pretty url, SEO without trailing slash)
     *
     * @param Document\PageSnippet $document
     * @param DocumentRoute $route
     * @param DynamicRequestContext|null $context
     *
     * @return DocumentRoute|null
     */
    private function handleDirectRouteRedirect(
        Document\PageSnippet $document,
        DocumentRoute $route,
        DynamicRequestContext $context = null
    )
    {
        $redirectTargetUrl = $context->getOriginalPath();

        // check for a pretty url, and if the document is called by that, otherwise redirect to pretty url
        if ($document instanceof Document\Page && !$document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            if ($prettyUrl = $document->getPrettyUrl()) {
                if (rtrim(strtolower($prettyUrl), ' /') !== rtrim(strtolower($context->getOriginalPath()), '/')) {
                    $redirectTargetUrl = $prettyUrl;
                }
            }
        }

        // check for a trailing slash in path, if exists, redirect to this page without the slash
        // the only reason for this is: SEO, Analytics, ... there is no system specific reason, pimcore would work also with a trailing slash without problems
        // use $originalPath because of the sites
        // only do redirecting with GET requests
        if ($context->getRequest()->getMethod() === 'GET') {
            $config = Config::getSystemConfig();

            if ($config->documents->allowtrailingslash) {
                if ($config->documents->allowtrailingslash === 'no') {
                    if ($redirectTargetUrl !== '/' && substr($redirectTargetUrl, -1) === '/') {
                        $redirectTargetUrl = rtrim($redirectTargetUrl, '/');
                    }
                }
            }

            // only allow the original key of a document to be the URL (lowercase/uppercase)
            if ($redirectTargetUrl !== rawurldecode($document->getFullPath())) {
                $redirectTargetUrl = $document->getFullPath();
            }
        }

        if (null !== $redirectTargetUrl && $redirectTargetUrl !== $context->getOriginalPath()) {
            $url = $redirectTargetUrl;
            if ($qs = $context->getRequest()->getQueryString()) {
                if (false === strpos($url, '?')) {
                    $url .= '&' . $qs;
                } else {
                    $url .= '?' . $qs;
                }
            }

            $route->setDefault('_controller', 'FrameworkBundle:Redirect:urlRedirect');
            $route->setDefault('path', $url);
            $route->setDefault('permanent', true);

            return $route;
        }
    }

    /**
     * Handle page snippet route (controller, action, view)
     *
     * @param Document\PageSnippet $document
     * @param DocumentRoute $route
     *
     * @return DocumentRoute
     */
    private function buildRouteForPageSnippetDocument(Document\PageSnippet $document, DocumentRoute $route)
    {
        $controller = $this->configNormalizer->formatController(
            $document->getModule(),
            $document->getController(),
            $document->getAction()
        );

        $route->setDefault('_controller', $controller);

        if ($document->getTemplate()) {
            $template = $this->configNormalizer->normalizeTemplate($document->getTemplate());
            $route->setDefault('_template', $template);
        }

        return $route;
    }

    /**
     * Check if document is can be used to generate a route
     *
     * @param $document
     *
     * @return bool
     */
    private function isDirectRouteDocument($document)
    {
        if ($document instanceof Document\PageSnippet) {
            if (in_array($document->getType(), $this->getDirectRouteDocumentTypes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Document $document
     *
     * @return bool
     */
    private function isDocumentSupported(Document $document)
    {
        return !$document->doRenderWithLegacyStack();
    }
}
