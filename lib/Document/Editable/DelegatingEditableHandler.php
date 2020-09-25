<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Editable;

use Pimcore\Document\Editable\Exception\NotFoundException;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Area\Info;
use Pimcore\Model\Document\Tag;
use Pimcore\Templating\Model\ViewModelInterface;

/**
 * @deprecated will be removed in v7, use EditableHandler directly instead
 */
class DelegatingEditableHandler implements EditableHandlerInterface
{
    /**
     * @var EditableHandlerInterface[]
     */
    protected $handlers = [];

    /**
     * Register a handler
     *
     * @param EditableHandlerInterface $handler
     *
     * @return $this
     */
    public function addHandler(EditableHandlerInterface $handler)
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * Get the matching handler for a view
     *
     * @param ViewModelInterface $view
     *
     * @return EditableHandlerInterface
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
     *
     * @return EditableHandlerInterface
     *
     * @deprecated
     */
    public function getHandlerForTag(Tag $tag)
    {
        return $this->getHandlerForEditable($tag);
    }

    /**
     * Get the matching handler for a Tag
     *
     * @param Editable|Editable\Area|Editable\Areablock $editable
     *
     * @return EditableHandlerInterface
     */
    public function getHandlerForEditable(Editable $editable)
    {
        $view = $editable->getView();

        try {
            return $this->getHandlerForView($view);
        } catch (NotFoundException $e) {
            throw new NotFoundException(sprintf(
                'No handler found for tag %s and view type %s',
                $editable->getName(),
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
     * @inheritDoc
     */
    public function isBrickEnabled(Editable $editable, $brick)
    {
        $handler = $this->getHandlerForEditable($editable);

        return $handler->isBrickEnabled($editable, $brick);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableAreablockAreas(Editable\Areablock $editable, array $options)
    {
        $handler = $this->getHandlerForEditable($editable);

        return $handler->getAvailableAreablockAreas($editable, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAreaFrontend(Info $info)
    {
        $handler = $this->getHandlerForEditable($info->getEditable());

        return $handler->renderAreaFrontend($info);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAction($view, $controller, $action, $parent = null, array $attributes = [], array $query = [], array $options = [])
    {
        $handler = $this->getHandlerForView($view);

        return $handler->renderAction($view, $controller, $action, $parent, $attributes, $query, $options);
    }
}

class_alias(DelegatingEditableHandler::class, 'Pimcore\Document\Tag\DelegatingTagHandler');
