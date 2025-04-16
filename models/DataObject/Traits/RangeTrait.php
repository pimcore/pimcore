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

namespace Pimcore\Model\DataObject\Traits;

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

        // Edge case
        if ($min == $max) {
            return [$min];
        }

        // range throws when used with $step greater then $delta
        if (abs($step) > abs($delta)) {
            if ($step > 0) {
                return [$min, $max];
            } else {
                // the native range() function supports negative $step values,
                // so this mimics its behavior.
                return [$max, $min];
            }
        }

        return range($min, $max, $step);
    }

}
