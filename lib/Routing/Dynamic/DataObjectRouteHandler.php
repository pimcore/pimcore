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

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\ElementListener;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Routing\DataObjectRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
final class DataObjectRouteHandler implements DynamicRouteHandlerInterface
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
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Document\Service $documentService
     * @param SiteResolver $siteResolver
     * @param RequestHelper $requestHelper
     */
    public function __construct(
        Document\Service $documentService,
        SiteResolver $siteResolver,
        RequestHelper $requestHelper,
        Config $config
    ) {
        $this->documentService = $documentService;
        $this->siteResolver = $siteResolver;
        $this->requestHelper = $requestHelper;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName(string $name)
    {
        if (preg_match('/^data_object_(\d+)_(.*)$/', $name, $match)) {
            $slug = DataObject\Data\UrlSlug::resolveSlug($match[2]);
            if ($slug && $slug->getObjectId() == $match[1]) {
                /** @var DataObject\Concrete $object * */
                $object = DataObject::getById($match[1]);
                if ($object instanceof DataObject\Concrete && $object->isPublished()) {
                    return $this->buildRouteForFromSlug($slug, $object);
                }
            }
        }

        throw new RouteNotFoundException(sprintf("Route for name '%s' was not found", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(RouteCollection $collection, DynamicRequestContext $context)
    {
        $slug = null;
        $site = $this->siteResolver->getSite($context->getRequest());
        $slug = DataObject\Data\UrlSlug::resolveSlug($context->getOriginalPath(), $site ? $site->getId() : 0);
        if ($slug) {
            $object = DataObject::getById($slug->getObjectId());
            if ($object instanceof DataObject\Concrete && $object->isPublished()) {
                $route = $this->buildRouteForFromSlug($slug, $object);
                $collection->add($route->getRouteKey(), $route);
            }
        }
    }

    /**
     * @param DataObject\Data\UrlSlug $slug
     * @param DataObject\Concrete $object
     *
     * @return DataObjectRoute
     *
     * @throws \Exception
     */
    private function buildRouteForFromSlug(DataObject\Data\UrlSlug $slug, DataObject\Concrete $object): DataObjectRoute
    {
        $route = new DataObjectRoute($slug->getSlug());
        $route->setOption('utf8', true);
        $route->setObject($object);
        $route->setSlug($slug);
        $route->setDefault('_controller', $slug->getAction());
        $route->setDefault('object', $object);
        $route->setDefault('urlSlug', $slug);

        $route->setDefault(
            ElementListener::FORCE_ALLOW_PROCESSING_UNPUBLISHED_ELEMENTS,
            $this->config['objects']['allow_processing_unpublished_elements_for_url_slug']
        );

        return $route;
    }
}
