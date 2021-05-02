<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Extension\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\Exception\ConfigurationException;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Area\Info;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\EditableRenderer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractAreabrick implements AreabrickInterface, TemplateAreabrickInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var EditableRenderer
     */
    protected $editableRenderer;

    /**
     * Called in AreabrickPass
     *
     * @param EditableRenderer $editableRenderer
     */
    public function setEditableRenderer(EditableRenderer $editableRenderer)
    {
        $this->editableRenderer = $editableRenderer;
    }

    /**
     * @var string
     */
    protected $id;

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        // make sure ID is only set once
        if (null !== $this->id) {
            throw new ConfigurationException(sprintf(
                'Brick ID is immutable (trying to set ID %s for brick %s)',
                $id,
                $this->id
            ));
        }

        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->id ? ucfirst($this->id) : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTemplate()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function action(Info $info)
    {
        // noop - implement as needed
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function postRenderAction(Info $info)
    {
        // noop - implement as needed
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagOpen(Info $info)
    {
        return '<div class="pimcore_area_' . $info->getId() . ' pimcore_area_content '. $this->getOpenTagCssClass($info) .'">';
    }

    /**
     * @param Info $info
     *
     * @return string|null
     */
    protected function getOpenTagCssClass(Info $info)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagClose(Info $info)
    {
        return '</div>';
    }

    /**
     * @param PageSnippet $document
     * @param string $type
     * @param string $inputName
     * @param string $inputName
     * @param array $options
     *
     * @return Editable\EditableInterface
     */
    protected function getDocumentEditable(PageSnippet $document, $type, $inputName, array $options = [])
    {
        return $this->editableRenderer->getEditable($document, $type, $inputName, $options);
    }

    public function needsReload(): bool
    {
        return false;
    }
}
