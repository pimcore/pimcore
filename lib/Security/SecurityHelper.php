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

namespace Pimcore\Security;

/**
 * @internal
 */
class SecurityHelper
{
    public static function convertHtmlSpecialChars(?string $text): ?string
    {
        if (is_string($text)) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
        }

        return null;
    }

    public static function convertHtmlSpecialCharsArrayKeys(array &$array, array $keys): void
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $array[$key] = self::convertHtmlSpecialChars($array[$key]);
            }
        }
    }
}
