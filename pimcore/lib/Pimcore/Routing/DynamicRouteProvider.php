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

use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Document;
use Pimcore\Service\Document\NearestPathResolver;
use Pimcore\Service\MvcConfigNormalizer;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;

class DynamicRouteProvider implements RouteProviderInterface
{
    /**
     * @var NearestPathResolver
     */
    protected $nearestPathResolver;

    /**
     * @var RedirectHandler
     */
    protected $redirectHandler;

    /**
     * @var MvcConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @param NearestPathResolver $nearestPathResolver
     * @param RedirectHandler $redirectHandler
     * @param MvcConfigNormalizer $configNormalizer
     */
    public function __construct(
        NearestPathResolver $nearestPathResolver,
        RedirectHandler $redirectHandler,
        MvcConfigNormalizer $configNormalizer
    )
    {
        $this->nearestPathResolver = $nearestPathResolver;
        $this->redirectHandler     = $redirectHandler;
        $this->configNormalizer    = $configNormalizer;
    }

    /**
     * @inheritdoc
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();
        $path       = urldecode($request->getPathInfo());

        // handled by SiteListener which runs before routing is started
        if ($request->attributes->has('_site_path')) {
            $path = $request->attributes->get('_site_path');
        }

        $document = Document::getByPath($path);
        if ($document) {
            $document = $this->handleHardlink($document, $path);

            if ($document && $document instanceof Document) {
                if ($route = $this->buildRouteForDocument($document)) {
                    $collection->add($route->getRouteKey(), $route);
                }
            }
        }

        // TODO throwing an exception here feels kinda hacky - try to find a better way
        if ($collection->count() === 0) {
            if (null !== $response = $this->redirectHandler->checkForRedirect($request, false)) {
                throw new ResponseException($response);
            }
        }

        return $collection;
    }

    /**
     * @param Document|mixed $document
     * @param string $path
     * @return Document
     */
    protected function handleHardlink($document, $path)
    {
        // check for a parent hardlink with childs
        if (!$document instanceof Document) {
            $hardlinkedParentDocument = $this->nearestPathResolver->getNearestDocumentByPath($path, true);
            if ($hardlinkedParentDocument instanceof Document\Hardlink) {
                if ($hardLinkedDocument = Document\Hardlink\Service::getChildByPath($hardlinkedParentDocument, $path)) {
                    $document = $hardLinkedDocument;
                }
            }
        }

        return $document;
    }

    /**
     * @param Document|Document\Page $document
     * @return DocumentRoute
     */
    protected function buildRouteForDocument(Document $document)
    {
        if (!$this->handleDocument($document)) {
            return null;
        }

        $locale = $document->getProperty('language');

        // check for direct hardlink
        if ($document instanceof Document\Hardlink) {
            $hardlinkParentDocument = $document;
            $document = Document\Hardlink\Service::wrap($hardlinkParentDocument);
        }

        $route = new DocumentRoute($document->getRealFullPath());
        $route->setDefault('_locale', $locale);
        $route->setDocument($document);

        if ($document instanceof Document\Link) {
            // TODO use RedirectRoute?
            $route->setDefault('_controller', 'FrameworkBundle:Redirect:urlRedirect');
            $route->setDefault('path', $document->getHref());
            $route->setDefault('permanent', true);
        } else {
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
        }

        return $route;
    }

    /**
     * @inheritdoc
     */
    public function getRouteByName($name)
    {
        if (preg_match('/^document_(\d+)$/', $name, $match)) {
            $document = Document::getById($match[1]);

            if ($document && $this->handleDocument($document)) {
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
