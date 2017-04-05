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

use Pimcore\Config;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Service\Document\NearestPathResolver;
use Pimcore\Service\MvcConfigNormalizer;
use Pimcore\Service\Request\SiteResolver;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;

class DynamicRouteProvider implements RouteProviderInterface
{
    /**
     * @var Document\Service
     */
    protected $documentService;

    /**
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var NearestPathResolver
     */
    protected $nearestPathResolver;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var MvcConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @var array
     */
    protected $directRouteDocumentTypes = ['page', 'snippet', 'email', 'newsletter', 'printpage', 'printcontainer'];

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
     * @inheritdoc
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();
        $path       = $originalPath = urldecode($request->getPathInfo());

        // site path handled by FrontendRoutingListener which runs before routing is started
        if (null !== $sitePath = $this->siteResolver->getSitePath($request)) {
            $path = $sitePath;
        }

        $this->matchDocuments($collection, $request, $path, $originalPath);

        return $collection;
    }

    /**
     * Add documents to route collection
     *
     * @param RouteCollection $collection
     * @param Request $request
     * @param string $path
     * @param string $originalPath
     */
    protected function matchDocuments(RouteCollection $collection, Request $request, $path, $originalPath)
    {
        $document = Document::getByPath($path);

        // check for a pretty url inside a site
        if (!$document && $this->siteResolver->isSiteRequest($request)) {
            $site = $this->siteResolver->getSite($request);

            $sitePrettyDocId = $this->documentService->getDao()->getDocumentIdByPrettyUrlInSite($site, $originalPath);
            if ($sitePrettyDocId) {
                if ($sitePrettyDoc = Document::getById($sitePrettyDocId)) {
                    $document = $sitePrettyDoc;

                    // TODO set pretty path via siteResolver?
                    // undo the modification of the path by the site detection (prefixing with site root path)
                    // this is not necessary when using pretty-urls and will cause problems when validating the
                    // prettyUrl later (redirecting to the prettyUrl in the case the page was called by the real path)
                    $path = $originalPath;
                }
            }
        }

        // check for a parent hardlink with childs
        if (!$document instanceof Document) {
            $hardlinkedParentDocument = $this->nearestPathResolver->getNearestDocumentByPath($path, true);
            if ($hardlinkedParentDocument instanceof Document\Hardlink) {
                if ($hardLinkedDocument = Document\Hardlink\Service::getChildByPath($hardlinkedParentDocument, $path)) {
                    $document = $hardLinkedDocument;
                }
            }
        }

        if ($document && $document instanceof Document) {
            if ($route = $this->buildRouteForDocument($document, $request, $path, $originalPath)) {
                $collection->add($route->getRouteKey(), $route);
            }
        }
    }

    /**
     * Build a route for a document. Request, path and originalPath are only set from match mode, not when generating URLs.
     *
     * @param Document $document
     * @param Request|null $request
     * @param string|null $path
     * @param string|null $originalPath
     *
     * @return DocumentRoute
     */
    protected function buildRouteForDocument(Document $document, Request $request = null, $path = null, $originalPath = null)
    {
        // check for direct hardlink
        if ($document instanceof Document\Hardlink) {
            $document = Document\Hardlink\Service::wrap($document);

            if (!$document) {
                return null;
            }
        }

        // check if document should be handled (not legacy)
        if (!$this->handleDocument($document)) {
            return null;
        }

        $locale = $document->getProperty('language');

        $route = new DocumentRoute($document->getFullPath());
        $route->setDefault('_locale', $locale);
        $route->setDocument($document);

        if ($this->isDirectRouteDocument($document)) {
            /** @var Document\PageSnippet $document */
            $route = $this->buildRouteForDirectRouteDocument($document, $route, $request, $path, $originalPath);
        } else if ($document->getType() === 'link') {
            /** @var Document\Link $document */
            $route = $this->buildRouteForLinkDocument($document, $route);
        }

        return $route;
    }

    /**
     * @param Document\PageSnippet $document
     * @param DocumentRoute $route
     * @param Request|null $request
     * @param null $path
     * @param null $originalPath
     *
     * @return null|DocumentRoute
     */
    protected function buildRouteForDirectRouteDocument(Document\PageSnippet $document, DocumentRoute $route, Request $request = null, $path = null, $originalPath = null)
    {
        // if we have a request we're currently in match mode (not generating URLs) -> only match when frontend request by admin
        $isAdminRequest = null !== $request && $this->requestHelper->isFrontendRequestByAdmin($request);

        // abort if document is not published and the request is no admin request
        if (!$document->isPublished() && !$isAdminRequest) {
            return null;
        }

        if (!$isAdminRequest && null !== $request) {
            $redirectTargetUrl = null;

            // check for a pretty url, and if the document is called by that, otherwise redirect to pretty url
            if ($document instanceof Document\Page && !$document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
                if ($prettyUrl = $document->getPrettyUrl()) {
                    if (rtrim(strtolower($prettyUrl), ' /') !== rtrim(strtolower($originalPath), '/')) {
                        $redirectTargetUrl = $prettyUrl;
                    }
                }
            }

            // check for a trailing slash in path, if exists, redirect to this page without the slash
            // the only reason for this is: SEO, Analytics, ... there is no system specific reason, pimcore would work also with a trailing slash without problems
            // use $originalPath because of the sites
            // only do redirecting with GET requests
            if ($request->getMethod() === 'GET') {
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

            if (null !== $redirectTargetUrl && $redirectTargetUrl !== $originalPath) {
                $url = $redirectTargetUrl;
                if ($qs = $request->getQueryString()) {
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

        return $this->buildRouteForPageSnippetDocument($document, $route);
    }

    protected function buildRouteForPageSnippetDocument(Document\PageSnippet $document, DocumentRoute $route)
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

    protected function buildRouteForLinkDocument(Document\Link $document, DocumentRoute $route)
    {
        $route->setDefault('_controller', 'FrameworkBundle:Redirect:urlRedirect');
        $route->setDefault('path', $document->getHref());
        $route->setDefault('permanent', true);

        return $route;
    }

    /**
     * Check if document is can be used to generate a route
     *
     * @param $document
     *
     * @return bool
     */
    protected function isDirectRouteDocument($document)
    {
        if ($document instanceof Document\PageSnippet) {
            if (in_array($document->getType(), $this->getDirectRouteDocumentTypes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getRouteByName($name)
    {
        if (preg_match('/^document_(\d+)$/', $name, $match)) {
            $document = Document::getById($match[1]);

            if ($this->isDirectRouteDocument($document) && $this->handleDocument($document)) {
                return $this->buildRouteForDocument($document);
            }
        }

        throw new RouteNotFoundException(sprintf("Route for name '%s' was not found", $name));
    }

    /**
     * @inheritdoc
     */
    public function getRoutesByNames($names)
    {
        // TODO needs performance optimizations
        // TODO really return all routes here as documentation states? where is this used?
        $routes = [];

        if (is_array($names)) {
            foreach ($names as $name) {
                try {
                    $route = $this->getRouteByName($name);
                    if ($route) {
                        $routes[] = $route;
                    }
                } catch (RouteNotFoundException $e) {
                    // noop
                }
            }
        }

        return $routes;
    }

    // TODO remove - this is just for testing. Overrides all documents with symfony mode
    // (allows to test symfony rendering without having to touch all documents)
    // controller = foo, action = bar becomes AppBundle:Foo:bar
    protected function handleDocument(Document $document)
    {
        if ($document->doRenderWithLegacyStack()) {
            return false;
        }

        return true;
    }
}
