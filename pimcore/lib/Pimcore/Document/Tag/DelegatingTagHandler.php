<?php

namespace Pimcore\Document\Tag;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Pimcore\Document\Tag\Exception\NotFoundException;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\View;

class DelegatingTagHandler implements TagHandlerInterface
{
    /**
     * @var TagHandlerInterface[]
     */
    protected $handlers = [];

    /**
     * Register a handler
     *
     * @param TagHandlerInterface $handler
     * @return $this
     */
    public function addHandler(TagHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * Get the matching handler for a view
     *
     * @param ViewModelInterface|View $view
     * @return TagHandlerInterface
     */
    public function getHandlerForView($view)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($view)) {
                return $handler;
            }
        }

        throw new NotFoundException(sprintf(
            'No handler found for view type %s',
            get_class($view)
        ));
    }

    /**
     * Get the matching handler for a Tag
     *
     * @param Tag|Tag\Area|Tag\Areablock $tag
     * @return TagHandlerInterface
     */
    public function getHandlerForTag(Tag $tag)
    {
        try {
            return $this->getHandlerForView($tag->getView());
        } catch (NotFoundException $e) {
            throw new NotFoundException(sprintf(
                'No handler found for tag %s',
                $tag->getName(),
                get_class($tag->getView())
            ), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($view)
    {
        try {
            $this->getHandlerForView($view);

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
        $handler = $this->getHandlerForTag($tag);

        return $handler->getAvailableAreablockAreas($tag, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAreaFrontend(Info $info, array $params)
    {
        $handler = $this->getHandlerForTag($info->getTag());

        return $handler->renderAreaFrontend($info, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAction($view, $controller, $action, $parent = null, array $params = [])
    {
        $handler = $this->getHandlerForView($view);

        return $handler->renderAction($view, $controller, $action, $parent, $params);
    }
}
