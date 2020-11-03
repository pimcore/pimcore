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

class Text extends Model\DataObject\ClassDefinition\Layout
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'text';

    /**
     * @var string
     */
    public $html = '';

    /**
     * @var string
     */
    public $renderingClass;

    /**
     * @var string
     */
    public $renderingData;

    /**
     * @var bool
     */
    public $border = false;

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param string $html
     *
     * @return $this
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return string
     */
    public function getRenderingClass()
    {
        return $this->renderingClass;
    }

    /**
     * @param string $renderingClass
     */
    public function setRenderingClass($renderingClass)
    {
        $this->renderingClass = $renderingClass;
    }

    /**
     * @return string
     */
    public function getRenderingData()
    {
        return $this->renderingData;
    }

    /**
     * @param string $renderingData
     */
    public function setRenderingData($renderingData)
    {
        $this->renderingData = $renderingData;
    }

    /**
     * @return bool
     */
    public function getBorder(): bool
    {
        return $this->border;
    }

    /**
     * @param bool $border
     */
    public function setBorder(bool $border): void
    {
        $this->border = $border;
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
        $renderer = Model\DataObject\ClassDefinition\Helper\DynamicTextResolver::resolveRenderingClass(
            $this->getRenderingClass()
        );

        if ($renderer === null) {
            $renderer = $this->getRenderingClass();
        }

        if (!$renderer instanceof DynamicTextLabelInterface) {
            @trigger_error('Using a text renderer class which does not implement ' . DynamicTextLabelInterface::class.' is deprecated', \E_USER_DEPRECATED);
        }

        if (method_exists($renderer, 'renderLayoutText') && $object) {
            $context['fieldname'] = $this->getName();
            $context['layout'] = $this;
            $result = call_user_func([$renderer, 'renderLayoutText'], $this->renderingData, $object, $context);
            $this->html = $result;
        }

        return $this;
    }
}
