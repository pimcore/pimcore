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

use Pimcore;
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
