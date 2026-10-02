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

namespace Pimcore\Telemetry;

use Psr\Log\LoggerInterface;
use function get_debug_type;
use function is_array;
use function is_scalar;
use function preg_match;

/**
 * Framework-level guard that makes the "content-never, behavior-only" contract a property of
 * the telemetry framework rather than a per-caller promise.
 *
 * It does two things, both at the single {@see Telemetry::capture()}/{@see Telemetry::groupIdentify()}
 * seam: it validates event names against the taxonomy convention (`domain.object_action`), and it
 * drops any non-scalar property value so an accidental array/object payload - which could carry
 * customer content - can never leave the instance. Violations are logged, never thrown: telemetry
 * must never disrupt the host process.
 *
 * @internal
 */
final class EventSanitizer
{
    /**
     * Lowercase, dot-separated segments, at least two of them - e.g. `object.opened`,
     * `importer.run_started`. Deliberately strict so the dataset stays queryable and cheap.
     */
    private const EVENT_NAME_PATTERN = '/^[a-z0-9]+(?:\.[a-z0-9_]+)+$/';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isValidEventName(string $event): bool
    {
        return preg_match(self::EVENT_NAME_PATTERN, $event) === 1;
    }

    /**
     * Keep scalar/null values and arrays whose leaves are all scalar (e.g. the list of installed
     * bundle names); drop and log objects, resources, and closures, which can stringify to
     * arbitrary customer content. A guard cannot tell a safe string from a content-bearing one -
     * that stays the caller's responsibility, enforced by the taxonomy - but it can keep live
     * objects out of the payload entirely.
     *
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>
     */
    public function sanitizeProperties(array $properties, string $context): array
    {
        $safe = [];

        foreach ($properties as $key => $value) {
            if ($this->isSafeValue($value)) {
                $safe[$key] = $value;

                continue;
            }

            $this->logger->warning('Dropped unsafe telemetry property before send', [
                'context' => $context,
                'property' => $key,
                'type' => get_debug_type($value),
            ]);
        }

        return $safe;
    }

    /**
     * Safe = scalar, null, or an array whose values are all safe (recursively). Objects,
     * resources, and closures are rejected.
     */
    private function isSafeValue(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isSafeValue($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
