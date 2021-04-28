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

namespace Pimcore\Model\DataObject\ClassDefinition\Layout\Traits;

/**
 * @internal
 */
trait LabelTrait
{
    /**
     * Width of input field labels
     *
     * @internal
     *
     * @var string|int
     */
    public $labelWidth = 100;

    /**
     * @internal
     *
     * @var string
     */
    public $labelAlign = 'left';

    /**
     * @param string|int $labelWidth
     *
     * @return $this
     */
    public function setLabelWidth($labelWidth)
    {
        if (is_numeric($labelWidth)) {
            $labelWidth = (int)$labelWidth;
        }

        $this->labelWidth = $labelWidth;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
    }

    /**
     * @param string $labelAlign
     *
     * @return $this
     */
    public function setLabelAlign($labelAlign)
    {
        if (!empty($labelAlign)) {
            $this->labelAlign = $labelAlign;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelAlign(): string
    {
        return $this->labelAlign;
    }
}
