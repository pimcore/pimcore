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
 * @since      1.0.0
 */

/**
 * Represents any valid complex php type.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 * @since      1.0.0
 */
abstract class PHP_Depend_Code_AbstractType extends PHP_Depend_Code_AbstractItem
{
    /**
     * The internal used cache instance.
     *
     * @var PHP_Depend_Util_Cache_Driver
     */
    protected $cache = null;

    /**
     * The currently used builder context.
     *
     * @var PHP_Depend_Builder_Context
     */
    protected $context = null;

    /**
     * The parent package for this class.
     *
     * @var PHP_Depend_Code_Package
     */
    private $package = null;

    /**
     * An <b>array</b> with all constants defined in this class or interface.
     *
     * @var array(string=>mixed)
     */
    protected $constants = null;

    /**
     * This property will indicate that the class or interface is user defined.
     * The parser marks all classes and interfaces as user defined that have a
     * source file and were part of parsing process.
     *
     * @var boolean
     */
    protected $userDefined = false;

    /**
     * List of all parsed child nodes.
     *
     * @var array(PHP_Depend_Code_ASTNodeI)
     */
    protected $nodes = array();

    /**
     * The start line number of the class or interface declaration.
     *
     * @var integer
     */
    protected $startLine = 0;

    /**
     * The end line number of the class or interface declaration.
     *
     * @var integer
     */
    protected $endLine = 0;

    /**
     * Name of the parent package for this class or interface instance. Or
     * <b>NULL</b> when no package was specified.
     *
     * @var string
     */
    protected $packageName = null;

    /**
     * The modifiers for this class instance.
     *
     * @var integer $_modifiers
     */
    protected $modifiers = 0;

    /**
     * Temporary property that only holds methods during the parsing process.
     *
     * @var PHP_Depend_Code_Method[]
     * @since 1.0.2
     */
    private $methods = array();


