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
 * @subpackage Builder
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * Default code tree builder implementation.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Builder
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
class PHP_Depend_Builder_Default implements PHP_Depend_BuilderI
{
    /**
     * The internal used cache instance.
     *
     * @var PHP_Depend_Util_Cache_Driver
     * @since 0.10.0
     */
    protected $cache = null;

    /**
     * The ast builder context.
     *
     * @var PHP_Depend_Builder_Context
     * @since 0.10.0
     */
    protected $context = null;

    /**
     * This property holds all packages found during the parsing phase.
     *
     * @param array(PHP_Depend_Code_Package)
     * @since 0.9.12
     */
    private $preparedPackages = null;

    /**
     * Default package which contains all functions and classes with an unknown
     * scope.
     *
     * @var PHP_Depend_Code_Package $defaultPackage
     */
    protected $defaultPackage = null;

    /**
     * Default source file that acts as a dummy.
     *
     * @var PHP_Depend_Code_File $defaultFile
     */
    protected $defaultFile = null;

    /**
     * All generated {@link PHP_Depend_Code_Trait} objects
     *
     * @var array
     */
    private $traits = array();

    /**
     * All generated {@link PHP_Depend_Code_Class} objects
     *
     * @var array(string=>PHP_Depend_Code_Class) $_classes
     */
    private $classes = array();

    /**
     * All generated {@link PHP_Depend_Code_Interface} instances.
     *
     * @var array(string=>PHP_Depend_Code_Interface) $_interfaces
     */
    private $interfaces = array();

    /**
     * All generated {@link PHP_Depend_Code_Package} objects
     *
     * @var array(string=>PHP_Depend_Code_Package) $_packages
     */
    private $packages = array();

    /**
     * Internal status flag used to check that a build request is internal.
     *
     * @var boolean $_internal
     */
    private $internal = false;

    /**
     * Internal used flag that marks the parsing process as frozen.
     *
     * @var boolean $_frozen
     */
    private $frozen = false;

    /**
     * Cache of all traits created during the regular parsing process.
     *
     * @var array
     */
    private $frozenTraits = array();

    /**
     * Cache of all classes created during the regular parsing process.
     *
     * @var array(PHP_Depend_Code_Class) $_frozenClasses
     */
    private $frozenClasses = array();

    /**
     * Cache of all interfaces created during the regular parsing process.
     *
     * @var array(PHP_Depend_Code_Interface) $_frozenInterfaces
     */
    private $frozenInterfaces = array();

    /**
     * Constructs a new builder instance.
     */
    public function __construct()
    {
        $this->defaultPackage = new PHP_Depend_Code_Package(self::DEFAULT_PACKAGE);
        $this->defaultFile    = new PHP_Depend_Code_File(null);

        $this->packages[self::DEFAULT_PACKAGE] = $this->defaultPackage;

        $this->context = new PHP_Depend_Builder_Context_GlobalStatic($this);
    }

