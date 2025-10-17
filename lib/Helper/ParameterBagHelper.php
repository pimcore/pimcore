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

namespace Pimcore\Helper;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Helper class for safely extracting typed values from ParameterBag instances.
 *
 * This helper addresses Symfony 7 breaking changes where ParameterBag::getInt()
 * and ParameterBag::getBool() now throw UnexpectedValueException instead of
 * returning fallback values. This helper uses the filter() method internally
 * with FILTER_NULL_ON_FAILURE to maintain backward compatibility.
 *
 * @internal
 */
class ParameterBagHelper
{
    /**
     * Safely retrieves an integer value from a ParameterBag.
     *
     * Uses FILTER_VALIDATE_INT with FILTER_NULL_ON_FAILURE to avoid exceptions.
     * Returns the default value if the key doesn't exist or contains an invalid value.
     *
     * Examples:
     * // Basic usage
     * $id = ParameterBagHelper::getInt($request->query, 'id');
     *
     * // With default value
     * $limit = ParameterBagHelper::getInt($request->request, 'limit', 50);
     *
     * // Multiple sources with fallback
     * $id = ParameterBagHelper::getInt($request->attributes, 'id')
     *     ?: ParameterBagHelper::getInt($request->query, 'id');
     *
     */
    public static function getInt(ParameterBag $bag, string $key, int $default = 0): int
    {
        return $bag->filter($key, $default, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Safely retrieves a boolean value from a ParameterBag.
     *
     * Uses FILTER_VALIDATE_BOOLEAN with FILTER_NULL_ON_FAILURE to avoid exceptions.
     * Returns the default value if the key doesn't exist or contains an invalid value.
     *
     * Accepted boolean values:
     * - true: "1", "true", "on", "yes", 1, true
     * - false: "0", "false", "off", "no", 0, false, ""
     *
     * Examples:
     * // Basic usage
     * $active = ParameterBagHelper::getBool($request->query, 'active');
     *
     * // With default value
     * $enabled = ParameterBagHelper::getBool($request->request, 'enabled', true);
     *
     * // In conditionals
     * if (ParameterBagHelper::getBool($request->query, 'preview')) {
     *     // Show preview
     * }
     */
    public static function getBool(ParameterBag $bag, string $key, bool $default = false): bool
    {
        return $bag->filter($key, $default, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
