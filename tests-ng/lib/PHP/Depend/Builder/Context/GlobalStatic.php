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
 * @subpackage Builder_Context
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 * @since      0.10.0
 */

/**
 * This class provides the default implementation of the builder context.
 *
 * This class utilizes the simple <b>static</b> language construct to share the
 * context instance between all using objects.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Builder_Context
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 * @since      0.10.0
 */
class PHP_Depend_Builder_Context_GlobalStatic implements PHP_Depend_Builder_Context
{
    /**
     * The currently used ast builder.
     *
     * @var PHP_Depend_BuilderI
     */
    protected static $builder = null;

    /**
     * Constructs a new builder context instance.
     *
     * @param PHP_Depend_BuilderI $builder The currently used ast builder.
     */
    public function __construct(PHP_Depend_BuilderI $builder)
    {
        self::$builder = $builder;
    }

    /**
     * This method can be used to register an existing function in the current
     * application context.
     *
     * @param PHP_Depend_Code_Function $function The function instance.
     *
     * @return void
     */
    public function registerFunction(PHP_Depend_Code_Function $function)
    {
        self::$builder->restoreFunction($function);
    }

    /**
     * This method can be used to register an existing trait in the current
     * class context.
     *
     * @param PHP_Depend_Code_Trait $trait The trait instance.
     *
     * @return void
     * @since 1.0.0
     */
    public function registerTrait(PHP_Depend_Code_Trait $trait)
    {
        self::$builder->restoreTrait($trait);
    }

    /**
     * This method can be used to register an existing class in the current
     * class context.
     *
     * @param PHP_Depend_Code_Class $class The class instance.
     *
     * @return void
     */
    public function registerClass(PHP_Depend_Code_Class $class)
    {
        self::$builder->restoreClass($class);
    }

    /**
     * This method can be used to register an existing interface in the current
     * class context.
     *
     * @param PHP_Depend_Code_Interface $interface The interface instance.
     *
     * @return void
     */
    public function registerInterface(PHP_Depend_Code_Interface $interface)
    {
        self::$builder->restoreInterface($interface);
    }

    /**
     * Returns the trait instance for the given qualified name.
     *
     * @param string $qualifiedName Full qualified trait name.
     *
     * @return PHP_Depend_Code_Trait
     * @since 1.0.0
     */
    public function getTrait( $qualifiedName )
    {
        return $this->getBuilder()->getTrait($qualifiedName);
    }

    /**
     * Returns the class instance for the given qualified name.
     *
     * @param string $qualifiedName Full qualified class name.
     *
     * @return PHP_Depend_Code_Class
     */
    public function getClass($qualifiedName)
    {
        return $this->getBuilder()->getClass($qualifiedName);
    }

    /**
     * Returns a class or an interface instance for the given qualified name.
     *
     * @param string $qualifiedName Full qualified class or interface name.
     *
     * @return PHP_Depend_Code_AbstractClassOrInterface
     */
    public function getClassOrInterface($qualifiedName)
    {
        return $this->getBuilder()->getClassOrInterface($qualifiedName);
    }

    /**
     * Returns the currently used builder instance.
     *
     * @return PHP_Depend_BuilderI
     */
    protected function getBuilder()
    {
        return self::$builder;
    }
}
