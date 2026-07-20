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

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_INT;
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
final class ParameterBagHelper
{
    /**
     * Triggers a deprecation warning for the helper method usage.
     */
    private static function triggerDeprecation(string $method): void
    {
        if (!filter_var($_SERVER['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2] ?? [];
        $callerInfo = isset($caller['class'], $caller['function'])
            ? sprintf('%s::%s()', $caller['class'], $caller['function'])
            : ($caller['function'] ?? 'unknown');

        trigger_deprecation(
            'pimcore/pimcore',
            '12.3',
            'Usage of \Pimcore\Helper::%s is deprecated. Use proper parameter validation instead in %s.',
            $method,
            $callerInfo
        );
    }

    /**
     * Safely retrieves an integer value from a ParameterBag.
     *
     * Uses FILTER_VALIDATE_INT with FILTER_NULL_ON_FAILURE to avoid exceptions.
     * Returns the default value if the key doesn't exist or contains an invalid value.
     *
     * @deprecated since 12.3, will be removed in 13.0. Use proper parameter validation instead.
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
        self::triggerDeprecation(__METHOD__);

        return $bag->filter($key, $default, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Safely retrieves a boolean value from a ParameterBag.
     *
     * Uses FILTER_VALIDATE_BOOLEAN with FILTER_NULL_ON_FAILURE to avoid exceptions.
     * Returns the default value if the key doesn't exist or contains an invalid value.
     *
     * @deprecated since 12.3, will be removed in 13.0. Use proper parameter validation instead.
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
        self::triggerDeprecation(__METHOD__);

        // Return filtered result
        if ($bag->get($key) === null) {
            return $default;
        }

        return $bag->filter($key, null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
