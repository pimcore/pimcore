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

namespace Pimcore\Model\DataObject\ClassDefinition\Layout;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use Pimcore\Model\DataObject\Concrete;

class Iframe extends Model\DataObject\ClassDefinition\Layout implements LayoutDefinitionEnrichmentInterface
{
    /**
     * Static type of this element
     *
     * @internal
     *
     */
    public string $fieldtype = 'iframe';

    /**
     * @internal
     *
     */
    public string $iframeUrl;

    /**
     * @internal
     *
     */
    public string $renderingData;

    public function getIframeUrl(): string
    {
        return $this->iframeUrl;
    }

    public function setIframeUrl(string $iframeUrl): void
    {
        $this->iframeUrl = $iframeUrl;
    }

    public function getRenderingData(): string
    {
        return $this->renderingData;
    }

    public function setRenderingData(string $renderingData): void
    {
        $this->renderingData = $renderingData;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $this->width = $this->getWidth() ? $this->getWidth() : 500;
        $this->height = $this->getHeight() ? $this->getHeight() : 500;

        return $this;
    }
}
