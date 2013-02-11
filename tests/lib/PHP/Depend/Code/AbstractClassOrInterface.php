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
 * Represents an interface or a class type.
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
abstract class PHP_Depend_Code_AbstractClassOrInterface
       extends PHP_Depend_Code_AbstractType
{
    /**
     * The parent for this class node.
     *
     * @var PHP_Depend_Code_ASTClassReference
     * @since 0.9.5
     */
    protected $parentClassReference = null;

    /**
     * List of all interfaces implemented/extended by the this type.
     *
     * @var array(PHP_Depend_Code_ASTClassOrInterfaceReference)
     */
    protected $interfaceReferences = array();

    /**
     * An <b>array</b> with all constants defined in this class or interface.
     *
     * @var array(string=>mixed)
     */
    protected $constants = null;

    /**
     * Returns the parent class or <b>null</b> if this class has no parent.
     *
     * @return PHP_Depend_Code_Class
     */
    public function getParentClass()
    {
        // No parent? Stop here!
        if ($this->parentClassReference === null) {
            return null;
        }

        $parentClass = $this->parentClassReference->getType();

        if ($parentClass === $this) {
            throw new PHP_Depend_Code_Exceptions_RecursiveInheritanceException(
                $this
            );
        }

        // Check parent against global filter
        $collection = PHP_Depend_Code_Filter_Collection::getInstance();
        if ($collection->accept($parentClass) === false) {
            return null;
        }

        return $parentClass;
    }

    /**
     * Returns an array with all parents for the current class.
     *
     * The returned array contains class instances for each parent of this class.
     * They are ordered in the reverse inheritance order. This means that the
     * direct parent of this class is the first element in the returned array
     * and parent of this parent the second element and so on.
     *
     * @return PHP_Depend_Code_Class[]
     * @since 1.0.0
     */
    public function getParentClasses()
    {
        $parents = array();
        $parent  = $this;
        while (is_object($parent = $parent->getParentClass())) {
            if (in_array($parent, $parents, true)) {
                throw new PHP_Depend_Code_Exceptions_RecursiveInheritanceException(
                    $parent
                );
            }
            $parents[] = $parent;
        }
        return $parents;
    }

    /**
     * Returns a reference onto the parent class of this class node or <b>null</b>.
     *
     * @return PHP_Depend_Code_ASTClassReference
     * @since 0.9.5
     */
    public function getParentClassReference()
    {
        return $this->parentClassReference;
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
        $this->nodes[]              = $classReference;
        $this->parentClassReference = $classReference;
    }

    /**
     * Returns a node iterator with all implemented interfaces.
     *
     * @return PHP_Depend_Code_NodeIterator
     * @since 0.9.5
     */
    public function getInterfaces()
    {
        $stack = $this->getParentClasses();
        array_unshift($stack, $this);

        $interfaces = array();

        while (($top = array_pop($stack)) !== null) {

            foreach ($top->interfaceReferences as $interfaceReference) {
                $interface = $interfaceReference->getType();
                if (in_array($interface, $interfaces, true) === true) {
                    continue;
                }
                $interfaces[] = $interface;
                $stack[] = $interface;
            }
        }

        return new PHP_Depend_Code_NodeIterator($interfaces);
    }

    /**
     * Returns an array of references onto the interfaces of this class node.
     *
     * @return array
     * @since 0.10.4
     */
    public function getInterfaceReferences()
    {
        return $this->interfaceReferences;
    }

    /**
     * Adds a interface reference node.
     *
     * @param PHP_Depend_Code_ASTClassOrInterfaceReference $interfaceReference The
     *        extended or implemented interface reference.
     *
     * @return void
     * @since 0.9.5
     */
    public function addInterfaceReference(
        PHP_Depend_Code_ASTClassOrInterfaceReference $interfaceReference
    ) {
        $this->nodes[]               = $interfaceReference;
        $this->interfaceReferences[] = $interfaceReference;
    }

    /**
     * Returns an <b>array</b> with all constants defined in this class or
     * interface.
     *
     * @return array(string=>mixed)
     */
    public function getConstants()
    {
        if ($this->constants === null) {
            $this->initConstants();
        }
        return $this->constants;
    }

    /**
     * This method returns <b>true</b> when a constant for <b>$name</b> exists,
     * otherwise it returns <b>false</b>.
     *
     * @param string $name Name of the searched constant.
     *
     * @return boolean
     * @since 0.9.6
     */
    public function hasConstant($name)
    {
        if ($this->constants === null) {
            $this->initConstants();
        }
        return array_key_exists($name, $this->constants);
    }

    /**
     * This method will return the value of a constant for <b>$name</b> or it
     * will return <b>false</b> when no constant for that name exists.
     *
     * @param string $name Name of the searched constant.
     *
     * @return mixed
     * @since 0.9.6
     */
    public function getConstant($name)
    {
        if ($this->hasConstant($name) === true) {
            return $this->constants[$name];
        }
        return false;
    }

    /**
     * Returns a list of all methods provided by this type or one of its parents.
     *
     * @return PHP_Depend_Code_Method[]
     * @since 0.9.10
     */
    public function getAllMethods()
    {
        $methods = array();
        foreach ($this->getInterfaces() as $interface) {
            foreach ($interface->getAllMethods() as $method) {
                $methods[strtolower($method->getName())] = $method;
            }
        }

        if (is_object($parentClass = $this->getParentClass())) {
            foreach ($parentClass->getAllMethods() as $methodName => $method) {
                $methods[$methodName] = $method;
            }
        }

        foreach ($this->getTraitMethods() as $method) {
            $methods[strtolower($method->getName())] = $method;
        }

        foreach ($this->getMethods() as $method) {
            $methods[strtolower($method->getName())] = $method;
        }

        return $methods;
    }

    /**
     * Returns all {@link PHP_Depend_Code_AbstractClassOrInterface} objects this
     * type depends on.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getDependencies()
    {
        $references = $this->interfaceReferences;
        if ($this->parentClassReference !== null) {
            $references[] = $this->parentClassReference;
        }

        return new PHP_Depend_Code_ClassOrInterfaceReferenceIterator($references);
    }

    /**
     * Returns <b>true</b> if this is an abstract class or an interface.
     *
     * @return boolean
     */
    public abstract function isAbstract();

    /**
     * Returns the declared modifiers for this type.
     *
     * @return integer
     */
    public abstract function getModifiers();

    /**
     * This method initializes the constants defined in this class or interface.
     *
     * @return void
     * @since 0.9.6
     */
    private function initConstants()
    {
        $this->constants = array();
        if (($parentClass = $this->getParentClass()) !== null) {
            $this->constants = $parentClass->getConstants();
        }

        foreach ($this->getInterfaces() as $interface) {
            $this->constants = array_merge(
                $this->constants,
                $interface->getConstants()
            );
        }

        $definitions = $this->findChildrenOfType(
            PHP_Depend_Code_ASTConstantDefinition::CLAZZ
        );

        foreach ($definitions as $definition) {
            $declarators = $definition->findChildrenOfType(
                PHP_Depend_Code_ASTConstantDeclarator::CLAZZ
            );

            foreach ($declarators as $declarator) {
                $image = $declarator->getImage();
                $value = $declarator->getValue()->getValue();

                $this->constants[$image] = $value;
            }
        }
    }

    /**
     * The magic sleep method is called by the PHP runtime environment before an
     * instance of this class gets serialized. It returns an array with the
     * names of all those properties that should be cached for this class or
     * interface instance.
     *
     * @return array
     * @since 0.10.0
     */
    public function __sleep()
    {
        return array_merge(
            array('constants', 'interfaceReferences', 'parentClassReference'),
            parent::__sleep()
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
