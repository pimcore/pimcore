<?php

namespace Pimcore\Document\Tag;

use Pimcore\Document\Tag\Exception\NotFoundException;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;

class TagHandler implements TagHandlerInterface
{
    /**
     * @var TagHandlerInterface[]
     */
    protected $strategies = [];

    /**
     * Register a handler strategy
     *
     * @param TagHandlerInterface $strategy
     * @return $this
     */
    public function addStrategy(TagHandlerInterface $strategy)
    {
        $this->strategies[] = $strategy;
    }

    /**
     * Get the matching strategy for a Tag
     *
     * @param Tag|Tag\Area|Tag\Areablock $tag
     * @return TagHandlerInterface
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getAvailableAreablockAreas(Tag\Areablock $tag, array $options)
    {
        $strategy = $this->getStrategy($tag);

        return $strategy->getAvailableAreablockAreas($tag, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAreaFrontend(Info $info, array $params)
    {
        $strategy = $this->getStrategy($info->getTag());

        return $strategy->renderAreaFrontend($info, $params);
    }
}
