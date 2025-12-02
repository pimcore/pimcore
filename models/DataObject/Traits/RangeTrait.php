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

use OutOfRangeException;

/**
 * @internal
 */
trait RangeTrait
{
    abstract public function getMinimum(): int | float | null;

    abstract public function getMaximum(): int | float | null;

    public function getRange(int|float $step = 1): array
    {
        /*
         * If $min or $max is null, range will be interpreted this as 0.
         * https://www.php.net/manual/en/function.range.php
         */
        $min = $this->getMinimum() ?? 0;
        $max = $this->getMaximum() ?? 0;

        $delta = $max - $min;

        // range throws when used with $step greater then $delta
        if ($min != $max && abs($step) > abs($delta)) {
            throw new OutOfRangeException('The range must be higher than the given step parameter');
        }

        return range($min, $max, $step);
    }
}
