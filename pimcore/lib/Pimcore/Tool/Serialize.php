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

class Serialize
{
    /**
     * @var array
     */
    protected static $loopFilterProcessedObjects = [];

    /**
     * @static
     * @param mixed $data
     * @return string
     */
    public static function serialize($data)
    {
        return serialize($data);
    }

    /**
     * @static
     * @param $data
     * @return mixed
     */
    public static function unserialize($data)
    {
        if (!empty($data) && is_string($data)) {
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * Shortcut to access the admin serializer
     *
     * @return \Symfony\Component\Serializer\Serializer
     */
    public static function getAdminSerializer()
    {
        return \Pimcore::getContainer()->get('pimcore_admin.serializer');
    }

    /**
     * this is a special json encoder that avoids recursion errors
     * especially for pimcore models that contain massive self referencing objects
     * @param $data
     * @return string
     */
    public static function removeReferenceLoops($data)
    {
        self::$loopFilterProcessedObjects = []; // reset
        return self::loopFilterCycles($data);
    }

    /**
     * @param $element
     * @return mixed
     */
    protected static function loopFilterCycles($element)
    {
        if (is_array($element)) {
            foreach ($element as &$value) {
                $value = self::loopFilterCycles($value);
            }
        } elseif (is_object($element)) {
            $clone = clone $element; // do not modify the original object

            if (in_array($element, self::$loopFilterProcessedObjects, true)) {
                return '"* RECURSION (' . get_class($element) . ') *"';
            }

            self::$loopFilterProcessedObjects[] = $element;

            $propCollection = get_object_vars($clone);

            foreach ($propCollection as $name => $propValue) {
                $clone->$name = self::loopFilterCycles($propValue);
            }

            array_splice(self::$loopFilterProcessedObjects, array_search($element, self::$loopFilterProcessedObjects, true), 1);

            return $clone;
        }

        return $element;
    }
}
