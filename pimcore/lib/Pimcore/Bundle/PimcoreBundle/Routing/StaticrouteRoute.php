<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing;

use Symfony\Component\Routing\Route;

// TODO this stupid name just exists because otherwise there's a StaticRoute wrapping a Staticroute which is even more weird
// if you come up with a better name go for it
class StaticrouteRoute extends Route
{
    /**
     * @var \Pimcore\Model\Staticroute
     */
    protected $staticRoute;

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
}
