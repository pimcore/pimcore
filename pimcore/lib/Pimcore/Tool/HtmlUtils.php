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

namespace Pimcore\Tool;

class HtmlUtils
{
    /**
     * Builds an attribute string from an array of attributes
     *
     * @param array $attributes
     * @param bool $omitNullValues
     *
     * @return string
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
