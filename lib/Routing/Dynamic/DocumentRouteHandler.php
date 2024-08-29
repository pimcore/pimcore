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

namespace Pimcore\Routing\Dynamic;

use LogicException;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\Request\Resolver\StaticPageResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Routing\DocumentRoute;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
final class DocumentRouteHandler implements DynamicRouteHandlerInterface
{
    private Document\Service $documentService;

    private SiteResolver $siteResolver;

    private RequestHelper $requestHelper;

    /**
     * Determines if unpublished documents should be matched, even when not in admin mode. This
     * is mainly needed for maintencance jobs/scripts.
     *
     */
    private bool $forceHandleUnpublishedDocuments = false;

    private array $directRouteDocumentTypes = [];

    private Config $config;

    private StaticPageResolver $staticPageResolver;

    public function __construct(
        Document\Service $documentService,
        SiteResolver $siteResolver,
        RequestHelper $requestHelper,
        Config $config,
        StaticPageResolver $staticPageResolver
    ) {
        $this->documentService = $documentService;
        $this->siteResolver = $siteResolver;
        $this->requestHelper = $requestHelper;
        $this->config = $config;
        $this->staticPageResolver = $staticPageResolver;
    }

    public function setForceHandleUnpublishedDocuments(bool $handle): void
    {
        $this->forceHandleUnpublishedDocuments = $handle;
    }

    public function getDirectRouteDocumentTypes(): array
    {
        if (empty($this->directRouteDocumentTypes)) {
            $documentConfig = \Pimcore\Config::getSystemConfiguration('documents');
            foreach ($documentConfig['type_definitions']['map'] as $type => $config) {
                if (isset($config['direct_route']) && $config['direct_route']) {
                    $this->directRouteDocumentTypes[] = $type;
                }
            }
        }

        return $this->directRouteDocumentTypes;
    }

    public function getRouteByName(string $name): ?DocumentRoute
    {
        if (preg_match('/^document_(\d+)$/', $name, $match)) {
            $document = Document::getById((int) $match[1]);

            if ($this->isDirectRouteDocument($document)) {
                return $this->buildRouteForDocument($document);
            }
        }

        throw new RouteNotFoundException(sprintf("Route for name '%s' was not found", $name));
    }

