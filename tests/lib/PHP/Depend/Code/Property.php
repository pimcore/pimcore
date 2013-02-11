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
 * This code class represents a class property.
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
class PHP_Depend_Code_Property 
       extends ReflectionProperty
    implements PHP_Depend_Code_NodeI
{
    /**
     * The type of this class.
     * 
     * @since 0.10.0
     */
    const CLAZZ = __CLASS__;
    
    /**
     * The unique identifier for this function.
     *
     * @var string $_uuid
     */
    private $uuid = null;

    /**
     * The source file for this item.
     *
     * @var PHP_Depend_Code_File $_sourceFile
     */
    private $sourceFile = null;
    
    /**
     * The parent type object.
     *
     * @var PHP_Depend_Code_Class $_declaringClass
     */
    private $declaringClass = null;

    /**
     * The wrapped field declaration instance.
     *
     * @var PHP_Depend_Code_ASTFieldDeclaration
     * @since 0.9.6
     */
    private $fieldDeclaration = null;

    /**
     * The wrapped variable declarator instance.
     *
     * @var PHP_Depend_Code_ASTVariableDeclarator
     * @since 0.9.6
     */
    private $variableDeclarator = null;

    /**
     * Constructs a new item for the given field declaration and variable
     * declarator.
     *
     * @param PHP_Depend_Code_ASTFieldDeclaration   $fieldDeclaration   The context
     *        field declaration where this property was declared in the source.
     * @param PHP_Depend_Code_ASTVariableDeclarator $variableDeclarator The context
     *        variable declarator for this property instance.
     */
    public function __construct(
        PHP_Depend_Code_ASTFieldDeclaration $fieldDeclaration,
        PHP_Depend_Code_ASTVariableDeclarator $variableDeclarator
    ) {
        $this->fieldDeclaration   = $fieldDeclaration;
        $this->variableDeclarator = $variableDeclarator;

        $this->uuid = spl_object_hash($this);
    }

    /**
     * Returns the item name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->variableDeclarator->getImage();
    }

    /**
     * Returns a uuid for this code node.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * This method returns a OR combined integer of the declared modifiers for
     * this property.
     *
     * @return integer
     * @since 0.9.6
     */
    public function getModifiers()
    {
        return $this->fieldDeclaration->getModifiers();
    }

    /**
     * Returns <b>true</b> if this node is marked as public, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isPublic()
    {
        return $this->fieldDeclaration->isPublic();
    }

    /**
     * Returns <b>true</b> if this node is marked as protected, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isProtected()
    {
        return $this->fieldDeclaration->isProtected();
    }

    /**
     * Returns <b>true</b> if this node is marked as private, otherwise the
     * returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->fieldDeclaration->isPrivate();
    }

    /**
     * Returns <b>true</b> when this node is declared as static, otherwise
     * the returned value will be <b>false</b>.
     *
     * @return boolean
     */
    public function isStatic()
    {
        return $this->fieldDeclaration->isStatic();
    }

    /**
     * This method will return <b>true</b> when this property doc comment
     * contains an array type hint, otherwise the it will return <b>false</b>.
     *
     * @return boolean
     * @since 0.9.6
     */
    public function isArray()
    {
        $typeNode = $this->fieldDeclaration->getFirstChildOfType(
            PHP_Depend_Code_ASTType::CLAZZ
        );
        if ($typeNode === null) {
            return false;
        }
        return $typeNode->isArray();
    }

    /**
     * This method will return <b>true</b> when this property doc comment
     * contains a primitive type hint, otherwise the it will return <b>false</b>.
     *
     * @return boolean
     * @since 0.9.6
     */
    public function isPrimitive()
    {
        $typeNode = $this->fieldDeclaration->getFirstChildOfType(
            PHP_Depend_Code_ASTType::CLAZZ
        );
        if ($typeNode === null) {
            return false;
        }
        return $typeNode->isPrimitive();
    }

    /**
     * Returns the type of this property. This method will return <b>null</b>
     * for all scalar type, only class properties will have a type.
     *
     * @return PHP_Depend_Code_AbstractClassOrInterface
     * @since 0.9.5
     */
    public function getClass()
    {
        $reference = $this->fieldDeclaration->getFirstChildOfType(
            PHP_Depend_Code_ASTClassOrInterfaceReference::CLAZZ
        );
        if ($reference === null) {
            return null;
        }
        return $reference->getType();
    }

    /**
     * Returns the source file for this item.
     *
     * @return PHP_Depend_Code_File
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * Sets the source file for this item.
     *
     * @param PHP_Depend_Code_File $sourceFile The item source file.
     *
     * @return void
     */
    public function setSourceFile(PHP_Depend_Code_File $sourceFile)
    {
        if ($this->sourceFile === null || $this->sourceFile->getName() === null) {
            $this->sourceFile = $sourceFile;
        }
    }

    /**
     * Returns the doc comment for this item or <b>null</b>.
     *
     * @return string
     */
    public function getDocComment()
    {
        return $this->fieldDeclaration->getComment();
    }

    /**
     * Returns the line number where the property declaration can be found.
     *
     * @return integer
     * @since 0.9.6
     */
    public function getStartLine()
    {
        return $this->variableDeclarator->getStartLine();
    }

    /**
     * Returns the column number where the property declaration starts.
     *
     * @return integer
     * @since 0.9.8
     */
    public function getStartColumn()
    {
        return $this->variableDeclarator->getStartColumn();
    }

    /**
     * Returns the line number where the property declaration ends.
     *
     * @return integer
     * @since 0.9.6
     */
    public function getEndLine()
    {
        return $this->variableDeclarator->getEndLine();
    }

    /**
     * Returns the column number where the property declaration ends.
     *
     * @return integer
     * @since 0.9.8
     */
    public function getEndColumn()
    {
        return $this->variableDeclarator->getEndColumn();
    }

    /**
     * This method will return the class where this property was declared.
     *
     * @return PHP_Depend_Code_AbstractClassOrInterface
     * @since 0.9.6
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * Sets the declaring class object.
     *
     * @param PHP_Depend_Code_Class $declaringClass The declaring class.
     *
     * @return void
     * @since 0.9.6
     */
    public function setDeclaringClass(PHP_Depend_Code_Class $declaringClass)
    {
        $this->declaringClass = $declaringClass;
    }

    /**
     * This method will return <b>true</b> when the parameter declaration
     * contains a default value.
     *
     * @return boolean
     * @since 0.9.6
     */
    public function isDefaultValueAvailable()
    {
        $value = $this->variableDeclarator->getValue();
        if ($value === null) {
            return false;
        }
        return $value->isValueAvailable();
    }

    /**
     * This method will return the default value for this property instance or
     * <b>null</b> when this property was only declared and not initialized.
     *
     * @return mixed
     * @since 0.9.6
     */
    public function getDefaultValue()
    {
        $value = $this->variableDeclarator->getValue();
        if ($value === null) {
            return null;
        }
        return $value->getValue();
    }

    /**
     * This method will return the value for this property instance on the given
     * object or <b>null</b> when the property has no value.
     *
     * @param stdClass $object The context object for PHP's native reflection api.
     *
     * @return mixed
     * @since 0.9.6
     * @SuppressWarnings("PMD.UnusedFormalParameter")
     */
    public function getValue($object = null)
    {
        throw new ReflectionException(__METHOD__ . '() is not supported.');
    }

    /**
     * This method is used to modifiy the property value of the given object.
     *
     * @param stdClass $object The context class where this property should set
     *                         the given value.
     * @param mixed    $value  Default value used by the native Reflection api
     *                         as new property default value.
     *
     * @return void
     * @since 0.9.6
     * @SuppressWarnings("PMD.UnusedFormalParameter")
     */
    public function setValue($object, $value = null)
    {
        throw new ReflectionException(__METHOD__ . '() is not supported.');
    }

    /**
     * This method can be used in PHP's native reflection api to allow access to
     * private or protected object property. This userland implementation will
     * always throw an exception.
     *
     * @param boolean $value Boolean <b>true</b> for accessible and <b>false</b>
     *        for a write protected property.
     *
     * @return void
     * @since 0.9.6
     * @SuppressWarnings("PMD.UnusedFormalParameter")
     */
    public function setAccessible($value)
    {
        throw new ReflectionException(__METHOD__ . '() is not supported.');
    }

    /**
     * This method will return <b>true</b> when the context property was
     * declared during compile time and not dynamically during runtime. In this
     * reflection implementation the default value will be always <b>true</b>.
     *
     * @return boolean
     * @since 0.9.6
     */
    public function isDefault()
    {
        return true;
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
        $visitor->visitProperty($this);
    }

    /**
     * This method returns a string representation of this parameter.
     *
     * @return string
     * @since 0.9.6
     */
    public function __toString()
    {
        $default = ($this->isDefault() === true ? ' <default>' : '');
        $static  = '';

        if ($this->isStatic() === true) {
            $default = '';
            $static  = ' static';
        }

        $visibility = ' public';
        if ($this->isProtected() === true) {
            $visibility = ' protected';
        } else if ($this->isPrivate() === true) {
            $visibility = ' private';
        }

        return sprintf(
            'Property [%s%s%s %s ]%s',
            $default,
            $visibility,
            $static,
            $this->getName(),
            PHP_EOL
        );
    }

    // @codeCoverageIgnoreStart

    /**
     * This method can be called by the PHP_Depend runtime environment or a
     * utilizing component to free up memory. This methods are required for
     * PHP version < 5.3 where cyclic references can not be resolved
     * automatically by PHP's garbage collector.
     *
     * @return void
     * @since 0.9.12
     * @deprecated Since 1.0.0
     */
    public function free()
    {
        trigger_error(__METHOD__ . '() is deprecated.', E_USER_DEPRECATED);
    }

    // @codeCoverageIgnoreEnd
}
