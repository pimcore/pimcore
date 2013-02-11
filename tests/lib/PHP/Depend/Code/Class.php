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
 * Represents a php class node.
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
class PHP_Depend_Code_Class extends PHP_Depend_Code_AbstractClassOrInterface
{
    /**
     * The type of this class.
     *
     * @since 0.10.0
     */
    const CLAZZ = __CLASS__;

    /**
     * List of associated properties.
     *
     * @var array(PHP_Depend_Code_Property) $_properties
     */
    private $properties = null;

    /**
     * Returns <b>true</b> if this is an abstract class or an interface.
     *
     * @return boolean
     */
    public function isAbstract()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_EXPLICIT_ABSTRACT)
                                 === PHP_Depend_ConstantsI::IS_EXPLICIT_ABSTRACT);
    }

    /**
     * This method will return <b>true</b> when this class is declared as final.
     *
     * @return boolean
     */
    public function isFinal()
    {
        return (($this->modifiers & PHP_Depend_ConstantsI::IS_FINAL)
                                 === PHP_Depend_ConstantsI::IS_FINAL);
    }

    /**
     * Returns all properties for this class.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getProperties()
    {
        if ($this->properties === null) {
            $this->properties = array();

            $declarations = $this->findChildrenOfType(
                PHP_Depend_Code_ASTFieldDeclaration::CLAZZ
            );
            foreach ($declarations as $declaration) {
                $declarators = $declaration->findChildrenOfType(
                    PHP_Depend_Code_ASTVariableDeclarator::CLAZZ
                );

                foreach ($declarators as $declarator) {

                    $property = new PHP_Depend_Code_Property(
                        $declaration, $declarator
                    );
                    $property->setDeclaringClass($this);
                    $property->setSourceFile($this->getSourceFile());

                    $this->properties[] = $property;
                }
            }
        }

        return new PHP_Depend_Code_NodeIterator($this->properties);
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
        } else if (($parent = $this->getParentClass()) !== null) {
            if ($parent === $type) {
                return true;
            }
            return $parent->isSubtypeOf($type);
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
        if ($this->modifiers !== 0) {
            throw new BadMethodCallException(
                'Cannot overwrite previously set class modifiers.'
            );
        }

        $expected = ~PHP_Depend_ConstantsI::IS_EXPLICIT_ABSTRACT
                  & ~PHP_Depend_ConstantsI::IS_IMPLICIT_ABSTRACT
                  & ~PHP_Depend_ConstantsI::IS_FINAL;

        if (($expected & $modifiers) !== 0) {
            throw new InvalidArgumentException('Invalid class modifier given.');
        }

        $this->modifiers = $modifiers;
    }

    /**
     * Visitor method for node tree traversal.
     *
     * @param PHP_Depend_VisitorI $visitor The context visitor implementation.
     *
     * @return void
     */
    public function accept(PHP_Depend_VisitorI $visitor)
    {
        $visitor->visitClass($this);
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

        $this->context->registerClass($this);
    }
}
