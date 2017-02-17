<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouteReference implements RouteReferenceInterface
{
    /**
     * @var string
     */
    protected $route;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var int
     */
    protected $type;

    /**
     * @param string $route
     * @param array $parameters
     * @param int $type
     */
    public function __construct($route, array $parameters = [], $type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $this->route      = $route;
        $this->parameters = $parameters;
        $this->type       = $type;
    }

    /**
     * @inheritDoc
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @inheritDoc
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->type;
    }
}
