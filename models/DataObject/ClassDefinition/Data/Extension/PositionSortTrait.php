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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Extension;

/**
 * @internal
 */
trait PositionSortTrait
{
    /**
     * @param array{position: int|string}|string|null $a If of type array must have key `position` with numeric value
     * @param array{position: int|string}|string|null $b If of type array must have key `position` with numeric value
     *
     * @return -1|0|1
     */
    protected function sort(array|string|null $a, array|string|null $b): int
    {
        if (is_array($a) && is_array($b)) {
            return $a['position'] <=> $b['position'];
        }
        if (is_string($a) && is_string($b)) {
            return strcmp($a, $b);
        }

        return 0;
    }
}
