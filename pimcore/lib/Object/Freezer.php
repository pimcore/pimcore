<?php
/**
 * Object_Freezer
 *
 * Copyright (c) 2008-2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */

require_once 'Object/Freezer/HashGenerator/NonRecursiveSHA1.php';
require_once 'Object/Freezer/IdGenerator/UUID.php';
require_once 'Object/Freezer/Util.php';

/**
 * This class provides the low-level functionality required to store ("freeze")
 * PHP objects to and retrieve ("thaw") PHP objects from an object store.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer
{
    /**
     * @var boolean
     */
    protected $autoload = TRUE;

    /**
     * @var array
     */
    protected $blacklist = array();

    /**
     * @var Object_Freezer_IdGenerator
     */
    protected $idGenerator;

    /**
     * @var Object_Freezer_HashGenerator
     */
    protected $hashGenerator;

    /**
     * Constructor.
     *
     * @param  Object_Freezer_IdGenerator   $idGenerator
     * @param  Object_Freezer_HashGenerator $hashGenerator
     * @param  array                        $blacklist
     * @param  boolean                      $useAutoload
     * @throws InvalidArgumentException
     */
    public function __construct(Object_Freezer_IdGenerator $idGenerator = NULL, Object_Freezer_HashGenerator $hashGenerator = NULL, array $blacklist = array(), $useAutoload = TRUE)
    {
        // Use Object_Freezer_IdGenerator_UUID by default.
        if ($idGenerator === NULL) {
            $idGenerator = new Object_Freezer_IdGenerator_UUID;
        }

        // Use Object_Freezer_HashGenerator_NonRecursiveSHA1 by default.
        if ($hashGenerator === NULL) {
            $hashGenerator = new Object_Freezer_HashGenerator_NonRecursiveSHA1(
              $idGenerator
            );
        }

        $this->setIdGenerator($idGenerator);
        $this->setHashGenerator($hashGenerator);
        $this->setBlacklist($blacklist);
        $this->setUseAutoload($useAutoload);
    }

    /**
     * Freezes an object.
     *
     * If the object has not been frozen before, the attribute
     * __php_object_freezer_uuid will be added to it.
     *
     * In the example below, we freeze an object of class A. As this object
     * aggregates an object of class B, the object freezer has to freeze two
     * objects in total.
     *
     * <code>
     * <?php
     * require_once 'Object/Freezer.php';
     *
     * class A
     * {
     *     protected $b;
     *
     *     public function __construct()
     *     {
     *         $this->b = new B;
     *     }
     * }
     *
     * class B
     * {
     *     protected $foo = 'bar';
     * }
     *
     * $freezer = new Object_Freezer;
     * var_dump($freezer->freeze(new A));
     * ?>
     * </code>
     *
     * Below is the output of the code example above.
     *
     * <code>
     * array(2) {
     *   ["root"]=>
     *   string(36) "32246c35-f47b-4fbc-a2ad-ed14e520865e"
     *   ["objects"]=>
     *   array(2) {
     *     ["32246c35-f47b-4fbc-a2ad-ed14e520865e"]=>
     *     array(3) {
     *       ["className"]=>
     *       string(1) "A"
     *       ["isDirty"]=>
     *       bool(true)
     *       ["state"]=>
     *       array(2) {
     *         ["b"]=>
     *         string(57)
     *         "__php_object_freezer_3cd682bf-8eba-4fec-90e2-ebe98aa07ab7"
     *         ["__php_object_freezer_hash"]=>
     *         string(40) "8b80da9c38c0c41c829cbbefbca9b18aa67ff607"
     *       }
     *     }
     *     ["3cd682bf-8eba-4fec-90e2-ebe98aa07ab7"]=>
     *     array(3) {
     *       ["className"]=>
     *       string(1) "B"
     *       ["isDirty"]=>
     *       bool(true)
     *       ["state"]=>
     *       array(2) {
     *         ["foo"]=>
     *         string(3) "bar"
     *         ["__php_object_freezer_hash"]=>
     *         string(40) "e04e935f09f2d526258d8a16613c5bce31e84e87"
     *       }
     *     }
     *   }
     * }
     * </code>
     *
     * The reference to the object of class B that the object of class A had
     * before it was frozen has been replaced with the UUID of the frozen
     * object of class B
     * (__php_object_freezer_3cd682bf-8eba-4fec-90e2-ebe98aa07ab7).
     *
     * The result array's "root" element contains the UUID for the now frozen
     * object of class A (32246c35-f47b-4fbc-a2ad-ed14e520865e).
     *
     * @param  object  $object  The object that is to be frozen.
     * @param  array   $objects Only used internally.
     * @return array            The frozen object(s).
     * @throws InvalidArgumentException
     */
    public function freeze($object, array &$objects = array())
    {
        // Bail out if a non-object was passed.
        if (!is_object($object)) {
            throw Object_Freezer_Util::getInvalidArgumentException(1, 'object');
        }

        // The object has not been frozen before, generate a new UUID and
        // store it in the "special" __php_object_freezer_uuid attribute.
        if (!isset($object->__php_object_freezer_uuid)) {
            $object->__php_object_freezer_uuid = $this->idGenerator->getId();
        }

        $isDirty = $this->isDirty($object, TRUE);
        $uuid    = $object->__php_object_freezer_uuid;

        if (!isset($objects[$uuid])) {
            $objects[$uuid] = array(
              'className' => get_class($object),
              'isDirty'   => $isDirty,
              'state'     => array()
            );

            // Iterate over the attributes of the object.
            foreach (Object_Freezer_Util::readAttributes($object) as $k => $v) {
                if ($k !== '__php_object_freezer_uuid') {
                    if (is_array($v)) {
                        $this->freezeArray($v, $objects);
                    }

                    else if (is_object($v) &&
                             !in_array(get_class($object), $this->blacklist)) {
                        // Freeze the aggregated object.
                        $this->freeze($v, $objects);

                        // Replace $v with the aggregated object's UUID.
                        $v = '__php_object_freezer_' .
                             $v->__php_object_freezer_uuid;
                    }

                    else if (is_resource($v)) {
                        $v = NULL;
                    }

                    // Store the attribute in the object's state array.
                    $objects[$uuid]['state'][$k] = $v;
                }
            }
        }

        return array('root' => $uuid, 'objects' => $objects);
    }

    /**
     * Freezes an array.
     *
     * @param array $array   The array that is to be frozen.
     * @param array $objects Only used internally.
     */
    protected function freezeArray(array &$array, array &$objects)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->freezeArray($value, $objects);
            }

            else if (is_object($value)) {
                $tmp   = $this->freeze($value, $objects);
                $value = '__php_object_freezer_' . $tmp['root'];
                unset($tmp);
            }
        }
    }

    /**
     * Thaws an object.
     *
     * <code>
     * <?php
     * require_once 'Object/Freezer.php';
     *
     * require_once 'A.php';
     * require_once 'B.php';
     *
     * $freezer = new Object_Freezer;
     *
     * var_dump(
     *   $freezer->thaw(
     *     array(
     *       'root'    => '32246c35-f47b-4fbc-a2ad-ed14e520865e',
     *       'objects' => array(
     *         '32246c35-f47b-4fbc-a2ad-ed14e520865e' => array(
     *           'className' => 'A',
     *           'isDirty'   => FALSE,
     *           'state'     => array(
     *             'b' =>
     *             '__php_object_freezer_3cd682bf-8eba-4fec-90e2-ebe98aa07ab7',
     *           ),
     *         ),
     *         '3cd682bf-8eba-4fec-90e2-ebe98aa07ab7' => array(
     *           'className' => 'B',
     *           'isDirty'   => FALSE,
     *           'state'     => array(
     *             'foo' => 'bar',
     *           )
     *         )
     *       )
     *     )
     *   )
     * );
     * ?>
     * </code>
     *
     * Below is the output of the code example above.
     *
     * <code>
     * object(A)#3 (2) {
     *   ["b":protected]=>
     *   object(B)#5 (2) {
     *     ["foo":protected]=>
     *     string(3) "bar"
     *     ["__php_object_freezer_uuid"]=>
     *     string(36) "3cd682bf-8eba-4fec-90e2-ebe98aa07ab7"
     *   }
     *   ["__php_object_freezer_uuid"]=>
     *   string(36) "32246c35-f47b-4fbc-a2ad-ed14e520865e"
     * }
     * </code>
     *
     * @param  array   $frozenObject The frozen object that should be thawed.
     * @param  string  $root         The UUID of the object that should be
     *                               treated as the root object when multiple
     *                               objects are present in $frozenObject.
     * @param  array   $objects      Only used internally.
     * @return object                The thawed object.
     * @throws RuntimeException
     */
    public function thaw(array $frozenObject, $root = NULL, array &$objects = array())
    {
        // Bail out if one of the required classes cannot be found.
        foreach ($frozenObject['objects'] as $object) {
            if (!class_exists($object['className'], $this->useAutoload)) {
                throw new RuntimeException(
                  sprintf(
                    'Class "%s" could not be found.', $object['className']
                  )
                );
            }
        }

        // By default, we thaw the root object and (recursively)
        // its aggregated objects.
        if ($root === NULL) {
            $root = $frozenObject['root'];
        }

        // Thaw object (if it has not been thawed before).
        if (!isset($objects[$root])) {
            $className = $frozenObject['objects'][$root]['className'];
            $state     = $frozenObject['objects'][$root]['state'];

            // Use a trick to create a new object of a class
            // without invoking its constructor.
            $objects[$root] = unserialize(
              sprintf('O:%d:"%s":0:{}', strlen($className), $className)
            );

            // Handle aggregated objects.
            $this->thawArray($state, $frozenObject, $objects);

            $reflector = new ReflectionObject($objects[$root]);

            foreach ($state as $name => $value) {
                if (strpos($name, '__php_object_freezer') !== 0) {
                    $attribute = $reflector->getProperty($name);
                    $attribute->setAccessible(TRUE);
                    $attribute->setValue($objects[$root], $value);
                }
            }

            // Store UUID.
            $objects[$root]->__php_object_freezer_uuid = $root;

            // Store hash.
            if (isset($state['__php_object_freezer_hash'])) {
                $objects[$root]->__php_object_freezer_hash =
                $state['__php_object_freezer_hash'];
            }
        }

        return $objects[$root];
    }

    /**
     * Thaws an array.
     *
     * @param  array   $array        The array that is to be thawed.
     * @param  array   $frozenObject The frozen object structure from which to
     *                               thaw.
     * @param  array   $objects      Only used internally.
     */
    protected function thawArray(array &$array, array $frozenObject, array &$objects)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->thawArray($value, $frozenObject, $objects);
            }

            else if (is_string($value) &&
                     strpos($value, '__php_object_freezer') === 0) {
                $aggregatedObjectId = str_replace(
                  '__php_object_freezer_', '', $value
                );

                if (isset($frozenObject['objects'][$aggregatedObjectId])) {
                    $value = $this->thaw(
                      $frozenObject, $aggregatedObjectId, $objects
                    );
                }
            }
        }
    }

    /**
     * Returns the Object_Freezer_IdGenerator implementation used
     * to generate object identifiers.
     *
     * @return Object_Freezer_IdGenerator
     */
    public function getIdGenerator()
    {
        return $this->idGenerator;
    }

    /**
     * Sets the Object_Freezer_IdGenerator implementation used
     * to generate object identifiers.
     *
     * @param Object_Freezer_IdGenerator $idGenerator
     */
    public function setIdGenerator(Object_Freezer_IdGenerator $idGenerator)
    {
        $this->idGenerator = $idGenerator;
    }

    /**
     * Returns the Object_Freezer_HashGenerator implementation used
     * to generate hash objects.
     *
     * @return Object_Freezer_HashGenerator
     */
    public function getHashGenerator()
    {
        return $this->hashGenerator;
    }

    /**
     * Sets the Object_Freezer_HashGenerator implementation used
     * to generate hash objects.
     *
     * @param Object_Freezer_IdGenerator $idGenerator
     */
    public function setHashGenerator(Object_Freezer_HashGenerator $hashGenerator)
    {
        $this->hashGenerator = $hashGenerator;
    }

    /**
     * Returns the blacklist of class names for which aggregates objects are
     * not frozen.
     *
     * @return array
     */
    public function getBlacklist()
    {
        return $this->blacklist;
    }

    /**
     * Sets the blacklist of class names for which aggregates objects are
     * not frozen.
     *
     * @param  array $blacklist
     * @throws InvalidArgumentException
     */
    public function setBlacklist(array $blacklist)
    {
        $this->blacklist = $blacklist;
    }

    /**
     * Returns the flag that controls whether or not __autoload()
     * should be invoked.
     *
     * @return boolean
     */
    public function getUseAutoload()
    {
        return $this->useAutoload;
    }

    /**
     * Sets the flag that controls whether or not __autoload()
     * should be invoked.
     *
     * @param  boolean $flag
     * @throws InvalidArgumentException
     */
    public function setUseAutoload($flag)
    {
        // Bail out if a non-boolean was passed.
        if (!is_bool($flag)) {
            throw Object_Freezer_Util::getInvalidArgumentException(
              1, 'boolean'
            );
        }

        $this->useAutoload = $flag;
    }

    /**
     * Checks whether an object is dirty, ie. if its SHA1 hash is still valid.
     *
     * Returns TRUE when the object's __php_object_freezer_hash attribute is no
     * longer valid or does not exist.
     * Returns FALSE when the object's __php_object_freezer_hash attribute is
     * still valid.
     *
     * @param  object  $object The object that is to be checked.
     * @param  boolean $rehash Whether or not to rehash dirty objects.
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function isDirty($object, $rehash = FALSE)
    {
        // Bail out if a non-object was passed.
        if (!is_object($object)) {
            throw Object_Freezer_Util::getInvalidArgumentException(1, 'object');
        }

        // Bail out if a non-boolean was passed.
        if (!is_bool($rehash)) {
            throw Object_Freezer_Util::getInvalidArgumentException(
              2, 'boolean'
            );
        }

        $isDirty = TRUE;
        $hash    = $this->hashGenerator->getHash($object);

        if (isset($object->__php_object_freezer_hash) &&
            $object->__php_object_freezer_hash == $hash) {
            $isDirty = FALSE;
        }

        if ($isDirty && $rehash) {
            $object->__php_object_freezer_hash = $hash;
        }

        return $isDirty;
    }
}
