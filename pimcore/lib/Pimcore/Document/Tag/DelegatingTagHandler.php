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
            $view ? get_class($view) : 'null'
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
        $view = $tag->getView();

        try {
            return $this->getHandlerForView($view);
        } catch (NotFoundException $e) {
            throw new NotFoundException(sprintf(
                'No handler found for tag %s and view type %s',
                $tag->getName(),
                $view ? get_class($view) : 'null'
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
