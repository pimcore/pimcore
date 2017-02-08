<?php

namespace Pimcore\Document\Area;

use Pimcore\Document\Area\Exception\ConfigurationException;

class AreabrickManager implements AreabrickManagerInterface
{
    /**
     * @var AreabrickInterface[]
     */
    protected $bricks = [];

    /**
     * @param AreabrickInterface $brick
     */
    public function register(AreabrickInterface $brick)
    {
        if (array_key_exists($brick->getId(), $this->bricks)) {
            throw new ConfigurationException(sprintf('Areabrick %s is already registered', $brick->getId()));
        }

        $this->bricks[$brick->getId()] = $brick;
    }

    /**
     * @param string $id
     *
     * @return AreabrickInterface
     */
    public function get($id)
    {
        if (!isset($this->bricks[$id])) {
            throw new ConfigurationException(sprintf('Areabrick %s is not registered', $id));
        }

        return $this->bricks[$id];
    }
}
