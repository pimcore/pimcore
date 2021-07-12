<?php


namespace Pimcore\Event\Admin\Login;

use Symfony\Contracts\EventDispatcher\Event;

class LoginRedirectEvent extends Event
{

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var array
     */
    protected $routeParams;

    /**
     * @param string $routeName
     * @param array $routeParams
     */
    public function __construct(string $routeName, array $routeParams = [])
    {
        $this->routeName = $routeName;
        $this->routeParams = $routeParams;
    }

    /**
     * @return string
     */
    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     */
    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    /**
     * @return array
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * @param array $routeParams
     */
    public function setRouteParams(array $routeParams): void
    {
        $this->routeParams = $routeParams;
    }

}
