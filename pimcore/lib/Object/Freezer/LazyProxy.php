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

/**
 * Proxy for a frozen object that is replaced with a thawed object when needed.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer_LazyProxy
{
    /**
     * @var Object_Freezer_Storage
     */
    protected $storage;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var object
     */
    protected $thawedObject;

    /**
     * Constructor.
     *
     * @param Object_Freezer_Storage $storage
     * @param string                 $uuid
     */
    public function __construct(Object_Freezer_Storage $storage, $uuid)
    {
        $this->storage = $storage;
        $this->uuid    = $uuid;
    }

    /**
     * Returns the real object.
     *
     * @return object
     */
    public function getObject()
    {
        if ($this->thawedObject === NULL) {
            $this->thawedObject = $this->storage->fetch($this->uuid);
        }

        return $this->thawedObject;
    }

    /**
     * Delegates the attribute read access to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $object    = $this->replaceProxy(2);
        $attribute = new ReflectionProperty($object, $name);
        $attribute->setAccessible(TRUE);

        return $attribute->getValue($object);
    }

    /**
     * Delegates the attribute write access to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $object    = $this->replaceProxy(2);
        $attribute = new ReflectionProperty($object, $name);
        $attribute->setAccessible(TRUE);

        $attribute->setValue($object, $value);
    }

    /**
     * Delegates the message to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $object    = $this->replaceProxy(3);
        $reflector = new ReflectionMethod($object, $name);

        return $reflector->invokeArgs($object, $arguments);
    }

    /**
     * Tries to replace the lazy proxy object with the real object.
     *
     * @param  integer $offset
     * @return object
     */
    protected function replaceProxy($offset)
    {
        $object = $this->getObject();

        /**
         * 0: LazyProxy::replaceProxy()
         * 1: LazyProxy::__get($name) / LazyProxy::__set($name, $value)
         *    2: Frame that accesses $name
         * 1: LazyProxy::__call($method, $arguments)
         * 2: LazyProxy::$method()
         *    3: Frame that invokes $method
         */
        $trace = debug_backtrace();

        if (isset($trace[$offset]['object'])) {
            $reflector = new ReflectionObject($trace[$offset]['object']);

            foreach ($reflector->getProperties() as $attribute) {
                $attribute->setAccessible(TRUE);

                if ($attribute->getValue($trace[$offset]['object']) === $this) {
                    $attribute->setValue($trace[$offset]['object'], $object);
                    break;
                }
            }
        }

        return $object;
    }
}
