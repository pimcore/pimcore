<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

// TODO this stupid name just exists because otherwise there's a StaticRoute wrapping a Staticroute which is even more weird
// if you come up with a better name go for it
class StaticrouteRoute extends Route implements RouteObjectInterface
{
    /**
     * @var \Pimcore\Model\Staticroute
     */
    protected $staticRoute;

    /**
     * @var int|null
     */
    protected $siteId;

    /**
     * @return \Pimcore\Model\Staticroute
     */
    public function getStaticRoute()
    {
        return $this->staticRoute;
    }

    /**
     * @param \Pimcore\Model\Staticroute $staticRoute
     * @return StaticrouteRoute
     */
    public function setStaticRoute($staticRoute)
    {
        $this->staticRoute = $staticRoute;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int|null $siteId
     * @return StaticrouteRoute
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;

        return $this;
    }

    /**
     * Get the content document this route entry stands for. If non-null,
     * the ControllerClassMapper uses it to identify a controller and
     * the content is passed to the controller.
     *
     * If there is no specific content for this url (i.e. its an "application"
     * page), may return null.
     *
     * @return object the document or entity this route entry points to
     */
    public function getContent()
    {
        return $this->staticRoute;
    }

    /**
     * Get the route name.
     *
     * Normal symfony routes do not know their name, the name is only known
     * from the route collection. In the CMF, it is possible to use route
     * documents outside of collections, and thus useful to have routes provide
     * their name.
     *
     * There are no limitations to allowed characters in the name.
     *
     * @return string|null the route name or null to use the default name
     *                     (e.g. from route collection if known)
     */
    public function getRouteKey()
    {
        if ($this->siteId) {
            return $this->staticRoute->getName() . '~~~' . $this->siteId;
        }

        return $this->staticRoute->getId();
    }
}