    public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void
    {
        $document = Document::getByPath($context->getPath());
        $site = $this->siteResolver->getSite($context->getRequest());

        // If the request is not from a site and the document is part of a site
        // or the ID of the requested site does not match the site where the document is located.
        // Then we have to throw a NotFoundHttpException
        if (!$site && $document && !Tool::isFrontendRequestByAdmin()) {
            $siteIdOfDocument = Frontend::getSiteIdForDocument($document);
            if ($siteIdOfDocument) {
                throw new NotFoundHttpException('The page does not exist on this configured site.');
            }
        }

        // check for a pretty url inside a site
        if (!$document && $this->siteResolver->isSiteRequest($context->getRequest())) {
            $sitePrettyDocId = $this->documentService->getDao()->getDocumentIdByPrettyUrlInSite($site, $context->getOriginalPath());
            if ($sitePrettyDocId) {
                if ($sitePrettyDoc = Document::getById($sitePrettyDocId)) {
                    $document = $sitePrettyDoc;

                    // TODO set pretty path via siteResolver?
                    // undo the modification of the path by the site detection (prefixing with site root path)
                    // this is not necessary when using pretty-urls and will cause problems when validating the
                    // prettyUrl later (redirecting to the prettyUrl in the case the page was called by the real path)
                    $context->setPath($context->getOriginalPath());
                }
            }
        }

        // check for a parent hardlink with children
        if (!$document instanceof Document) {
            $hardlinkedParentDocument = $this->documentService->getNearestDocumentByPath($context->getPath(), true);
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
     *
     */
    public function buildRouteForDocument(Document $document, DynamicRequestContext $context = null): ?DocumentRoute
    {
        // check for direct hardlink
        if ($document instanceof Document\Hardlink) {
            $document = Document\Hardlink\Service::wrap($document);

            if (!$document) {
                return null;
            }
        }

        $route = new DocumentRoute($document->getFullPath());
        $route->setOption('utf8', true);

        // coming from matching -> set route path the currently matched one
        if (null !== $context) {
            $route->setPath($context->getOriginalPath());
        }

        $route->setDefault('_locale', $document->getProperty('language'));
        $route->setDocument($document);

        if ($this->isDirectRouteDocument($document)) {
            /** @var Document\PageSnippet $document */
            $route = $this->handleDirectRouteDocument($document, $route, $context);
        } elseif ($document->getType() === 'link') {
            /** @var Document\Link $document */
            $route = $this->handleLinkDocument($document, $route);
        }

        return $route;
    }

    /**
     * Handle route params for link document
     */
    private function handleLinkDocument(Document\Link $document, DocumentRoute $route): DocumentRoute
    {
        $route->setDefault('_controller', 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction');
        $route->setDefault('path', $document->getHref());
        $route->setDefault('permanent', true);

        return $route;
    }

    /**
     * Handle direct route documents (not link)
     */
    private function handleDirectRouteDocument(
        Document\PageSnippet $document,
        DocumentRoute $route,
        DynamicRequestContext $context = null
    ): ?DocumentRoute {
        // if we have a request in context, we're currently in match mode (not generating URLs) -> only match when frontend request by admin
        try {
            $request = $context ? $context->getRequest() : $this->requestHelper->getMainRequest();
            $isAdminRequest = $this->requestHelper->isFrontendRequestByAdmin($request);
        } catch (LogicException $e) {
            // catch logic exception here - when the exception fires, it is no admin request
            $isAdminRequest = false;
        }

        // abort if document is not published and the request is no admin request
        // and matching unpublished documents was not forced
        if (!$document->isPublished()) {
            if (!($isAdminRequest || $this->forceHandleUnpublishedDocuments)) {
                return null;
            }
        }

        if (!$isAdminRequest && null !== $context) {
            // check for redirects (pretty URL, SEO) when not in admin mode and while matching (not generating route)
            if ($redirectRoute = $this->handleDirectRouteRedirect($document, $route, $context)) {
                return $redirectRoute;
            }

            // set static page context
            if ($document instanceof Page && $document->getStaticGeneratorEnabled()) {
                $this->staticPageResolver->setStaticPageContext($context->getRequest());
            }
        }

        // Use latest version, if available, when the request is admin request
        // so then route should be built based on latest Document settings
        // https://github.com/pimcore/pimcore/issues/9644
        if ($isAdminRequest) {
            $latestVersion = $document->getLatestVersion();
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();
                if ($latestDoc instanceof Document\PageSnippet) {
                    $document = $latestDoc;
                }
            }
        }

        return $this->buildRouteForPageSnippetDocument($document, $route);
    }

    /**
     * Handle document redirects (pretty url, SEO without trailing slash)
     */
    private function handleDirectRouteRedirect(
        Document\PageSnippet $document,
        DocumentRoute $route,
        DynamicRequestContext $context = null
    ): ?DocumentRoute {
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
            if (($this->config['documents']['allow_trailing_slash'] ?? null) === 'no') {
                if ($redirectTargetUrl !== '/' && str_ends_with($redirectTargetUrl, '/')) {
                    $redirectTargetUrl = rtrim($redirectTargetUrl, '/');
                }
            }

            // only allow the original key of a document to be the URL (lowercase/uppercase)
            if ($redirectTargetUrl !== '/' && rtrim($redirectTargetUrl, '/') !== rawurldecode($document->getFullPath())) {
                $redirectTargetUrl = $document->getFullPath();
            }
        }

        if (null !== $redirectTargetUrl && $redirectTargetUrl !== $context->getOriginalPath()) {
            $route->setDefault('_controller', 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction');
            $route->setDefault('path', $redirectTargetUrl);
            $route->setDefault('permanent', true);

            return $route;
        }

        return null;
    }

    /**
     * Handle page snippet route (controller, action, view)
     */
    private function buildRouteForPageSnippetDocument(Document\PageSnippet $document, DocumentRoute $route): DocumentRoute
    {
        $route->setDefault('_controller', $document->getController());

        if ($document->getTemplate()) {
            $route->setDefault('_template', $document->getTemplate());
        }

        return $route;
    }

    /**
     * Check if document is can be used to generate a route
     */
    private function isDirectRouteDocument(?Document $document): bool
    {
        if ($document instanceof Document\PageSnippet) {
            if (in_array($document->getType(), $this->getDirectRouteDocumentTypes())) {
                return true;
            }
        }

        return false;
    }
}
