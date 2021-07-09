<?php


namespace Pimcore\Event\Admin\Login;

use Symfony\Contracts\EventDispatcher\Event;

class LoginRedirectEvent extends Event
{

    /**
     * @var
     */
    protected $routeName;

    /**
     * @var array
     */
    protected $routeParams;

    /**
     * LoginRedirectEvent constructor.
     * @param $routeName
     * @param array $routeParams
     */
    public function __construct($routeName, array $routeParams)
    {
        $this->routeName = $routeName;
        $this->routeParams = $routeParams;
    }

    /**
     * @return mixed
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param mixed $routeName
     */
    public function setRouteName($routeName): void
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
