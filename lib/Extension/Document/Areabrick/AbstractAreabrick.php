<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Extension\Document\Areabrick;

use Pimcore\Bundle\CoreBundle\DependencyInjection\ContainerAwareInterface;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ContainerAwareTrait;
use Pimcore\Extension\Document\Areabrick\Exception\ConfigurationException;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Area\Info;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\EditableRenderer;

abstract class AbstractAreabrick implements AreabrickInterface, TemplateAreabrickInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected EditableRenderer $editableRenderer;

    /**
     * Called in AreabrickPass
     *
     */
    public function setEditableRenderer(EditableRenderer $editableRenderer): void
    {
        $this->editableRenderer = $editableRenderer;
    }

    protected ?string $id = null;

    public function setId(string $id): void
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

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->id ? ucfirst($this->id) : '';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getVersion(): string
    {
        return '';
    }

    public function getIcon(): ?string
    {
        return null;
    }

    public function hasTemplate(): bool
    {
        return true;
    }

    public function action(Info $info): ?\Symfony\Component\HttpFoundation\Response
    {
        // noop - implement as needed
        return null;
    }

    public function postRenderAction(Info $info): ?\Symfony\Component\HttpFoundation\Response
    {
        // noop - implement as needed
        return null;
    }

    public function getHtmlTagOpen(Info $info): string
    {
        return '<div class="pimcore_area_' . $info->getId() . ' pimcore_area_content '. $this->getOpenTagCssClass($info) .'">';
    }

    protected function getOpenTagCssClass(Info $info): ?string
    {
        return null;
    }

    public function getHtmlTagClose(Info $info): string
    {
        return '</div>';
    }

    protected function getDocumentEditable(PageSnippet $document, string $type, string $inputName, array $options = []): Editable\EditableInterface
    {
        return $this->editableRenderer->getEditable($document, $type, $inputName, $options);
    }

    public function needsReload(): bool
    {
        return false;
    }
}
