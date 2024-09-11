<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
     */
    public int $labelWidth = 100;

    /**
     * @internal
     *
     */
    public string $labelAlign = 'left';

    /**
     * @return $this
     */
    public function setLabelWidth(int $labelWidth): static
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    public function getLabelWidth(): int
    {
        return $this->labelWidth;
    }

    /**
     * @return $this
     */
    public function setLabelAlign(string $labelAlign): static
    {
        if ($labelAlign) {
            $this->labelAlign = $labelAlign;
        }

        return $this;
    }

    public function getLabelAlign(): string
    {
        return $this->labelAlign;
    }
}
