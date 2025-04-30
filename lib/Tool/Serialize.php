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

    public static function serialize(mixed $data): string
    {
        return serialize($data);
    }

    public static function unserialize(?string $data = null): mixed
    {
        if ($data) {
            $data = unserialize($data);
        }

        return $data;
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
