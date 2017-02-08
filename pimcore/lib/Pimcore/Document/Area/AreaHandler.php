<?php

namespace Pimcore\Document\Area;

use Pimcore\Document\Area\Exception\NotFoundException;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;

class AreaHandler implements AreaHandlerInterface
{
    /**
     * @var AreaHandlerInterface[]
     */
    protected $strategies = [];

    /**
     * Register a handler strategy
     *
     * @param AreaHandlerInterface $strategy
     * @return $this
     */
    public function addStrategy(AreaHandlerInterface $strategy)
    {
        $this->strategies[] = $strategy;
    }

    /**
     * Get the matching strategy for a Tag
     *
     * @param Tag|Tag\Area|Tag\Areablock $tag
     * @return AreaHandlerInterface
     */
    public function getStrategy(Tag $tag)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($tag)) {
                return $strategy;
            }
        }

        throw new NotFoundException(sprintf(
            'No handler strategy found for tag %s with view type %s',
            $tag->getName(),
            get_class($tag->getView())
        ));
    }

    /**
     * Determine if handler strategy supports the tag
     *
     * @param Tag|Tag\Area|Tag\Areablock $tag
     * @return bool
     */
    public function supports(Tag $tag)
    {
        try {
            $this->getStrategy($tag);

            return true;
        } catch (NotFoundException $e) {
            // noop
        }

        return false;
    }

    /**
     * Build tag options
     *
     * @param Tag\Areablock $tag
     * @param array $options
     *
     * @return array
     */
    public function getAvailableAreas(Tag\Areablock $tag, array $options)
    {
        $strategy = $this->getStrategy($tag);

        return $strategy->getAvailableAreas($tag, $options);
    }

    /**
     * Render the area frontend
     *
     * @param Info $info
     * @param array $params
     */
    public function renderFrontend(Info $info, array $params)
    {
        $strategy = $this->getStrategy($info->getTag());

        return $strategy->renderFrontend($info, $params);
    }
}
