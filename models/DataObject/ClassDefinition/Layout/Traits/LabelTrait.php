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
