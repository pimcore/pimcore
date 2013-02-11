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
 * @subpackage Visitor
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * This abstract class provides a default implementation of the node visitor
 * listener.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Visitor
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
abstract class PHP_Depend_Visitor_AbstractListener
    implements PHP_Depend_Visitor_ListenerI
{
    /**
     * Is called when the visitor starts a new class instance.
     *
     * @param PHP_Depend_Code_Class $class The context class instance.
     *
     * @return void
     */
    public function startVisitClass(PHP_Depend_Code_Class $class)
    {
        $this->startVisitNode($class);
    }

    /**
     * Is called when the visitor ends with a class instance.
     *
     * @param PHP_Depend_Code_Class $class The context class instance.
     *
     * @return void
     */
    public function endVisitClass(PHP_Depend_Code_Class $class)
    {
        $this->endVisitNode($class);
    }

    /**
     * Is called when the visitor starts a new trait instance.
     *
     * @param PHP_Depend_Code_Trait $trait The context trait instance.
     *
     * @return void
     * @since 1.0.0
     */
    public function startVisitTrait(PHP_Depend_Code_Trait $trait)
    {
        $this->startVisitNode($trait);
    }

    /**
     * Is called when the visitor ends with a trait instance.
     *
     * @param PHP_Depend_Code_Trait $trait The context trait instance.
     *
     * @return void
     * @since 1.0.0
     */
    public function endVisitTrait(PHP_Depend_Code_Trait $trait)
    {
        $this->endVisitNode($trait);
    }

    /**
     * Is called when the visitor starts a new file instance.
     *
     * @param PHP_Depend_Code_File $file The context file instance.
     *
     * @return void
     */
    public function startVisitFile(PHP_Depend_Code_File $file)
    {
        $this->startVisitNode($file);
    }

    /**
     * Is called when the visitor ends with a file instance.
     *
     * @param PHP_Depend_Code_File $file The context file instance.
     *
     * @return void
     */
    public function endVisitFile(PHP_Depend_Code_File $file)
    {
        $this->endVisitNode($file);
    }

    /**
     * Is called when the visitor starts a new function instance.
     *
     * @param PHP_Depend_Code_Function $function The context function instance.
     *
     * @return void
     */
    public function startVisitFunction(PHP_Depend_Code_Function $function)
    {
        $this->startVisitNode($function);
    }

    /**
     * Is called when the visitor ends with a function instance.
     *
     * @param PHP_Depend_Code_Function $function The context function instance.
     *
     * @return void
     */
    public function endVisitFunction(PHP_Depend_Code_Function $function)
    {
        $this->endVisitNode($function);
    }

    /**
     * Is called when the visitor starts a new interface instance.
     *
     * @param PHP_Depend_Code_Interface $interface The context interface instance.
     *
     * @return void
     */
    public function startVisitInterface(PHP_Depend_Code_Interface $interface)
    {
        $this->startVisitNode($interface);
    }

    /**
     * Is called when the visitor ends with an interface instance.
     *
     * @param PHP_Depend_Code_Interface $interface The context interface instance.
     *
     * @return void
     */
    public function endVisitInterface(PHP_Depend_Code_Interface $interface)
    {
        $this->endVisitNode($interface);
    }

    /**
     * Is called when the visitor starts a new method instance.
     *
     * @param PHP_Depend_Code_Method $method The context method instance.
     *
     * @return void
     */
    public function startVisitMethod(PHP_Depend_Code_Method $method)
    {
        $this->startVisitNode($method);
    }

    /**
     * Is called when the visitor ends with a method instance.
     *
     * @param PHP_Depend_Code_Method $method The context method instance.
     *
     * @return void
     */
    public function endVisitMethod(PHP_Depend_Code_Method $method)
    {
        $this->endVisitNode($method);
    }

    /**
     * Is called when the visitor starts a new package instance.
     *
     * @param PHP_Depend_Code_Package $package The context package instance.
     *
     * @return void
     */
    public function startVisitPackage(PHP_Depend_Code_Package $package)
    {
        $this->startVisitNode($package);
    }

    /**
     * Is called when the visitor ends with a package instance.
     *
     * @param PHP_Depend_Code_Package $package The context package instance.
     *
     * @return void
     */
    public function endVisitPackage(PHP_Depend_Code_Package $package)
    {
        $this->endVisitNode($package);
    }

    /**
     * Is called when the visitor starts a new parameter instance.
     *
     * @param PHP_Depend_Code_Parameter $parameter The context parameter instance.
     *
     * @return void
     */
    public function startVisitParameter(PHP_Depend_Code_Parameter $parameter)
    {
        $this->startVisitNode($parameter);
    }

    /**
     * Is called when the visitor ends with a parameter instance.
     *
     * @param PHP_Depend_Code_Package $parameter The context parameter instance.
     *
     * @return void
     */
    public function endVisitParameter(PHP_Depend_Code_Parameter $parameter)
    {
        $this->endVisitNode($parameter);
    }

    /**
     * Is called when the visitor starts a new property instance.
     *
     * @param PHP_Depend_Code_Property $property The context property instance.
     *
     * @return void
     */
    public function startVisitProperty(PHP_Depend_Code_Property $property)
    {
        $this->startVisitNode($property);
    }

    /**
     * Is called when the visitor ends with a property instance.
     *
     * @param PHP_Depend_Code_Property $property The context property instance.
     *
     * @return void
     */
    public function endVisitProperty(PHP_Depend_Code_Property $property)
    {
        $this->endVisitNode($property);
    }

    /**
     * Generic notification method that is called for every node start.
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return void
     */
    protected function startVisitNode(PHP_Depend_Code_NodeI $node)
    {

    }

    /**
     * Generic notification method that is called when the node processing ends.
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return void
     */
    protected function endVisitNode(PHP_Depend_Code_NodeI $node)
    {

    }
}
