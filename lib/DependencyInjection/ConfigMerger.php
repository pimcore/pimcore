<?php

declare(strict_types=1);

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

namespace Pimcore\DependencyInjection;

/**
 * @deprecated Will be removed in Pimcore 7
 */
class ConfigMerger
{
    /**
     * Recursively merges arrays.
     *
     * Merge two arrays as array_merge_recursive does, but instead of converting values to arrays when keys are the
     * same, it replaces the value from the first array with the value from the second one. Array values in the first
     * array are never overwritten with scalars from the second (e.g. an array can't be overwritten with null).
     *
     * IMPORTANT: never change this behaviour as it is needed to properly handle security config merging!
     *
     * @see https://github.com/oro-subtree/PhpUtils/blob/master/ArrayUtil.php
     *
     * @param array $first
     * @param array $second
     *
     * @return array
     */
    public function merge(array $first, array $second): array
    {
        foreach ($second as $idx => $value) {
            if (is_int($idx)) {
                $first[] = $value;
            } else {
                if (!array_key_exists($idx, $first)) {
                    $first[$idx] = $value;
                } elseif (is_array($value) && is_array($first[$idx])) {
                    $first[$idx] = $this->merge($first[$idx], $value);
                } elseif (!is_array($first[$idx])) {
                    $first[$idx] = $value;
                }
            }
        }

        return $first;
    }
}
