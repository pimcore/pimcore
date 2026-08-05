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

namespace Pimcore\Model\DataObject\Traits;

/**
 * @internal
 */
trait DataWidthTrait
{
    /**
     * @internal
     */
    public string|int|null $width = null;

    public function getWidth(): int|string|null
    {
        return $this->width;
    }

    public function setWidth(int|string|null $width): static
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }
}
