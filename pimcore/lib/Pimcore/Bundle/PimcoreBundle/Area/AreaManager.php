<?php

namespace Pimcore\Bundle\PimcoreBundle\Area;

use Pimcore\Bundle\PimcoreBundle\Area\Exception\ConfigurationException;

class AreaManager
{
    /**
     * @var AreaInterface[]
     */
    protected $areas = [];

    /**
     * @param AreaInterface $area
     */
    public function register(AreaInterface $area)
    {
        if (array_key_exists($area->getId(), $this->areas)) {
            throw new ConfigurationException(sprintf('Area %s is already registered', $area->getId()));
        }

        $this->areas[$area->getId()] = $area;
    }

    /**
     * @param string $id
     *
     * @return AreaInterface
     */
    public function get($id)
    {
        if (!isset($this->areas[$id])) {
            throw new ConfigurationException(sprintf('Area %s is not registered', $id));
        }

        return $this->areas[$id];
    }
}
