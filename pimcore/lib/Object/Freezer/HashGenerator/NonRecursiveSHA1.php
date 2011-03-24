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

require_once 'Object/Freezer/HashGenerator.php';
require_once 'Object/Freezer/Util.php';

/**
 * Interface for generators of object hashes.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Interface available since Release 1.0.0
 */
class Object_Freezer_HashGenerator_NonRecursiveSHA1 implements Object_Freezer_HashGenerator
{
    /**
     * @var Object_Freezer_IdGenerator
     */
    protected $idGenerator;

    /**
     * Constructor.
     *
     * @param  Object_Freezer_IdGenerator $idGenerator
     */
    public function __construct(Object_Freezer_IdGenerator $idGenerator)
    {
        $this->idGenerator = $idGenerator;
    }

    /**
     * Implementation of Object_Freezer_HashGenerator that uses the SHA1
     * hashing function on the attribute values of an object without recursing
     * into aggregated arrays or objects.
     *
     * @param  object $object The object that is to be hashed.
     * @return string
     * @throws InvalidArgumentException
     */
    public function getHash($object)
    {
        // Bail out if a non-object was passed.
        if (!is_object($object)) {
            throw Object_Freezer_Util::getInvalidArgumentException(1, 'object');
        }

        $attributes = Object_Freezer_Util::readAttributes($object);
        ksort($attributes);

        if (isset($attributes['__php_object_freezer_hash'])) {
            unset($attributes['__php_object_freezer_hash']);
        }

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $attributes[$key] = '<array>';
            }

            else if (is_object($value)) {
                if (!isset($value->__php_object_freezer_uuid)) {
                    $value->__php_object_freezer_uuid =
                    $this->idGenerator->getId();
                }

                $attributes[$key] = $value->__php_object_freezer_uuid;
            }

            else if (is_resource($value)) {
                $attributes[$key] = NULL;
            }
        }

        return sha1(get_class($object) . join(':', $attributes));
    }
}
