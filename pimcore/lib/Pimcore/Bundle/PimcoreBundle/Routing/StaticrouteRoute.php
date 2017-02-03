<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing;

use Pimcore\Model\Staticroute;
use Symfony\Component\Routing\Route;

// TODO this stupid name just exists because otherwise there's a StaticRoute wrapping a Staticroute which is even more weird
// if you come up with a better name go for it
class StaticrouteRoute extends Route
{
    /**
     * @var Staticroute
     */
    protected $staticRoute;

    /**
     * @return Staticroute
     */
    public function getStaticRoute()
    {
        return $this->staticRoute;
    }

    /**
     * @param Staticroute $staticRoute
     * @return $this
     */
    public function setStaticRoute($staticRoute)
    {
        $this->staticRoute = $staticRoute;

        return $this;
    }
}
