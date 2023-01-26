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
trait SimpleComparisonTrait
{
    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }

    protected function isEqualArray(?array $array1, ?array $array2): bool
    {
        $array1 = array_filter(is_array($array1) ? $array1 : []);
        $array2 = array_filter(is_array($array2) ? $array2 : []);

        if (count($array1) != count($array2)) {
            return false;
        }

        return $array1 == $array2;
    }
}
