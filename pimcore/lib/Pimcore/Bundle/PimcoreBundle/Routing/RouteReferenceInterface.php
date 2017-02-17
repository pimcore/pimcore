<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface RouteReferenceInterface
{
    /**
     * Get route name
     *
     * @return string
     */
    public function getRoute();

    /**
     * Get parameters to use when generating the route
     *
     * @return array
     */
    public function getParameters();

    /**
     * Get route type - directly passed to URL generator
     *
     * @see UrlGeneratorInterface
     *
     * @return int
     */
    public function getType();
}