    /**
     * Setter method for the currently used token cache.
     *
     * @param PHP_Depend_Util_Cache_Driver $cache Used token cache instance.
     *
     * @return PHP_Depend_Builder_Default
     * @since 0.10.0
     */
    public function setCache(PHP_Depend_Util_Cache_Driver $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Builds a new code type reference instance.
     *
     * @param string $qualifiedName The qualified name of the referenced type.
     *
     * @return PHP_Depend_Code_ASTClassOrInterfaceReference
     * @since 0.9.5
     */
    public function buildAstClassOrInterfaceReference($qualifiedName)
    {
        $this->checkBuilderState();

        // Debug method creation
        PHP_Depend_Util_Log::debug(
            'Creating: PHP_Depend_Code_ASTClassOrInterfaceReference(' .
            $qualifiedName .
            ')'
        );

        return new PHP_Depend_Code_ASTClassOrInterfaceReference(
            $this->context,
            $qualifiedName
        );
    }

    /**
     * This method will try to find an already existing instance for the given
     * qualified name. It will create a new {@link PHP_Depend_Code_Class}
     * instance when no matching type exists.
     *
     * @param string $qualifiedName The full qualified type identifier.
     *
     * @return PHP_Depend_Code_AbstractClassOrInterface
     * @since 0.9.5
     */
    public function getClassOrInterface($qualifiedName)
    {
        $classOrInterface = $this->findClass($qualifiedName);
        if ($classOrInterface !== null) {
            return $classOrInterface;
        }

        $classOrInterface = $this->findInterface($qualifiedName);
        if ($classOrInterface !== null) {
            return $classOrInterface;
        }
        return $this->buildClassInternal($qualifiedName);
    }

    /**
     * Builds a new php trait instance.
     *
     * @param string $qualifiedName The full qualified trait name.
     *
     * @return PHP_Depend_Code_Trait
     * @since 1.0.0
     */
    public function buildTrait($qualifiedName)
    {
        $this->checkBuilderState();

        $trait = new PHP_Depend_Code_Trait($this->extractTypeName($qualifiedName));
        $trait->setCache($this->cache)
            ->setContext($this->context)
            ->setSourceFile($this->defaultFile);

        return $trait;
    }

    /**
     * This method will try to find an already existing instance for the given
     * qualified name. It will create a new {@link PHP_Depend_Code_Trait}
     * instance when no matching type exists.
     *
     * @param string $qualifiedName The full qualified type identifier.
     *
     * @return PHP_Depend_Code_Trait
     * @since 1.0.0
     */
    public function getTrait($qualifiedName)
    {
        $trait = $this->findTrait($qualifiedName);
        if ($trait === null) {
            $trait = $this->buildTraitInternal($qualifiedName);
        }
        return $trait;
    }

    /**
     * Builds a new trait reference node.
     *
     * @param string $qualifiedName The full qualified trait name.
     *
     * @return PHP_Depend_Code_ASTTraitReference
     * @since 1.0.0
     */
    public function buildAstTraitReference($qualifiedName)
    {
        $this->checkBuilderState();

        PHP_Depend_Util_Log::debug(
            'Creating: PHP_Depend_Code_ASTTraitReference(' . $qualifiedName . ')'
        );

        return new PHP_Depend_Code_ASTTraitReference($this->context, $qualifiedName);
    }

    /**
     * Builds a new class instance or reuses a previous created class.
     *
     * Where possible you should give a qualified class name, that is prefixed
     * with the package identifier.
     *
     * <code>
     *   $builder->buildClass('php::depend::Parser');
     * </code>
     *
     * To determine the correct class, this method implements the following
     * algorithm.
     *
     * <ol>
     *   <li>Check for an exactly matching instance and reuse it.</li>
     *   <li>Check for a class instance that belongs to the default package. If
     *   such an instance exists, reuse it and replace the default package with
     *   the newly given package information.</li>
     *   <li>Check that the requested class is in the default package, if this
     *   is true, reuse the first class instance and ignore the default package.
     *   </li>
     *   <li>Create a new instance for the specified package.</li>
     * </ol>
     *
     * @param string $name The class name.
     *
     * @return PHP_Depend_Code_Class The created class object.
     */
    public function buildClass($name)
    {
        $this->checkBuilderState();
        
        $class = new PHP_Depend_Code_Class($this->extractTypeName($name));
        $class->setCache($this->cache)
            ->setContext($this->context)
            ->setSourceFile($this->defaultFile);

        return $class;
    }

    /**
     * This method will try to find an already existing instance for the given
     * qualified name. It will create a new {@link PHP_Depend_Code_Class}
     * instance when no matching type exists.
     *
     * @param string $qualifiedName The full qualified type identifier.
     *
     * @return PHP_Depend_Code_Class
     * @since 0.9.5
     */
    public function getClass($qualifiedName)
    {
        $class = $this->findClass($qualifiedName);
        if ($class === null) {
            $class = $this->buildClassInternal($qualifiedName);
        }
        return $class;
    }

    /**
     * Builds a new code type reference instance.
     *
     * @param string $qualifiedName The qualified name of the referenced type.
     *
     * @return PHP_Depend_Code_ASTClassReference
     * @since 0.9.5
     */
    public function buildAstClassReference($qualifiedName)
    {
        $this->checkBuilderState();

        // Debug method creation
        PHP_Depend_Util_Log::debug(
            'Creating: PHP_Depend_Code_ASTClassReference(' . $qualifiedName . ')'
        );

        return new PHP_Depend_Code_ASTClassReference($this->context, $qualifiedName);
    }

    /**
     * Builds a new new interface instance.
     *
     * If there is an existing class instance for the given name, this method
     * checks if this class is part of the default namespace. If this is the
     * case this method will update all references to the new interface and it
     * removes the class instance. Otherwise it creates new interface instance.
     *
     * Where possible you should give a qualified interface name, that is
     * prefixed with the package identifier.
     *
     * <code>
     *   $builder->buildInterface('php::depend::Parser');
     * </code>
     *
     * To determine the correct interface, this method implements the following
     * algorithm.
     *
     * <ol>
     *   <li>Check for an exactly matching instance and reuse it.</li>
     *   <li>Check for a interface instance that belongs to the default package.
     *   If such an instance exists, reuse it and replace the default package
     *   with the newly given package information.</li>
     *   <li>Check that the requested interface is in the default package, if
     *   this is true, reuse the first interface instance and ignore the default
     *   package.
     *   </li>
     *   <li>Create a new instance for the specified package.</li>
     * </ol>
     *
     * @param string $name The interface name.
     *
     * @return PHP_Depend_Code_Interface The created interface object.
     */
    public function buildInterface($name)
    {
        $this->checkBuilderState();
        
        $interface = new PHP_Depend_Code_Interface($this->extractTypeName($name));
        $interface->setCache($this->cache)
            ->setContext($this->context)
            ->setSourceFile($this->defaultFile);

        return $interface;
    }

    /**
     * This method will try to find an already existing instance for the given
     * qualified name. It will create a new {@link PHP_Depend_Code_Interface}
     * instance when no matching type exists.
     *
     * @param string $qualifiedName The full qualified type identifier.
     *
     * @return PHP_Depend_Code_Interface
     * @since 0.9.5
     */
    public function getInterface($qualifiedName)
    {
        $interface = $this->findInterface($qualifiedName);
        if ($interface === null) {
            $interface = $this->buildInterfaceInternal($qualifiedName);
        }
        return $interface;
    }

    /**
     * Builds a new method instance.
     *
     * @param string $name The method name.
     *
     * @return PHP_Depend_Code_Method The created class method object.
     */
    public function buildMethod($name)
    {
        $this->checkBuilderState();

        // Debug method creation
        PHP_Depend_Util_Log::debug("Creating: PHP_Depend_Code_Method({$name})");

        // Create a new method instance
        $method = new PHP_Depend_Code_Method($name);
        $method->setCache($this->cache);

        return $method;
    }

    /**
     * Builds a new package instance.
     *
     * @param string $name The package name.
     *
     * @return PHP_Depend_Code_Package The created package object.
     */
    public function buildPackage($name)
    {
        if (!isset($this->packages[$name])) {
            // Debug package creation
            PHP_Depend_Util_Log::debug(
                'Creating: PHP_Depend_Code_Package(' . $name . ')'
            );

            $this->packages[$name] = new PHP_Depend_Code_Package($name);
        }
        return $this->packages[$name];
    }

    /**
     * Builds a new function instance.
     *
     * @param string $name The function name.
     *
     * @return PHP_Depend_Code_Function The function instance.
     */
    public function buildFunction($name)
    {
        $this->checkBuilderState();

        // Debug function creation
        PHP_Depend_Util_Log::debug("Creating: PHP_Depend_Code_Function({$name})");

        // Create new function
        $function = new PHP_Depend_Code_Function($name);
        $function->setCache($this->cache)
            ->setContext($this->context)
            ->setSourceFile($this->defaultFile);
 
        return $function;
    }

    /**
     * Builds a new self reference instance.
     *
     * @param PHP_Depend_Code_AbstractClassOrInterface $type The type instance
     *        that reference the concrete target of self.
     *
     * @return PHP_Depend_Code_ASTSelfReference
     * @since 0.9.6
     */
    public function buildAstSelfReference(
        PHP_Depend_Code_AbstractClassOrInterface $type
    ) {
        PHP_Depend_Util_Log::debug(
            'Creating: PHP_Depend_Code_ASTSelfReference(' . $type->getName() . ')'
        );

        return new PHP_Depend_Code_ASTSelfReference($this->context, $type);
    }

    /**
     * Builds a new parent reference instance.
     *
     * @param PHP_Depend_Code_ASTClassOrInterfaceReference $reference The type
     *        instance that reference the concrete target of parent.
     *
     * @return PHP_Depend_Code_ASTParentReference
     * @since 0.9.6
     */
    public function buildAstParentReference(
        PHP_Depend_Code_ASTClassOrInterfaceReference $reference
    ) {
        include_once 'PHP/Depend/Code/ASTParentReference.php';

        PHP_Depend_Util_Log::debug(
            'Creating: PHP_Depend_Code_ASTParentReference()'
        );

        return new PHP_Depend_Code_ASTParentReference($reference);
    }

    /**
     * Builds a new static reference instance.
     *
     * @param PHP_Depend_Code_AbstractClassOrInterface $owner The owning instance
     *        that reference the concrete target of static.
     *
     * @return PHP_Depend_Code_ASTStaticReference
     * @since 0.9.6
     */
    public function buildAstStaticReference(
        PHP_Depend_Code_AbstractClassOrInterface $owner
    ) {
        PHP_Depend_Util_Log::debug('Creating: PHP_Depend_Code_ASTStaticReference()');

        return new PHP_Depend_Code_ASTStaticReference($this->context, $owner);
    }

    /**
     * Builds a new field declaration node.
     *
     * @return PHP_Depend_Code_ASTFieldDeclaration
     * @since 0.9.6
     */
    public function buildAstFieldDeclaration()
    {
        return $this->buildAstNodeInstance('ASTFieldDeclaration');
    }

    /**
     * Builds a new variable declarator node.
     *
     * @param string $image The source image for the variable declarator.
     *
     * @return PHP_Depend_Code_ASTVariableDeclarator
     * @since 0.9.6
     */
    public function buildAstVariableDeclarator($image)
    {
        return $this->buildAstNodeInstance('ASTVariableDeclarator', $image);
    }

    /**
     * Builds a new static variable declaration node.
     *
     * @param string $image The source image for the statuc declaration.
     *
     * @return PHP_Depend_Code_ASTStaticVariableDeclaration
     * @since 0.9.6
     */
    public function buildAstStaticVariableDeclaration($image)
    {
        return $this->buildAstNodeInstance('ASTStaticVariableDeclaration', $image);
    }

    /**
     * Builds a new constant node.
     *
     * @param string $image The source image for the constant.
     *
     * @return PHP_Depend_Code_ASTConstant
     * @since 0.9.6
     */
    public function buildAstConstant($image)
    {
        return $this->buildAstNodeInstance('ASTConstant', $image);
    }

    /**
     * Builds a new variable node.
     *
     * @param string $image The source image for the variable.
     *
     * @return PHP_Depend_Code_ASTVariable
     * @since 0.9.6
     */
    public function buildAstVariable($image)
    {
        return $this->buildAstNodeInstance('ASTVariable', $image);
    }

    /**
     * Builds a new variable variable node.
     *
     * @param string $image The source image for the variable variable.
     *
     * @return PHP_Depend_Code_ASTVariableVariable
     * @since 0.9.6
     */
    public function buildAstVariableVariable($image)
    {
        return $this->buildAstNodeInstance('ASTVariableVariable', $image);
    }

    /**
     * Builds a new compound variable node.
     *
     * @param string $image The source image for the compound variable.
     *
     * @return PHP_Depend_Code_ASTCompoundVariable
     * @since 0.9.6
     */
    public function buildAstCompoundVariable($image)
    {
        return $this->buildAstNodeInstance('ASTCompoundVariable', $image);
    }

    /**
     * Builds a new compound expression node.
     *
     * @return PHP_Depend_Code_ASTCompoundExpression
     * @since 0.9.6
     */
    public function buildAstCompoundExpression()
    {
        return $this->buildAstNodeInstance('ASTCompoundExpression');
    }

    /**
     * Builds a new closure node.
     *
     * @return PHP_Depend_Code_ASTClosure
     * @since 0.9.12
     */
    public function buildAstClosure()
    {
        return $this->buildAstNodeInstance('ASTClosure');
    }

    /**
     * Builds a new formal parameters node.
     *
     * @return PHP_Depend_Code_ASTFormalParameters
     * @since 0.9.6
     */
    public function buildAstFormalParameters()
    {
        return $this->buildAstNodeInstance('ASTFormalParameters');
    }

    /**
     * Builds a new formal parameter node.
     *
     * @return PHP_Depend_Code_ASTFormalParameter
     * @since 0.9.6
     */
    public function buildAstFormalParameter()
    {
        return $this->buildAstNodeInstance('ASTFormalParameter');
    }

    /**
     * Builds a new expression node.
     *
     * @return PHP_Depend_Code_ASTExpression
     * @since 0.9.8
     */
    public function buildAstExpression()
    {
        return $this->buildAstNodeInstance('ASTExpression');
    }

    /**
     * Builds a new assignment expression node.
     *
     * @param string $image The assignment operator.
     *
     * @return PHP_Depend_Code_ASTAssignmentExpression
     * @since 0.9.8
     */
    public function buildAstAssignmentExpression($image)
    {
        return $this->buildAstNodeInstance('ASTAssignmentExpression', $image);
    }

    /**
     * Builds a new allocation expression node.
     *
     * @param string $image The source image of this expression.
     *
     * @return PHP_Depend_Code_ASTAllocationExpression
     * @since 0.9.6
     */
    public function buildAstAllocationExpression($image)
    {
        return $this->buildAstNodeInstance('ASTAllocationExpression', $image);
    }

    /**
     * Builds a new eval-expression node.
     *
     * @param string $image The source image of this expression.
     *
     * @return PHP_Depend_Code_ASTEvalExpression
     * @since 0.9.12
     */
    public function buildAstEvalExpression($image)
    {
        return $this->buildAstNodeInstance('ASTEvalExpression', $image);
    }

    /**
     * Builds a new exit-expression instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTExitExpression
     * @since 0.9.12
     */
    public function buildAstExitExpression($image)
    {
        return $this->buildAstNodeInstance('ASTExitExpression', $image);
    }

    /**
     * Builds a new clone-expression node.
     *
     * @param string $image The source image of this expression.
     *
     * @return PHP_Depend_Code_ASTCloneExpression
     * @since 0.9.12
     */
    public function buildAstCloneExpression($image)
    {
        return $this->buildAstNodeInstance('ASTCloneExpression', $image);
    }

    /**
     * Builds a new list-expression node.
     *
     * @param string $image The source image of this expression.
     *
     * @return PHP_Depend_Code_ASTListExpression
     * @author Joey Mazzarelli
     * @since 0.9.12
     */
    public function buildAstListExpression($image)
    {
        return $this->buildAstNodeInstance('ASTListExpression', $image);
    }

    /**
     * Builds a new include- or include_once-expression.
     *
     * @return PHP_Depend_Code_ASTIncludeExpression
     * @since 0.9.12
     */
    public function buildAstIncludeExpression()
    {
        return $this->buildAstNodeInstance('ASTIncludeExpression');
    }

    /**
     * Builds a new require- or require_once-expression.
     *
     * @return PHP_Depend_Code_ASTRequireExpression
     * @since 0.9.12
     */
    public function buildAstRequireExpression()
    {
        return $this->buildAstNodeInstance('ASTRequireExpression');
    }

    /**
     * Builds a new array-expression node.
     *
     * @return PHP_Depend_Code_ASTArrayIndexExpression
     * @since 0.9.12
     */
    public function buildAstArrayIndexExpression()
    {
        return $this->buildAstNodeInstance('ASTArrayIndexExpression');
    }

    /**
     * Builds a new string-expression node.
     *
     * <code>
     * //     --------
     * $string{$index}
     * //     --------
     * </code>
     *
     * @return PHP_Depend_Code_ASTStringIndexExpression
     * @since 0.9.12
     */
    public function buildAstStringIndexExpression()
    {
        return $this->buildAstNodeInstance('ASTStringIndexExpression');
    }

    /**
     * Builds a new php array node.
     *
     * @return PHP_Depend_Code_ASTArray
     * @since 1.0.0
     */
    public function buildAstArray()
    {
        return $this->buildAstNodeInstance('ASTArray');
    }

    /**
     * Builds a new array element node.
     *
     * @return PHP_Depend_Code_ASTArrayElement
     * @since 1.0.0
     */
    public function buildAstArrayElement()
    {
        return $this->buildAstNodeInstance('ASTArrayElement');
    }


    /**
     * Builds a new instanceof expression node.
     *
     * @param string $image The source image of this expression.
     *
     * @return PHP_Depend_Code_ASTInstanceOfExpression
     * @since 0.9.6
     */
    public function buildAstInstanceOfExpression($image)
    {
        return $this->buildAstNodeInstance('ASTInstanceOfExpression', $image);
    }

    /**
     * Builds a new isset-expression node.
     *
     * <code>
     * //  -----------
     * if (isset($foo)) {
     * //  -----------
     * }
     *
     * //  -----------------------
     * if (isset($foo, $bar, $baz)) {
     * //  -----------------------
     * }
     * </code>
     *
     * @return PHP_Depend_Code_ASTIssetExpression
     * @since 0.9.12
     */
    public function buildAstIssetExpression()
    {
        return $this->buildAstNodeInstance('ASTIssetExpression');
    }

    /**
     * Builds a new boolean conditional-expression.
     *
     * <code>
     *         --------------
     * $bar = ($foo ? 42 : 23);
     *         --------------
     * </code>
     *
     * @return PHP_Depend_Code_ASTConditionalExpression
     * @since 0.9.8
     */
    public function buildAstConditionalExpression()
    {
        return $this->buildAstNodeInstance('ASTConditionalExpression', '?');
    }

    /**
     * Build a new shift left expression.
     *
     * @return PHP_Depend_Code_ASTShiftLeftExpression
     * @since 1.0.1
     */
    public function buildAstShiftLeftExpression()
    {
        return $this->buildAstNodeInstance('ASTShiftLeftExpression');
    }

    /**
     * Build a new shift right expression.
     *
     * @return PHP_Depend_Code_ASTShiftRightExpression
     * @since 1.0.1
     */
    public function buildAstShiftRightExpression()
    {
        return $this->buildAstNodeInstance('ASTShiftRightExpression');
    }

    /**
     * Builds a new boolean and-expression.
     *
     * @return PHP_Depend_Code_ASTBooleanAndExpression
     * @since 0.9.8
     */
    public function buildAstBooleanAndExpression()
    {
        return $this->buildAstNodeInstance('ASTBooleanAndExpression', '&&');
    }

    /**
     * Builds a new boolean or-expression.
     *
     * @return PHP_Depend_Code_ASTBooleanOrExpression
     * @since 0.9.8
     */
    public function buildAstBooleanOrExpression()
    {
        return $this->buildAstNodeInstance('ASTBooleanOrExpression', '||');
    }

    /**
     * Builds a new logical <b>and</b>-expression.
     *
     * @return PHP_Depend_Code_ASTLogicalAndExpression
     * @since 0.9.8
     */
    public function buildAstLogicalAndExpression()
    {
        return $this->buildAstNodeInstance('ASTLogicalAndExpression', 'and');
    }

    /**
     * Builds a new logical <b>or</b>-expression.
     *
     * @return PHP_Depend_Code_ASTLogicalOrExpression
     * @since 0.9.8
     */
    public function buildAstLogicalOrExpression()
    {
        return $this->buildAstNodeInstance('ASTLogicalOrExpression', 'or');
    }

    /**
     * Builds a new logical <b>xor</b>-expression.
     *
     * @return PHP_Depend_Code_ASTLogicalXorExpression
     * @since 0.9.8
     */
    public function buildAstLogicalXorExpression()
    {
        return $this->buildAstNodeInstance('ASTLogicalXorExpression', 'xor');
    }

    /**
     * Builds a new trait use-statement node.
     *
     * @return PHP_Depend_Code_ASTTraitUseStatement
     * @since 1.0.0
     */
    public function buildAstTraitUseStatement()
    {
        return $this->buildAstNodeInstance('ASTTraitUseStatement');
    }

    /**
     * Builds a new trait adaptation scope
     *
     * @return PHP_Depend_Code_ASTTraitAdaptation
     * @since 1.0.0
     */
    public function buildAstTraitAdaptation()
    {
        return $this->buildAstNodeInstance('ASTTraitAdaptation');
    }

    /**
     * Builds a new trait adaptation alias statement.
     *
     * @param string $image The trait method name.
     *
     * @return PHP_Depend_Code_ASTTraitAdaptationAlias
     * @since 1.0.0
     */
    public function buildAstTraitAdaptationAlias($image)
    {
        return $this->buildAstNodeInstance('ASTTraitAdaptationAlias', $image);
    }

    /**
     * Builds a new trait adaptation precedence statement.
     *
     * @param string $image The trait method name.
     *
     * @return PHP_Depend_Code_ASTTraitAdaptationPrecedence
     * @since 1.0.0
     */
    public function buildAstTraitAdaptationPrecedence($image)
    {
        return $this->buildAstNodeInstance('ASTTraitAdaptationPrecedence', $image);
    }

    /**
     * Builds a new switch-statement-node.
     *
     * @return PHP_Depend_Code_ASTSwitchStatement
     * @since 0.9.8
     */
    public function buildAstSwitchStatement()
    {
        return $this->buildAstNodeInstance('ASTSwitchStatement');
    }

    /**
     * Builds a new switch-label node.
     *
     * @param string $image The source image of this label.
     *
     * @return PHP_Depend_Code_ASTSwitchLabel
     * @since 0.9.8
     */
    public function buildAstSwitchLabel($image)
    {
        return $this->buildAstNodeInstance('ASTSwitchLabel', $image);
    }

    /**
     * Builds a new global-statement instance.
     *
     * @return PHP_Depend_Code_ASTGlobalStatement
     * @since 0.9.12
     */
    public function buildAstGlobalStatement()
    {
        return $this->buildAstNodeInstance('ASTGlobalStatement');
    }

    /**
     * Builds a new unset-statement instance.
     *
     * @return PHP_Depend_Code_ASTUnsetStatement
     * @since 0.9.12
     */
    public function buildAstUnsetStatement()
    {
        return $this->buildAstNodeInstance('ASTUnsetStatement');
    }

    /**
     * Builds a new catch-statement node.
     *
     * @param string $image The source image of this statement.
     *
     * @return PHP_Depend_Code_ASTCatchStatement
     * @since 0.9.8
     */
    public function buildAstCatchStatement($image)
    {
        return $this->buildAstNodeInstance('ASTCatchStatement', $image);
    }

    /**
     * Builds a new if statement node.
     *
     * @param string $image The source image of this statement.
     *
     * @return PHP_Depend_Code_ASTIfStatement
     * @since 0.9.8
     */
    public function buildAstIfStatement($image)
    {
        return $this->buildAstNodeInstance('ASTIfStatement', $image);
    }

    /**
     * Builds a new elseif statement node.
     *
     * @param string $image The source image of this statement.
     *
     * @return PHP_Depend_Code_ASTElseIfStatement
     * @since 0.9.8
     */
    public function buildAstElseIfStatement($image)
    {
        return $this->buildAstNodeInstance('ASTElseIfStatement', $image);
    }

    /**
     * Builds a new for statement node.
     *
     * @param string $image The source image of this statement.
     *
     * @return PHP_Depend_Code_ASTForStatement
     * @since 0.9.8
     */
    public function buildAstForStatement($image)
    {
        return $this->buildAstNodeInstance('ASTForStatement', $image);
    }

    /**
     * Builds a new for-init node.
     *
     * <code>
     *      ------------------------
     * for ($x = 0, $y = 23, $z = 42; $x < $y; ++$x) {}
     *      ------------------------
     * </code>
     *
     * @return PHP_Depend_Code_ASTForInit
     * @since 0.9.8
     */
    public function buildAstForInit()
    {
        return $this->buildAstNodeInstance('ASTForInit');
    }

    /**
     * Builds a new for-update node.
     *
     * <code>
     *                                        -------------------------------
     * for ($x = 0, $y = 23, $z = 42; $x < $y; ++$x, $y = $x + 1, $z = $x + 2) {}
     *                                        -------------------------------
     * </code>
     *
     * @return PHP_Depend_Code_ASTForUpdate
     * @since 0.9.12
     */
    public function buildAstForUpdate()
    {
        return $this->buildAstNodeInstance('ASTForUpdate');
    }

    /**
     * Builds a new foreach-statement node.
     *
     * @param string $image The source image of this statement.
     *
     * @return PHP_Depend_Code_ASTForeachStatement
     * @since 0.9.8
     */
    public function buildAstForeachStatement($image)
    {
        return $this->buildAstNodeInstance('ASTForeachStatement', $image);
    }

    /**
     * Builds a new while-statement node.
     *
     * @param string $image The source image of this statement.
     *
     * @return PHP_Depend_Code_ASTWhileStatement
     * @since 0.9.8
     */
    public function buildAstWhileStatement($image)
    {
        return $this->buildAstNodeInstance('ASTWhileStatement', $image);
    }

    /**
     * Builds a new do/while-statement node.
     *
     * @param string $image The source image of this statement.
     *
     * @return PHP_Depend_Code_ASTDoWhileStatement
     * @since 0.9.12
     */
    public function buildAstDoWhileStatement($image)
    {
        return $this->buildAstNodeInstance('ASTDoWhileStatement', $image);
    }

    /**
     * Builds a new declare-statement node.
     *
     * <code>
     * -------------------------------
     * declare(encoding='ISO-8859-1');
     * -------------------------------
     *
     * -------------------
     * declare(ticks=42) {
     *     // ...
     * }
     * -
     *
     * ------------------
     * declare(ticks=42):
     *     // ...
     * enddeclare;
     * -----------
     * </code>
     *
     * @return PHP_Depend_Code_ASTDeclareStatement
     * @since 0.10.0
     */
    public function buildAstDeclareStatement()
    {
        return $this->buildAstNodeInstance('ASTDeclareStatement');
    }

    /**
     * Builds a new member primary expression node.
     *
     * <code>
     * //--------
     * Foo::bar();
     * //--------
     *
     * //---------
     * Foo::$bar();
     * //---------
     *
     * //---------
     * $obj->bar();
     * //---------
     *
     * //----------
     * $obj->$bar();
     * //----------
     * </code>
     *
     * @param string $image The source image of this expression.
     *
     * @return PHP_Depend_Code_ASTMemberPrimaryPrefix
     * @since 0.9.6
     */
    public function buildAstMemberPrimaryPrefix($image)
    {
        return $this->buildAstNodeInstance('ASTMemberPrimaryPrefix', $image);
    }

    /**
     * Builds a new identifier node.
     *
     * @param string $image The image of this identifier.
     *
     * @return PHP_Depend_Code_ASTIdentifier
     * @since 0.9.6
     */
    public function buildAstIdentifier($image)
    {
        return $this->buildAstNodeInstance('ASTIdentifier', $image);
    }

    /**
     * Builds a new function postfix expression.
     *
     * <code>
     * //-------
     * foo($bar);
     * //-------
     *
     * //--------
     * $foo($bar);
     * //--------
     * </code>
     *
     * @param string $image The image of this node.
     *
     * @return PHP_Depend_Code_ASTFunctionPostfix
     * @since 0.9.6
     */
    public function buildAstFunctionPostfix($image)
    {
        return $this->buildAstNodeInstance('ASTFunctionPostfix', $image);
    }

    /**
     * Builds a new method postfix expression.
     *
     * <code>
     * //   ---------
     * Foo::bar($baz);
     * //   ---------
     *
     * //   ----------
     * Foo::$bar($baz);
     * //   ----------
     * </code>
     *
     * @param string $image The image of this node.
     *
     * @return PHP_Depend_Code_ASTMethodPostfix
     * @since 0.9.6
     */
    public function buildAstMethodPostfix($image)
    {
        return $this->buildAstNodeInstance('ASTMethodPostfix', $image);
    }

    /**
     * Builds a new constant postfix expression.
     *
     * <code>
     * //   ---
     * Foo::BAR;
     * //   ---
     * </code>
     *
     * @param string $image The image of this node.
     *
     * @return PHP_Depend_Code_ASTConstantPostfix
     * @since 0.9.6
     */
    public function buildAstConstantPostfix($image)
    {
        return $this->buildAstNodeInstance('ASTConstantPostfix', $image);
    }

    /**
     * Builds a new property postfix expression.
     *
     * <code>
     * //   ----
     * Foo::$bar;
     * //   ----
     *
     * //       ---
     * $object->bar;
     * //       ---
     * </code>
     *
     * @param string $image The image of this node.
     *
     * @return PHP_Depend_Code_ASTPropertyPostfix
     * @since 0.9.6
     */
    public function buildAstPropertyPostfix($image)
    {
        return $this->buildAstNodeInstance('ASTPropertyPostfix', $image);
    }

    /**
     * Builds a new arguments list.
     *
     * <code>
     * //      ------------
     * Foo::bar($x, $y, $z);
     * //      ------------
     *
     * //       ------------
     * $foo->bar($x, $y, $z);
     * //       ------------
     * </code>
     *
     * @return PHP_Depend_Code_ASTArguments
     * @since 0.9.6
     */
    public function buildAstArguments()
    {
        return $this->buildAstNodeInstance('ASTArguments');
    }

    /**
     * Builds a new array type node.
     *
     * @return PHP_Depend_Code_ASTTypeArray
     * @since 0.9.6
     */
    public function buildAstTypeArray()
    {
        return $this->buildAstNodeInstance('ASTTypeArray');
    }

    /**
     * Builds a new node for the callable type.
     *
     * @return PHP_Depend_Code_ASTTypeCallable
     * @since 1.0.0
     */
    public function buildAstTypeCallable()
    {
        return $this->buildAstNodeInstance('ASTTypeCallable');
    }

    /**
     * Builds a new primitive type node.
     *
     * @param string $image The source image for the primitive type.
     *
     * @return PHP_Depend_Code_ASTPrimitiveType
     * @since 0.9.6
     */
    public function buildAstPrimitiveType($image)
    {
        return $this->buildAstNodeInstance('ASTPrimitiveType', $image);
    }

    /**
     * Builds a new literal node.
     *
     * @param string $image The source image for the literal node.
     *
     * @return PHP_Depend_Code_ASTLiteral
     * @since 0.9.6
     */
    public function buildAstLiteral($image)
    {
        return $this->buildAstNodeInstance('ASTLiteral', $image);
    }

    /**
     * Builds a new php string node.
     *
     * <code>
     * $string = "Manuel $Pichler <{$email}>";
     *
     * // PHP_Depend_Code_ASTString
     * // |-- ASTLiteral             -  "Manuel ")
     * // |-- ASTVariable            -  $Pichler
     * // |-- ASTLiteral             -  " <"
     * // |-- ASTCompoundExpression  -  {...}
     * // |   |-- ASTVariable        -  $email
     * // |-- ASTLiteral             -  ">"
     * </code>
     *
     * @return PHP_Depend_Code_ASTString
     * @since 0.9.10
     */
    public function buildAstString()
    {
        return $this->buildAstNodeInstance('ASTString');
    }

    /**
     * Builds a new heredoc node.
     *
     * @return PHP_Depend_Code_ASTHeredoc
     * @since 0.9.12
     */
    public function buildAstHeredoc()
    {
        return $this->buildAstNodeInstance('ASTHeredoc');
    }

    /**
     * Builds a new constant definition node.
     *
     * <code>
     * class Foo
     * {
     * //  ------------------------
     *     const FOO = 42, BAR = 23;
     * //  ------------------------
     * }
     * </code>
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTConstantDefinition
     * @since 0.9.6
     */
    public function buildAstConstantDefinition($image)
    {
        return $this->buildAstNodeInstance('ASTConstantDefinition', $image);
    }

    /**
     * Builds a new constant declarator node.
     *
     * <code>
     * class Foo
     * {
     *     //    --------
     *     const BAR = 42;
     *     //    --------
     * }
     * </code>
     *
     * Or in a comma separated constant defintion:
     *
     * <code>
     * class Foo
     * {
     *     //    --------
     *     const BAR = 42,
     *     //    --------
     *
     *     //    --------------
     *     const BAZ = 'Foobar',
     *     //    --------------
     *
     *     //    ----------
     *     const FOO = 3.14;
     *     //    ----------
     * }
     * </code>
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTConstantDeclarator
     * @since 0.9.6
     */
    public function buildAstConstantDeclarator($image)
    {
        return $this->buildAstNodeInstance('ASTConstantDeclarator', $image);
    }

    /**
     * Builds a new comment node instance.
     *
     * @param string $cdata The comment text.
     *
     * @return PHP_Depend_Code_ASTComment
     * @since 0.9.8
     */
    public function buildAstComment($cdata)
    {
        return $this->buildAstNodeInstance('ASTComment', $cdata);
    }

    /**
     * Builds a new unary expression node instance.
     *
     * @param string $image The unary expression image/character.
     *
     * @return PHP_Depend_Code_ASTUnaryExpression
     * @since 0.9.11
     */
    public function buildAstUnaryExpression($image)
    {
        return $this->buildAstNodeInstance('ASTUnaryExpression', $image);
    }

    /**
     * Builds a new cast-expression node instance.
     *
     * @param string $image The cast-expression image/character.
     *
     * @return PHP_Depend_Code_ASTCastExpression
     * @since 0.10.0
     */
    public function buildAstCastExpression($image)
    {
        return $this->buildAstNodeInstance('ASTCastExpression', $image);
    }

    /**
     * Builds a new postfix-expression node instance.
     *
     * @param string $image The postfix-expression image/character.
     *
     * @return PHP_Depend_Code_ASTPostfixExpression
     * @since 0.10.0
     */
    public function buildAstPostfixExpression($image)
    {
        return $this->buildAstNodeInstance('ASTPostfixExpression', $image);
    }

    /**
     * Builds a new pre-increment-expression node instance.
     *
     * @return PHP_Depend_Code_ASTPreIncrementExpression
     * @since 0.10.0
     */
    public function buildAstPreIncrementExpression()
    {
        return $this->buildAstNodeInstance('ASTPreIncrementExpression');
    }

    /**
     * Builds a new pre-decrement-expression node instance.
     *
     * @return PHP_Depend_Code_ASTPreDecrementExpression
     * @since 0.10.0
     */
    public function buildAstPreDecrementExpression()
    {
        return $this->buildAstNodeInstance('ASTPreDecrementExpression');
    }

    /**
     * Builds a new function/method scope instance.
     *
     * @return PHP_Depend_Code_ASTScope
     * @since 0.9.12
     */
    public function buildAstScope()
    {
        return $this->buildAstNodeInstance('ASTScope');
    }

    /**
     * Builds a new statement instance.
     *
     * @return PHP_Depend_Code_ASTStatement
     * @since 0.9.12
     */
    public function buildAstStatement()
    {
        return $this->buildAstNodeInstance('ASTStatement');
    }

    /**
     * Builds a new return statement node instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTReturnStatement
     * @since 0.9.12
     */
    public function buildAstReturnStatement($image)
    {
        return $this->buildAstNodeInstance('ASTReturnStatement', $image);
    }

    /**
     * Builds a new break-statement node instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTBreakStatement
     * @since 0.9.12
     */
    public function buildAstBreakStatement($image)
    {
        return $this->buildAstNodeInstance('ASTBreakStatement', $image);
    }

    /**
     * Builds a new continue-statement node instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTContinueStatement
     * @since 0.9.12
     */
    public function buildAstContinueStatement($image)
    {
        return $this->buildAstNodeInstance('ASTContinueStatement', $image);
    }

    /**
     * Builds a new scope-statement instance.
     *
     * @return PHP_Depend_Code_ASTScopeStatement
     * @since 0.9.12
     */
    public function buildAstScopeStatement()
    {
        return $this->buildAstNodeInstance('ASTScopeStatement');
    }

    /**
     * Builds a new try-statement instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTTryStatement
     * @since 0.9.12
     */
    public function buildAstTryStatement($image)
    {
        return $this->buildAstNodeInstance('ASTTryStatement', $image);
    }

    /**
     * Builds a new throw-statement instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTThrowStatement
     * @since 0.9.12
     */
    public function buildAstThrowStatement($image)
    {
        return $this->buildAstNodeInstance('ASTThrowStatement', $image);
    }

    /**
     * Builds a new goto-statement instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTGotoStatement
     * @since 0.9.12
     */
    public function buildAstGotoStatement($image)
    {
        return $this->buildAstNodeInstance('ASTGotoStatement', $image);
    }

    /**
     * Builds a new label-statement instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTLabelStatement
     * @since 0.9.12
     */
    public function buildAstLabelStatement($image)
    {
        return $this->buildAstNodeInstance('ASTLabelStatement', $image);
    }

    /**
     * Builds a new exit-statement instance.
     *
     * @param string $image The source code image for this node.
     *
     * @return PHP_Depend_Code_ASTEchoStatement
     * @since 0.9.12
     */
    public function buildAstEchoStatement($image)
    {
        return $this->buildAstNodeInstance('ASTEchoStatement', $image);
    }

    /**
     * Returns an iterator with all generated {@link PHP_Depend_Code_Package}
     * objects.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getIterator()
    {
        return $this->getPackages();
    }

    /**
     * Returns an iterator with all generated {@link PHP_Depend_Code_Package}
     * objects.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getPackages()
    {
        if ($this->preparedPackages === null) {
            $this->preparedPackages = $this->getPreparedPackages();
        }
        return new PHP_Depend_Code_NodeIterator($this->preparedPackages);
    }

    /**
     * Returns an iterator with all generated {@link PHP_Depend_Code_Package}
     * objects.
     *
     * @return PHP_Depend_Code_NodeIterator
     * @since 0.9.12
     */
    private function getPreparedPackages()
    {
        // Create a package array copy
        $packages = $this->packages;

        // Remove default package if empty
        if ($this->defaultPackage->getTypes()->count() === 0
            && $this->defaultPackage->getFunctions()->count() === 0
        ) {
            unset($packages[self::DEFAULT_PACKAGE]);
        }
        return $packages;
    }

    /**
     * Builds a new trait instance or reuses a previous created trait.
     *
     * Where possible you should give a qualified trait name, that is prefixed
     * with the package identifier.
     *
     * <code>
     *   $builder->buildTrait('php::depend::Parser');
     * </code>
     *
     * To determine the correct trait, this method implements the following
     * algorithm.
     *
     * <ol>
     *   <li>Check for an exactly matching instance and reuse it.</li>
     *   <li>Check for a class instance that belongs to the default package. If
     *   such an instance exists, reuse it and replace the default package with
     *   the newly given package information.</li>
     *   <li>Check that the requested trait is in the default package, if this
     *   is true, reuse the first trait instance and ignore the default package.
     *   </li>
     *   <li>Create a new instance for the specified package.</li>
     * </ol>
     *
     * @param string $qualifiedName The qualified trait name.
     *
     * @return PHP_Depend_Code_Trait
     * @since 0.9.5
     */
    protected function buildTraitInternal($qualifiedName)
    {
        $this->internal = true;

        $trait = $this->buildTrait($qualifiedName);
        $trait->setPackage(
            $this->buildPackage($this->extractPackageName($qualifiedName))
        );

        $this->restoreTrait($trait);

        return $trait;
    }

    /**
     * This method tries to find a trait instance matching for the given
     * qualified name in all scopes already processed. It will return the best
     * matching instance or <b>null</b> if no match exists.
     *
     * @param string $qualifiedName The qualified trait name.
     *
     * @return PHP_Depend_Code_Trait
     * @since 0.9.5
     */
    protected function findTrait($qualifiedName)
    {
        $this->freeze();

        $trait = $this->findType(
            $this->frozenTraits,
            $qualifiedName
        );

        if ($trait === null) {
            $trait = $this->findType($this->traits, $qualifiedName);
        }
        return $trait;
    }

    /**
     * Builds a new new interface instance.
     *
     * If there is an existing interface instance for the given name, this method
     * checks if this interface is part of the default namespace. If this is the
     * case this method will update all references to the new interface and it
     * removes the class instance. Otherwise it creates new interface instance.
     *
     * Where possible you should give a qualified interface name, that is
     * prefixed with the package identifier.
     *
     * <code>
     *   $builder->buildInterface('php::depend::Parser');
     * </code>
     *
     * To determine the correct interface, this method implements the following
     * algorithm.
     *
     * <ol>
     *   <li>Check for an exactly matching instance and reuse it.</li>
     *   <li>Check for a interface instance that belongs to the default package.
     *   If such an instance exists, reuse it and replace the default package
     *   with the newly given package information.</li>
     *   <li>Check that the requested interface is in the default package, if
     *   this is true, reuse the first interface instance and ignore the default
     *   package.
     *   </li>
     *   <li>Create a new instance for the specified package.</li>
     * </ol>
     *
     * @param string $qualifiedName The full qualified interface name.
     *
     * @return PHP_Depend_Code_Interface
     * @since 0.9.5
     */
    protected function buildInterfaceInternal($qualifiedName)
    {
        $this->internal = true;

        $interface = $this->buildInterface($qualifiedName);
        $interface->setPackage(
            $this->buildPackage($this->extractPackageName($qualifiedName))
        );

        $this->restoreInterface($interface);

        return $interface;
    }

    /**
     * This method tries to find an interface instance matching for the given
     * qualified name in all scopes already processed. It will return the best
     * matching instance or <b>null</b> if no match exists.
     *
     * @param string $qualifiedName The qualified interface name.
     *
     * @return PHP_Depend_Code_Interface
     * @since 0.9.5
     */
    protected function findInterface($qualifiedName)
    {
        $this->freeze();

        $interface = $this->findType(
            $this->frozenInterfaces,
            $qualifiedName
        );

        if ($interface === null) {
            $interface = $this->findType(
                $this->interfaces,
                $qualifiedName
            );
        }
        return $interface;
    }

    /**
     * Builds a new class instance or reuses a previous created class.
     *
     * Where possible you should give a qualified class name, that is prefixed
     * with the package identifier.
     *
     * <code>
     *   $builder->buildClass('php::depend::Parser');
     * </code>
     *
     * To determine the correct class, this method implements the following
     * algorithm.
     *
     * <ol>
     *   <li>Check for an exactly matching instance and reuse it.</li>
     *   <li>Check for a class instance that belongs to the default package. If
     *   such an instance exists, reuse it and replace the default package with
     *   the newly given package information.</li>
     *   <li>Check that the requested class is in the default package, if this
     *   is true, reuse the first class instance and ignore the default package.
     *   </li>
     *   <li>Create a new instance for the specified package.</li>
     * </ol>
     *
     * @param string $qualifiedName The qualified class name.
     *
     * @return PHP_Depend_Code_Class
     * @since 0.9.5
     */
    protected function buildClassInternal($qualifiedName)
    {
        $this->internal = true;

        $class = $this->buildClass($qualifiedName);
        $class->setPackage(
            $this->buildPackage($this->extractPackageName($qualifiedName))
        );

        $this->restoreClass($class);

        return $class;
    }

    /**
     * This method tries to find a class instance matching for the given
     * qualified name in all scopes already processed. It will return the best
     * matching instance or <b>null</b> if no match exists.
     *
     * @param string $qualifiedName The qualified class name.
     *
     * @return PHP_Depend_Code_Class
     * @since 0.9.5
     */
    protected function findClass($qualifiedName)
    {
        $this->freeze();

        $class = $this->findType(
            $this->frozenClasses,
            $qualifiedName
        );

        if ($class === null) {
            $class = $this->findType($this->classes, $qualifiedName);
        }
        return $class;
    }

    /**
     * This method tries to find an interface or class instance matching for the
     * given qualified name in all scopes already processed. It will return the
     * best matching instance or <b>null</b> if no match exists.
     *
     * @param array  $instances     Map of already created instances.
     * @param string $qualifiedName The qualified interface or class name.
     *
     * @return PHP_Depend_Code_AbstractType
     * @since 0.9.5
     */
    protected function findType(array $instances, $qualifiedName)
    {
        $classOrInterfaceName = $this->extractTypeName($qualifiedName);
        $packageName          = $this->extractPackageName($qualifiedName);

        $caseInsensitiveName = strtolower($classOrInterfaceName);

        if (!isset($instances[$caseInsensitiveName])) {
            return null;
        }

        // Check for exact match and return first matching instance
        if (isset($instances[$caseInsensitiveName][$packageName])) {
            return reset($instances[$caseInsensitiveName][$packageName]);
        }

        if (!$this->isDefault($packageName)) {
            return null;
        }

        $classesOrInterfaces = reset($instances[$caseInsensitiveName]);
        return reset($classesOrInterfaces);
    }

    /**
     * This method will freeze the actual builder state and create a second
     * runtime scope.
     *
     * @return void
     * @since 0.9.5
     */
    protected function freeze()
    {
        if ($this->frozen === true) {
            return;
        }

        $this->frozen = true;

        $this->frozenTraits     = $this->copyTypesWithPackage($this->traits);
        $this->frozenClasses    = $this->copyTypesWithPackage($this->classes);
        $this->frozenInterfaces = $this->copyTypesWithPackage($this->interfaces);

        $this->traits     = array();
        $this->classes    = array();
        $this->interfaces = array();
    }

    /**
     * Creates a copy of the given input array, but skips all types that do not
     * contain a parent package.
     *
     * @param array $originalTypes The original types created during the parsing
     *        process.
     *
     * @return array
     */
    private function copyTypesWithPackage(array $originalTypes)
    {
        $copiedTypes = array();
        foreach ($originalTypes as $typeName => $packages) {
            foreach ($packages as $package => $types) {
                foreach ($types as $index => $type) {
                    if (is_object($type->getPackage())) {
                        $copiedTypes[$typeName][$package][$index] = $type;
                    }
                }
            }
        }
        return $copiedTypes;
    }

    /**
     * Restores a function within the internal type scope.
     *
     * @param PHP_Depend_Code_Function $function A function instance.
     *
     * @return void
     * @since 0.10.0
     */
    public function restoreFunction(PHP_Depend_Code_Function $function)
    {
        $this->buildPackage($function->getPackageName())
            ->addFunction($function);
    }

    /**
     * Restores a trait within the internal type scope.
     *
     * @param PHP_Depend_Code_Trait $trait A trait instance.
     *
     * @return void
     * @since 0.10.0
     */
    public function restoreTrait(PHP_Depend_Code_Trait $trait)
    {
        $this->storeTrait(
            $trait->getName(),
            $trait->getPackageName(),
            $trait
        );
    }

    /**
     * Restores a class within the internal type scope.
     *
     * @param PHP_Depend_Code_Class $class A class instance.
     *
     * @return void
     * @since 0.10.0
     */
    public function restoreClass(PHP_Depend_Code_Class $class)
    {
        $this->storeClass(
            $class->getName(),
            $class->getPackageName(),
            $class
        );
    }

    /**
     * Restores an interface within the internal type scope.
     *
     * @param PHP_Depend_Code_Interface $interface An interface instance.
     *
     * @return void
     * @since 0.10.0
     */
    public function restoreInterface(PHP_Depend_Code_Interface $interface)
    {
        $this->storeInterface(
            $interface->getName(),
            $interface->getPackageName(),
            $interface
        );
    }

    /**
     * This method will persist a trait instance for later reuse.
     *
     * @param string                $traitName   The local trait name.
     * @param string                $packageName The package name
     * @param PHP_Depend_Code_Trait $trait       The context trait.
     *
     * @return void
     * @@since 1.0.0
     */
    protected function storeTrait(
        $traitName, $packageName, PHP_Depend_Code_Trait $trait
    ) {
        $traitName = strtolower($traitName);
        if (!isset($this->traits[$traitName][$packageName])) {
            $this->traits[$traitName][$packageName] = array();
        }
        $this->traits[$traitName][$packageName][$trait->getUuid()] = $trait;

        $package = $this->buildPackage($packageName);
        $package->addType($trait);
    }

    /**
     * This method will persist a class instance for later reuse.
     *
     * @param string                $className   The local class name.
     * @param string                $packageName The package name
     * @param PHP_Depend_Code_Class $class       The context class.
     *
     * @return void
     * @@since 0.9.5
     */
    protected function storeClass(
        $className, $packageName, PHP_Depend_Code_Class $class
    ) {
        $className = strtolower($className);
        if (!isset($this->classes[$className][$packageName])) {
            $this->classes[$className][$packageName] = array();
        }
        $this->classes[$className][$packageName][$class->getUuid()] = $class;

        $package = $this->buildPackage($packageName);
        $package->addType($class);
    }

    /**
     * This method will persist an interface instance for later reuse.
     *
     * @param string                    $interfaceName The local interface name.
     * @param string                    $packageName   The package name
     * @param PHP_Depend_Code_Interface $interface     The context interface.
     *
     * @return void
     * @@since 0.9.5
     */
    protected function storeInterface(
        $interfaceName, $packageName, PHP_Depend_Code_Interface $interface
    ) {
        $interfaceName = strtolower($interfaceName);
        if (!isset($this->interfaces[$interfaceName][$packageName])) {
            $this->interfaces[$interfaceName][$packageName] = array();
        }
        $this->interfaces[$interfaceName][$packageName][$interface->getUuid()]
            = $interface;

        $package = $this->buildPackage($packageName);
        $package->addType($interface);
    }

    /**
     * Checks that the parser is not frozen or a request is flagged as internal.
     *
     * @param boolean $internal The new internal flag value.
     *
     * @return void
     * @since 0.9.5
     */
    protected function checkBuilderState($internal = false)
    {
        if ($this->frozen === true && $this->internal === false) {
            throw new BadMethodCallException(
                'Cannot create new nodes, when internal state is frozen.'
            );
        }
        $this->internal = $internal;
    }


    /**
     * Returns <b>true</b> if the given package is the default package.
     *
     * @param string $packageName The package name.
     *
     * @return boolean
     */
    protected function isDefault($packageName)
    {
        return ($packageName === self::DEFAULT_PACKAGE);
    }

    /**
     * Extracts the type name of a qualified PHP 5.3 type identifier.
     *
     * <code>
     *   $typeName = $this->extractTypeName('foo\bar\foobar');
     *   var_dump($typeName);
     *   // Results in:
     *   // string(6) "foobar"
     * </code>
     *
     * @param string $qualifiedName The qualified PHP 5.3 type identifier.
     *
     * @return string
     */
    protected function extractTypeName($qualifiedName)
    {
        if (($pos = strrpos($qualifiedName, '\\')) !== false) {
            return substr($qualifiedName, $pos + 1);
        }
        return $qualifiedName;
    }

    /**
     * Extracts the package name of a qualified PHP 5.3 class identifier.
     *
     * If the class name doesn't contain a package identifier this method will
     * return the default identifier.
     *
     * <code>
     *   $packageName = $this->extractPackageName('foo\bar\foobar');
     *   var_dump($packageName);
     *   // Results in:
     *   // string(8) "foo\bar"
     *
     *   $packageName = $this->extractPackageName('foobar');
     *   var_dump($packageName);
     *   // Results in:
     *   // string(6) "+global"
     * </code>
     *
     * @param string $qualifiedName The qualified PHP 5.3 class identifier.
     *
     * @return string
     */
    protected function extractPackageName($qualifiedName)
    {
        if (($pos = strrpos($qualifiedName, '\\')) !== false) {
            return ltrim(substr($qualifiedName, 0, $pos), '\\');
        } else if (PHP_Depend_Util_Type::isInternalType($qualifiedName)) {
            return PHP_Depend_Util_Type::getTypePackage($qualifiedName);
        }
        return self::DEFAULT_PACKAGE;
    }

    /**
     * Creates a {@link PHP_Depend_Code_ASTNode} instance.
     *
     * @param string $className Local name of the ast node class.
     * @param string $image     Optional image for the created ast node.
     *
     * @return PHP_Depend_Code_ASTNode
     * @since 0.9.12
     */
    private function buildAstNodeInstance($className, $image = null)
    {
        $fileName  = "PHP/Depend/Code/{$className}.php";
        $className = "PHP_Depend_Code_{$className}";

        include_once $fileName;

        PHP_Depend_Util_Log::debug("Creating: {$className}({$image})");

        return new $className($image);
    }
}
