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
 * Representation of a code interface.
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
class PHP_Depend_Code_Interface extends PHP_Depend_Code_AbstractClassOrInterface
{
    /**
     * The type of this class.
     *
     * @since 0.10.0
     */
    const CLAZZ = __CLASS__;

    /**
     * The modifiers for this interface instance, by default an interface is
     * always abstract.
     *
     * @var integer $_modifiers
     */
    protected $modifiers = PHP_Depend_ConstantsI::IS_IMPLICIT_ABSTRACT;

    /**
     * Returns <b>true</b> if this is an abstract class or an interface.
     *
     * @return boolean
     */
    public function isAbstract()
    {
        return true;
    }

    /**
     * Sets a reference onto the parent class of this class node.
     *
     * @param PHP_Depend_Code_ASTClassReference $classReference Reference to the
     *        declared parent class.
     *
     * @return void
     * @since 0.9.5
     */
    public function setParentClassReference(
        PHP_Depend_Code_ASTClassReference $classReference
    ) {
        throw new BadMethodCallException(
            'Unsupported method ' . __METHOD__ . '() called.'
        );
    }

    /**
     * Checks that this user type is a subtype of the given <b>$type</b> instance.
     *
     * @param PHP_Depend_Code_AbstractType $type Possible parent type.
     *
     * @return boolean
     */
    public function isSubtypeOf(PHP_Depend_Code_AbstractType $type)
    {
        if ($type === $this) {
            return true;
        } else if ($type instanceof PHP_Depend_Code_Interface) {
            foreach ($this->getInterfaces() as $interface) {
                if ($interface === $type) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns the declared modifiers for this type.
     *
     * @return integer
     * @since 0.9.4
     */
    public function getModifiers()
    {
        return $this->modifiers;
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
        $visitor->visitInterface($this);
    }

    /**
     * The magic wakeup method will be called by PHP's runtime environment when
     * a serialized instance of this class was unserialized. This implementation
     * of the wakeup method will register this object in the the global class
     * context.
     *
     * @return void
     * @since 0.10.0
     */
    public function __wakeup()
    {
        parent::__wakeup();

        $this->context->registerInterface($this);
    }
}
