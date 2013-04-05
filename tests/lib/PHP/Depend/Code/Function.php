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
 * Represents a php function node.
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
class PHP_Depend_Code_Function extends PHP_Depend_Code_AbstractCallable
{
    /**
     * The type of this class.
     *
     * @since 0.10.0
     */
    const CLAZZ = __CLASS__;

    /**
     * The parent package for this function.
     *
     * @var PHP_Depend_Code_Package
     * @since 0.10.0
     */
    private $package = null;

    /**
     * The currently used builder context.
     *
     * @var PHP_Depend_Builder_Context
     * @since 0.10.0
     */
    protected $context = null;

    /**
     * The name of the parent package for this function. We use this property
     * to restore the parent package while we unserialize a cached object tree.
     *
     * @var string
     */
    protected $packageName = null;

    /**
     * Sets the currently active builder context.
     *
     * @param PHP_Depend_Builder_Context $context Current builder context.
     *
     * @return PHP_Depend_Code_Function
     * @since 0.10.0
     */
    public function setContext(PHP_Depend_Builder_Context $context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Returns the parent package for this function.
     *
     * @return PHP_Depend_Code_Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Sets the parent package for this function.
     *
     * @param PHP_Depend_Code_Package $package The parent package.
     *
     * @return void
     */
    public function setPackage(PHP_Depend_Code_Package $package)
    {
        $this->packageName = $package->getName();
        $this->package    = $package;
    }

    /**
     * Resets the package associated with this function node.
     *
     * @return void
     * @since 0.10.2
     */
    public function unsetPackage()
    {
        $this->packageName = null;
        $this->package    = null;
    }

    /**
     * Returns the name of the parent namespace/package or <b>NULL</b> when this
     * function does not belong to a package.
     *
     * @return string
     * @since 0.10.0
     */
    public function getPackageName()
    {
        return $this->packageName;
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
        $visitor->visitFunction($this);
    }

    /**
     * The magic sleep method will be called by the PHP engine when this class
     * gets serialized. It returns an array with those properties that should be
     * cached for all function instances.
     *
     * @return array(string)
     * @since 0.10.0
     */
    public function __sleep()
    {
        return array_merge(array('context', 'packageName'), parent::__sleep());
    }

    /**
     * The magic wakeup method will be called by PHP's runtime environment when
     * a serialized instance of this class was unserialized. This implementation
     * of the wakeup method will register this object in the the global function
     * context.
     *
     * @return void
     * @since 0.10.0
     */
    public function __wakeup()
    {
        $this->context->registerFunction($this);
    }
}
