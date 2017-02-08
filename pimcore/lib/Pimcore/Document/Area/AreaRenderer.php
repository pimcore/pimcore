<?php

namespace Pimcore\Document\Area;

use Pimcore\Document\Area\Exception\RuntimeException;
use Pimcore\Model\Document\Tag\Area\Info;

class AreaRenderer
{
    /**
     * @var AreaRenderingStrategyInterface[]
     */
    protected $strategies = [];

    /**
     * Register a rendering strategy
     *
     * @param AreaRenderingStrategyInterface $strategy
     * @return $this
     */
    public function addStrategy(AreaRenderingStrategyInterface $strategy)
    {
        $this->strategies[] = $strategy;
    }

    /**
     * Render the area frontend
     *
     * @param Info $info
     * @param array $params
     */
    public function renderFrontend(Info $info, array $params)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($info)) {
                return $strategy->renderFrontend($info, $params);
            }
        }

        throw new RuntimeException(sprintf(
            'No rendering strategy found for area %s with view type %s',
            $info->getId(),
            get_class($info->getTag()->getView())
        ));
    }
}
