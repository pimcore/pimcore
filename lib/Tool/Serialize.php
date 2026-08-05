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

namespace Pimcore\Tool;

use Pimcore;
use Pimcore\Serializer\Serializer;
use Throwable;

final class Serialize
{
    protected static array $loopFilterProcessedObjects = [];

    /**
     * Ensures the "missing $allowedClasses argument" deprecation is emitted at most once per
     * process, so callers that unserialize in a loop (e.g. a listing of many objects) cannot
     * flood the logs with the identical notice.
     */
    private static bool $unserializeWithoutAllowedClassesDeprecationTriggered = false;

    public static function serialize(mixed $data): string
    {
        return serialize($data);
    }

    /**
     * Pass an array of class names to allow only those classes during deserialization, `false` to
     * forbid object deserialization entirely (the safe choice for scalar/array data), or `true` to
     * allow any class when reconstructing a trusted, non-attacker-writable stored object graph.
     *
     * Omitting the argument is deprecated: it currently defaults to the permissive `true` for
     * backward compatibility, but the default will change to `false` in Pimcore 2027.1. Every caller
     * should pass an explicit value before then.
     *
     * @param array<int, class-string>|bool $allowedClasses
     */
    public static function unserialize(?string $data = null, array|bool $allowedClasses = true): mixed
    {
        if ($data === null || $data === '') {
            return $data;
        }

        if (func_num_args() < 2 && !self::$unserializeWithoutAllowedClassesDeprecationTriggered) {
            self::$unserializeWithoutAllowedClassesDeprecationTriggered = true;
            trigger_deprecation(
                'pimcore/pimcore',
                '12.3',
                'Calling %s() without the $allowedClasses argument is deprecated. Pass an explicit '
                . 'value: the default will change from true to false (object deserialization disabled) '
                . 'in Pimcore 2027.1.',
                __METHOD__
            );
        }

        return unserialize($data, [
            'allowed_classes' => $allowedClasses,
        ]);
    }

    /**
     * @internal
     *
     * Shortcut to access the admin serializer
     *
     */
    public static function getAdminSerializer(): \Symfony\Component\Serializer\Serializer
    {
        return Pimcore::getContainer()->get('pimcore_admin.serializer');
    }

    public static function getSerializer(): Serializer
    {
        return Pimcore::getContainer()->get('pimcore.serializer');
    }

    public static function toJson(array $data, int $options = 0): string
    {
        return self::getSerializer()->encode($data, 'json', ['json_encode_options' => $options]);
    }

    public static function fromJson(string $json): array
    {
        return self::getSerializer()->decode($json, 'json');
    }

    /**
     * @internal
     *
     * this is a special json encoder that avoids recursion errors
     * especially for pimcore models that contain massive self referencing objects
     *
     *
     */
    public static function removeReferenceLoops(mixed $data): mixed
    {
        self::$loopFilterProcessedObjects = []; // reset

        return self::loopFilterCycles($data);
    }

    protected static function loopFilterCycles(mixed $element): mixed
    {
        if (is_array($element)) {
            foreach ($element as &$value) {
                $value = self::loopFilterCycles($value);
            }
        } elseif (is_object($element)) {
            try {
                $clone = clone $element; // do not modify the original object
            } catch (Throwable $e) {
                return sprintf('"* NON-CLONEABLE (%s): %s *"', get_class($element), $e->getMessage());
            }

            if (in_array($element, self::$loopFilterProcessedObjects, true)) {
                return '"* RECURSION (' . get_class($element) . ') *"';
            }

            self::$loopFilterProcessedObjects[] = $element;

            $propCollection = get_object_vars($clone);

            foreach ($propCollection as $name => $propValue) {
                if (!str_starts_with((string) $name, "\0")) {
                    $clone->$name = self::loopFilterCycles($propValue);
                }
            }

            array_splice(self::$loopFilterProcessedObjects, array_search($element, self::$loopFilterProcessedObjects, true), 1);

            return $clone;
        }

        return $element;
    }
}
