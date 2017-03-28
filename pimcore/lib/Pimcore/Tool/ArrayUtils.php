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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

class ArrayUtils
{
    /**
     *
     * Recursively merge arrays.
     *
     * Merge two arrays as array_merge_recursive do, but instead of converting values to arrays when keys are same
     * replaces value from first array with value from second
     *
     * @see https://github.com/oro-subtree/PhpUtils/blob/master/ArrayUtil.php
     *
     * @param array $first
     * @param array $second
     *
     * @return array
     */
    public static function arrayMergeRecursiveDistinct(array $first, array $second)
    {
        foreach ($second as $idx => $value) {
            if (is_integer($idx)) {
                $first[] = $value;
            } else {
                if (!array_key_exists($idx, $first)) {
                    $first[$idx] = $value;
                } else {
                    if (is_array($value)) {
                        if (is_array($first[$idx])) {
                            $first[$idx] = self::arrayMergeRecursiveDistinct($first[$idx], $value);
                        } else {
                            $first[$idx] = $value;
                        }
                    } else {
                        $first[$idx] = $value;
                    }
                }
            }
        }

        return $first;
    }
}
