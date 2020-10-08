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

use Pimcore\DependencyInjection\ConfigMerger;

/**
 * @deprecated will be removed in Pimcore 7
 */
class ArrayUtils
{
    /**
     * @deprecated Use the ConfigMerger instead
     *
     * @param array $first
     * @param array $second
     *
     * @return array
     */
    public static function arrayMergeRecursiveDistinct(array $first, array $second)
    {
        $merger = new ConfigMerger();

        return $merger->merge($first, $second);
    }
}
