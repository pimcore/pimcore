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

namespace Pimcore\Bundle\PimcoreLegacyBundle\Routing;

use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class FallbackRouter implements RouterInterface, VersatileGeneratorInterface
{
    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var UrlMatcherInterface
     */
    protected $matcher;

    /**
     * @var RouteCollection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $routeDefaults = [
        '_controller' => 'Pimcore\Bundle\PimcoreLegacyBundle:Fallback:fallback'
    ];

    /**
     * @param RequestContext $context
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getRouteDefaults()
    {
        return $this->routeDefaults;
    }

    /**
     * @param array $routeDefaults
     */
    public function setRouteDefaults(array $routeDefaults)
    {
        $this->routeDefaults = $routeDefaults;
    }

    /**
     * @return UrlMatcherInterface
     */
    public function getMatcher()
    {
        if (null === $this->matcher) {
            $this->matcher = new UrlMatcher($this->getRouteCollection(), $this->context);
        }

        return $this->matcher;
    }

    /**
     * @return Route
     */
    protected function buildFallbackRoute()
    {
        $route = new Route('/{path}');
        $route->setDefaults($this->getRouteDefaults());
        $route->setRequirement('path', '.*');

        return $route;
    }

    /**
     * @inheritDoc
     */
    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = new RouteCollection();
            $this->collection->add(
                'pimcore_legacy_fallback',
                $this->buildFallbackRoute()
            );
        }

        return $this->collection;
    }

    /**
     * @inheritDoc
     */
    public function match($pathinfo)
    {
        return $this->getMatcher()->match($pathinfo);
    }

    /**
     * @inheritDoc
     */
    public function supports($name)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRouteDebugMessage($name, array $parameters = [])
    {
        return $name;
    }

    /**
     * @inheritDoc
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        throw new RouteNotFoundException('Legacy route generation is not supported');
    }
}
