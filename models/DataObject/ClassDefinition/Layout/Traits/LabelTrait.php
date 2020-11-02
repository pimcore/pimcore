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

namespace Pimcore\Model\DataObject\ClassDefinition\Layout\Traits;

use Pimcore\Model\DataObject\ClassDefinition\Layout;

trait LabelTrait
{
    /**
     * Width of input field labels
     *
     * @var int
     */
    public $labelWidth = 100;

    /**
     * @var string
     */
    public $labelAlign = 'left';

    /**
     * @param int $labelWidth
     *
     * @return $this
     */
    public function setLabelWidth($labelWidth)
    {
        if (!empty($labelWidth)) {
            $this->labelWidth = (int)$labelWidth;
        }

        return $this;
    }

    /**
     * @return int
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
