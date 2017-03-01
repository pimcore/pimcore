<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing\Staticroute;

use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Provider implements RouteProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        // TODO: Implement getRouteCollectionForRequest() method.
    }

    /**
     * @inheritDoc
     */
    public function getRouteByName($name)
    {
        // TODO: Implement getRouteByName() method.
    }

    /**
     * @inheritDoc
     */
    public function getRoutesByNames($names)
    {
        // TODO: Implement getRoutesByNames() method.
    }
}
