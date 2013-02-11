<?php
/**
 * This file is part of PHP_Depend.
 * 
 * PHP Version 5
 *
 * Copyright (c) 2008-2012, Manuel Pichler <mapi@pdepend.org>.
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
 *   * Neither the name of Manuel Pichler nor the names of his
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
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Util
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * Simple container class that holds settings for PHP_Depend and all its sub
 * systems.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Util
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
class PHP_Depend_Util_Configuration
{
    /**
     * Simple object tree holding the concrete configuration values.
     *
     * @var stdClass
     * @since 0.10.0
     */
    protected $settings = null;

    /**
     * Constructs a new configuration instance with the given settings tree.
     *
     * @param stdClass $settings The concrete configuration values.
     * 
     * @since 0.10.0
     */
    public function __construct(stdClass $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Magic get method that is called by PHP's runtime engine whenever an
     * undeclared object property is accessed through a read operation. This
     * implementation of the magic get method checks if a configuration value
     * for the given <b>$name</b> exists and returns the configuration value if
     * a matching entry exists. Otherwise this method will throw an exception.
     *
     * @param string $name Name of the requested configuration value.
     *
     * @return mixed
     * @throws OutOfRangeException If no matching configuration value exists.
     * @since 0.10.0
     */
    public function __get($name)
    {
        if (isset($this->settings->{$name})) {
            return $this->settings->{$name};
        }
        throw new OutOfRangeException(
            sprintf("A configuration option '%s' not exists.", $name)
        );
    }

    /**
     * Magic setter method that will be called by PHP's runtime engine when a
     * write operation is performed on an undeclared object property. This
     * implementation of the magic set method always throws an exception, because
     * configuration settings are inmutable.
     *
     * @param string $name  Name of the write property.
     * @param mixed  $value The new property value.
     *
     * @return void
     * @throws OutOfRangeException Whenever this method is called.
     * @since 0.10.0
     */
    public function __set($name, $value)
    {
        throw new OutOfRangeException(
            sprintf("A configuration option '%s' not exists.", $name)
        );
    }

    /**
     * Magic isset method that will be called by PHP's runtime engine when the
     * <em>isset()</em> operator is called on an undefined object property. This
     * implementation of the magic isset method tests if a configuration value
     * for the given <b>$name</b> exists.
     *
     * @param string $name Name of the requested property.
     *
     * @return boolean
     * @since 0.10.0
     */
    public function __isset($name)
    {
        return isset($this->settings->{$name});
    }
}
