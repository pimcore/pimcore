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
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Layout;

use Pimcore\Model;

class Iframe extends Model\DataObject\ClassDefinition\Layout
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'iframe';

    /** @var string */
    public $iframeUrl;

    /** @var string */
    public $renderingData;

    /**
     * @return string
     */
    public function getIframeUrl(): string
    {
        return $this->iframeUrl;
    }

    /**
     * @param string $iframeUrl
     */
    public function setIframeUrl(string $iframeUrl): void
    {
        $this->iframeUrl = $iframeUrl;
    }

    /**
     * @return string
     */
    public function getRenderingData(): string
    {
        return $this->renderingData;
    }

    /**
     * @param string $renderingData
     */
    public function setRenderingData(string $renderingData): void
    {
        $this->renderingData = $renderingData;
    }

    /**
     * Override point for Enriching the layout definition before the layout is returned to the admin interface.
     *
     * @param Model\DataObject\Concrete|null $object
     * @param array $context additional contextual data
     *
     * @return self
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $this->width = $this->getWidth() ? $this->getWidth() : 500;
        $this->height = $this->getHeight() ? $this->getHeight() : 500;

        return $this;
    }
}
