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
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * Represents a php method node.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
class PHP_Depend_Code_Method extends PHP_Depend_Code_AbstractCallable
{
    /**
     * The parent type object.
     *
     * @var PHP_Depend_Code_AbstractType
     */
    protected $parent = null;

    /**
     * Defined modifiers for this property node.
     *
     * @var integer
     */
    protected $modifiers = 0;

    /**
     * This method returns a OR combined integer of the declared modifiers for
     * this method.
     *
     * @return integer
     * @since 1.0.0
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * This method sets a OR combined integer of the declared modifiers for this
     * node.
     *
     * This method will throw an exception when the value of given <b>$modifiers</b>
     * contains an invalid/unexpected modifier
     *
     * @param integer $modifiers The declared modifiers for this node.
     *
     * @return void
     * @throws InvalidArgumentException If the given modifier contains unexpected
     *                                  values.
     * @since 0.9.4
     */
    public function setModifiers($modifiers)
    {
        $expected = ~PHP_Depend_ConstantsI::IS_PUBLIC
                  & ~PHP_Depend_ConstantsI::IS_PROTECTED
                  & ~PHP_Depend_ConstantsI::IS_PRIVATE
                  & ~PHP_Depend_ConstantsI::IS_STATIC
                  & ~PHP_Depend_ConstantsI::IS_ABSTRACT
                  & ~PHP_Depend_ConstantsI::IS_FINAL;

        if (($expected & $modifiers) !== 0) {
            throw new InvalidArgumentException('Invalid method modifier given.');
        }

        $this->modifiers = $modifiers;
    }

    /**
     * Returns <b>true</b> if this is an abstract method.
     *
     * @return boolean
     */
    public function isAbstract()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_ABSTRACT)
                                 === PHP_Depend_ConstantsI::IS_ABSTRACT);
    }

    /**
     * Returns <b>true</b> if this node is marked as public, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isPublic()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_PUBLIC)
                                 === PHP_Depend_ConstantsI::IS_PUBLIC);
    }

    /**
     * Returns <b>true</b> if this node is marked as protected, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isProtected()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_PROTECTED)
                                 === PHP_Depend_ConstantsI::IS_PROTECTED);
    }

    /**
     * Returns <b>true</b> if this node is marked as private, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isPrivate()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_PRIVATE)
                                 === PHP_Depend_ConstantsI::IS_PRIVATE);
    }

    /**
     * Returns <b>true</b> when this node is declared as static, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isStatic()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_STATIC)
                                 === PHP_Depend_ConstantsI::IS_STATIC);
    }

    /**
     * Returns <b>true</b> when this node is declared as final, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isFinal()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_FINAL)
                                 === PHP_Depend_ConstantsI::IS_FINAL);
    }

    /**
     * Returns the parent type object or <b>null</b>
     *
     * @return PHP_Depend_Code_AbstractType|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent type object.
     *
     * @param PHP_Depend_Code_AbstractType $parent The parent type.
     *
     * @return void
     */
    public function setParent(PHP_Depend_Code_AbstractType $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Returns the source file where this method was declared.
     *
     * @return PHP_Depend_Code_File
     * @throws PHP_Depend_Code_Exceptions_SourceNotFoundException When no parent
     *         class or interface was set for this method instance.
     * @since 0.10.0
     */
    public function getSourceFile()
    {
        if ($this->parent === null) {
            throw new PHP_Depend_Code_Exceptions_SourceNotFoundException($this);
        }
        return $this->parent->getSourceFile();
    }

    /**
     * Visitor method for node tree traversal.
     *
     * @param PHP_Depend_VisitorI $visitor The context visitor
     *                                              implementation.
     *
     * @return void
     */
    public function accept(PHP_Depend_VisitorI $visitor)
    {
        $visitor->visitMethod($this);
    }

    /**
     * The magic sleep method will be called by the PHP engine when this class
     * gets serialized. It returns an array with those properties that should be
     * cached for method instances.
     *
     * @return array(string)
     * @since 0.10.0
     */
    public function __sleep()
    {
        return array_merge(array('modifiers'), parent::__sleep());
    }
}
