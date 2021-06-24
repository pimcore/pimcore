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

use Pimcore\Config;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Model\Document;
use Pimcore\Routing\DocumentRoute;
use Pimcore\Tool;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
final class StaticPageRouteHandler implements DynamicRouteHandlerInterface
{
    /**
     * @var StaticPageGenerator
     */
    protected $staticPageGenerator;

    /**
     * @var Config
     */
    private $config;


    public function __construct(StaticPageGenerator $staticPageGenerator, Config $config)
    {
        $this->staticPageGenerator = $staticPageGenerator;
        $this->config = $config['documents'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName(string $name)
    {
        if (preg_match('/^document_(\d+)$/', $name, $match)) {
            $document = Document::getById($match[1]);

            if ($document && $this->hasStaticPage($document)) {
                return $this->buildStaticPageRoute($document);
            }
        }

        throw new RouteNotFoundException(sprintf("Route for name '%s' was not found", $name));
    }

    public function matchRequest(RouteCollection $collection, DynamicRequestContext $context)
    {
        if (Tool::isFrontendRequestByAdmin()) {
            return;
        }

        if (!$this->config['static_page_router']['enabled']) {
            return;
        }

        $routePatterns = $this->config['static_page_router']['route_patterns'];
        if (!empty($routePatterns) && !@preg_match($routePatterns, $context->getPath())) {
            return;
        }

        $document = Document::getByPath($context->getPath());
        if ($document && $document instanceof Document\Page && $this->hasStaticPage($document)) {
            if ($route = $this->buildStaticPageRoute($document, $context)) {
                $collection->add($route->getRouteKey(), $route);
            }
        }
    }

    /**
     * @param $document
     *
     * @return bool
     */
    private function hasStaticPage($document) {
        return $this->staticPageGenerator->pageExists($document);
    }

    /**
     * Build a route for a document static page.
     *
     * @param Document $document
     * @param DynamicRequestContext|null $context
     *
     * @return Route|null
     */
    private function buildStaticPageRoute(Document $document, DynamicRequestContext $context = null)
    {
        $route = new DocumentRoute($document->getFullPath());
        $route->setDocument($document);
        $route->setOption('utf8', true);
        $route->setDefault('_controller', 'Pimcore\Bundle\CoreBundle\Controller\PublicServicesController::staticPageAction');

        return $route;
    }
}
