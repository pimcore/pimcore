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

namespace Pimcore\Tool;

/**
 * @internal
 */
class HtmlUtils
{
    /**
     * Builds an attribute string from an array of attributes
     *
     *
     */
    public static function assembleAttributeString(array $attributes, bool $omitNullValues = false): string
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            // do not output null values or use an attribute without
            // value depending on parameter
            if (null === $value) {
                if ($omitNullValues) {
                    continue;
                } else {
                    $parts[] = $key;
                }
            } else {
                $parts[] = sprintf('%s="%s"', $key, $value);
            }
        }

        return implode(' ', $parts);
    }
}