    /**
     * Setter method for the currently used token cache, where this class or
     * interface instance can store the associated tokens.
     *
     * @param PHP_Depend_Util_Cache_Driver $cache The currently used cache instance.
     *
     * @return PHP_Depend_Code_AbstractType
     */
    public function setCache(PHP_Depend_Util_Cache_Driver $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Sets the currently active builder context.
     *
     * @param PHP_Depend_Builder_Context $context Current builder context.
     *
     * @return PHP_Depend_Code_AbstractType
     */
    public function setContext(PHP_Depend_Builder_Context $context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Adds a parsed child node to this node.
     *
     * @param PHP_Depend_Code_ASTNodeI $node A parsed child node instance.
     *
     * @return void
     * @access private
     */
    public function addChild(PHP_Depend_Code_ASTNodeI $node)
    {
        $this->nodes[] = $node;
    }

    /**
     * Returns all child nodes of this class.
     *
     * @return array(PHP_Depend_Code_ASTNodeI)
     */
    public function getChildren()
    {
        return $this->nodes;
    }

    /**
     * This method will search recursive for the first child node that is an
     * instance of the given <b>$targetType</b>. The returned value will be
     * <b>null</b> if no child exists for that.
     *
     * @param string $targetType Searched class or interface type.
     *
     * @return PHP_Depend_Code_ASTNodeI
     * @access private
     * @todo Refactor $_methods property to getAllMethods() when it exists.
     */
    public function getFirstChildOfType($targetType)
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof $targetType) {
                return $node;
            }
            if (($child = $node->getFirstChildOfType($targetType)) !== null) {
                return $child;
            }
        }
        $methods = $this->getMethods();
        foreach ($methods as $method) {
            if (($child = $method->getFirstChildOfType($targetType)) !== null) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Will find all children for the given type.
     *
     * @param string $targetType The target class or interface type.
     * @param array  &$results   The found children.
     *
     * @return PHP_Depend_Code_ASTNodeI[]
     * @access private
     * @todo Refactor $_methods property to getAllMethods() when it exists.
     */
    public function findChildrenOfType($targetType, array &$results = array())
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof $targetType) {
                $results[] = $node;
            }
            $node->findChildrenOfType($targetType, $results);
        }

        foreach ($this->getMethods() as $method) {
            $method->findChildrenOfType($targetType, $results);
        }

        return $results;
    }

    /**
     * This method will return <b>true</b> when this type has a declaration in
     * the analyzed source files.
     *
     * @return boolean
     */
    public function isUserDefined()
    {
        return $this->userDefined;
    }

    /**
     * This method can be used to mark a type as user defined. User defined
     * means that the type has a valid declaration in the analyzed source files.
     *
     * @return void
     */
    public function setUserDefined()
    {
        $this->userDefined = true;
    }

    /**
     * Returns all {@link PHP_Depend_Code_Method} objects in this type.
     *
     * @return PHP_Depend_Code_Method[]
     */
    public function getMethods()
    {
        if (is_array($this->methods)) {
            return new PHP_Depend_Code_NodeIterator($this->methods);
        }

        $methods = (array) $this->cache
            ->type('methods')
            ->restore($this->getUuid());

        foreach ($methods as $method) {
            $method->sourceFile = $this->sourceFile;
            $method->setParent($this);
        }

        return new PHP_Depend_Code_NodeIterator($methods);
    }

    /**
     * Adds the given method to this type.
     *
     * @param PHP_Depend_Code_Method $method A new type method.
     *
     * @return PHP_Depend_Code_Method
     */
    public function addMethod(PHP_Depend_Code_Method $method)
    {
        $method->setParent($this);

        $this->methods[] = $method;

        return $method;
    }

    /**
     * Returns an array with {@link PHP_Depend_Code_Method} objects that are
     * imported through traits.
     *
     * @return PHP_Depend_Code_Method[]
     * @since 1.0.0
     */
    protected function getTraitMethods()
    {
        $methods = array();

        $uses = $this->findChildrenOfType(
            PHP_Depend_Code_ASTTraitUseStatement::CLAZZ
        );

        foreach ($uses as $use) {
            foreach ($use->getAllMethods() as $method) {
                foreach ($uses as $use2) {
                    if ($use2->hasExcludeFor($method)) {
                        continue 2;
                    }
                }

                $name = strtolower($method->getName());

                if (isset($methods[$name])) {
                    throw new PHP_Depend_Code_Exceptions_MethodCollisionException(
                        $method, $this
                    );
                }
                $methods[$name] = $method;
            }
        }
        return $methods;
    }

    /**
     * Returns an <b>array</b> with all tokens within this type.
     *
     * @return PHP_Depend_Token[]
     */
    public function getTokens()
    {
        return (array) $this->cache
            ->type('tokens')
            ->restore($this->uuid);
    }

    /**
     * Sets the tokens for this type.
     *
     * @param PHP_Depend_Token[] $tokens The generated tokens.
     *
     * @return void
     */
    public function setTokens(array $tokens)
    {
        $this->startLine = reset($tokens)->startLine;
        $this->endLine   = end($tokens)->endLine;

        $this->cache
            ->type('tokens')
            ->store($this->uuid, $tokens);
    }

    /**
     * Returns the line number where the class or interface declaration starts.
     *
     * @return integer
     */
    public function getStartLine()
    {
        return $this->startLine;
    }

    /**
     * Returns the line number where the class or interface declaration ends.
     *
     * @return integer
     */
    public function getEndLine()
    {
        return $this->endLine;
    }

    /**
     * Returns the name of the parent package.
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * Returns the parent package for this class.
     *
     * @return PHP_Depend_Code_Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Sets the parent package for this class.
     *
     * @param PHP_Depend_Code_Package $package The parent package.
     *
     * @return void
     */
    public function setPackage(PHP_Depend_Code_Package $package)
    {
        $this->package    = $package;
        $this->packageName = $package->getName();
    }

    /**
     * Resets the associated package reference.
     *
     * @return void
     */
    public function unsetPackage()
    {
        $this->package    = null;
        $this->packageName = null;
    }

    /**
     * This method will return <b>true</b> when this class or interface instance
     * was restored from the cache and not currently parsed. Otherwise this
     * method will return <b>false</b>.
     *
     * @return boolean
     */
    public function isCached()
    {
        return $this->sourceFile->isCached();
    }

    /**
     * Returns a list of all methods provided by this type or one of its parents.
     *
     * @return array(PHP_Depend_Code_Method)
     */
    public abstract function getAllMethods();

    /**
     * Checks that this user type is a subtype of the given <b>$type</b>
     * instance.
     *
     * @param PHP_Depend_Code_AbstractType $type The possible parent type instance.
     *
     * @return boolean
     * @since 1.0.6
     */
    public abstract function isSubtypeOf(PHP_Depend_Code_AbstractType $type);

    /**
     * The magic sleep method is called by the PHP runtime environment before an
     * instance of this class gets serialized. It returns an array with the
     * names of all those properties that should be cached for this class or
     * interface instance.
     *
     * @return array
     */
    public function __sleep()
    {
        if (is_array($this->methods)) {
            $this->cache
                ->type('methods')
                ->store($this->uuid, $this->methods);

            $this->methods = null;
        }

        return array(
            'cache',
            'context',
            'docComment',
            'endLine',
            'modifiers',
            'name',
            'nodes',
            'packageName',
            'startLine',
            'userDefined',
            'uuid'
        );
    }

    /**
     * The magic wakeup method is called by the PHP runtime environment when a
     * serialized instance of this class gets unserialized and all properties
     * are restored. This implementation of the <b>__wakeup()</b> method sets
     * a flag that this object was restored from the cache and it restores the
     * dependency between this class or interface and it's child methods.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->methods = null;
    }
}
