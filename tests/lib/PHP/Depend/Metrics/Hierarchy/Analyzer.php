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
 * @subpackage Metrics
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * This analyzer calculates class/package hierarchy metrics.
 *
 * This analyzer expects that a node list filter is set, before it starts the
 * analyze process. This filter will suppress PHP internal and external library
 * stuff.
 * 
 * This analyzer is based on the following metric set:
 * - http://www.aivosto.com/project/help/pm-oo-misc.html
 *
 * This analyzer is based on the following metric set:
 * - http://www.aivosto.com/project/help/pm-oo-misc.html
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Metrics
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
class PHP_Depend_Metrics_Hierarchy_Analyzer
       extends PHP_Depend_Metrics_AbstractAnalyzer
    implements PHP_Depend_Metrics_AnalyzerI,
               PHP_Depend_Metrics_FilterAwareI,
               PHP_Depend_Metrics_NodeAwareI,
               PHP_Depend_Metrics_ProjectAwareI
{
    /**
     * Type of this analyzer class.
     */
    const CLAZZ = __CLASS__;

    /**
     * Metrics provided by the analyzer implementation.
     */
    const M_NUMBER_OF_ABSTRACT_CLASSES = 'clsa',
          M_NUMBER_OF_CONCRETE_CLASSES = 'clsc',
          M_NUMBER_OF_ROOT_CLASSES     = 'roots',
          M_NUMBER_OF_LEAF_CLASSES     = 'leafs';

    /**
     * Number of all analyzed functions.
     *
     * @var integer $_fcs
     */
    private $fcs = 0;

    /**
     * Number of all analyzer methods.
     *
     * @var integer $_mts
     */
    private $mts = 0;

    /**
     * Number of all analyzed classes.
     *
     * @var integer $_cls
     */
    private $cls = 0;

    /**
     * Number of all analyzed abstract classes.
     *
     * @var integer $_clsa
     */
    private $clsa = 0;

    /**
     * Number of all analyzed interfaces.
     *
     * @var integer $_interfs
     */
    private $interfs = 0;

    /**
     * Number of all root classes within the analyzed source code.
     *
     * @var array(string=>boolean) $_roots
     */
    private $roots = array();

    /**
     * Number of all none leaf classes within the analyzed source code
     *
     * @var array(string=>boolean) $_noneLeafs
     */
    private $noneLeafs = array();

    /**
     * Hash with all calculated node metrics.
     *
     * <code>
     * array(
     *     '0375e305-885a-4e91-8b5c-e25bda005438'  =>  array(
     *         'loc'    =>  42,
     *         'ncloc'  =>  17,
     *         'cc'     =>  12
     *     ),
     *     'e60c22f0-1a63-4c40-893e-ed3b35b84d0b'  =>  array(
     *         'loc'    =>  42,
     *         'ncloc'  =>  17,
     *         'cc'     =>  12
     *     )
     * )
     * </code>
     *
     * @var array(string=>array) $_nodeMetrics
     */
    private $nodeMetrics = null;

    /**
     * Processes all {@link PHP_Depend_Code_Package} code nodes.
     *
     * @param PHP_Depend_Code_NodeIterator $packages The input package set.
     *
     * @return void
     * @see PHP_Depend_Metrics_AnalyzerI::analyze()
     */
    public function analyze(PHP_Depend_Code_NodeIterator $packages)
    {
        if ($this->nodeMetrics === null) {

            $this->fireStartAnalyzer();

            // Init node metrics
            $this->nodeMetrics = array();

            // Visit all nodes
            foreach ($packages as $package) {
                $package->accept($this);
            }

            $this->fireEndAnalyzer();
        }
    }

    /**
     * Provides the project summary metrics as an <b>array</b>.
     *
     * @return array(string=>mixed)
     */
    public function getProjectMetrics()
    {
        // Count none leaf classes
        $noneLeafs = count($this->noneLeafs);

        return array(
            self::M_NUMBER_OF_ABSTRACT_CLASSES  =>  $this->clsa,
            self::M_NUMBER_OF_CONCRETE_CLASSES  =>  $this->cls - $this->clsa,
            self::M_NUMBER_OF_ROOT_CLASSES      =>  count($this->roots),
            self::M_NUMBER_OF_LEAF_CLASSES      =>  $this->cls - $noneLeafs,
        );
    }

    /**
     * This method will return an <b>array</b> with all generated metric values
     * for the given <b>$node</b> instance. If there are no metrics for the
     * requested node, this method will return an empty <b>array</b>.
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return array(string=>mixed)
     */
    public function getNodeMetrics(PHP_Depend_Code_NodeI $node)
    {
        if (isset($this->nodeMetrics[$node->getUuid()])) {
            return $this->nodeMetrics[$node->getUuid()];
        }
        return array();
    }

    /**
     * Calculates metrics for the given <b>$class</b> instance.
     *
     * @param PHP_Depend_Code_Class $class The context class instance.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitClass()
     */
    public function visitClass(PHP_Depend_Code_Class $class)
    {
        if (false === $class->isUserDefined()) {
            return;
        }

        $this->fireStartClass($class);

        ++$this->cls;

        if ($class->isAbstract()) {
            ++$this->clsa;
        }

        $parentClass = $class->getParentClass();
        if ($parentClass !== null) {
            if ($parentClass->getParentClass() === null) {
                $this->roots[$parentClass->getUuid()] = true;
            }
            $this->noneLeafs[$parentClass->getUuid()] = true;
        }

        // Store node metric
        $this->nodeMetrics[$class->getUuid()] = array();

        foreach ($class->getMethods() as $method) {
            $method->accept($this);
        }
        foreach ($class->getProperties() as $property) {
            $property->accept($this);
        }

        $this->fireEndClass($class);
    }

    /**
     * Calculates metrics for the given <b>$function</b> instance.
     *
     * @param PHP_Depend_Code_Function $function The context function instance.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitFunction()
     */
    public function visitFunction(PHP_Depend_Code_Function $function)
    {
        $this->fireStartFunction($function);
        ++$this->fcs;
        $this->fireEndFunction($function);
    }

    /**
     * Calculates metrics for the given <b>$interface</b> instance.
     *
     * @param PHP_Depend_Code_Interface $interface The context interface instance.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitInterface()
     */
    public function visitInterface(PHP_Depend_Code_Interface $interface)
    {
        $this->fireStartInterface($interface);

        ++$this->interfs;

        foreach ($interface->getMethods() as $method) {
            $method->accept($this);
        }

        $this->fireEndInterface($interface);
    }

    /**
     * Visits a method node.
     *
     * @param PHP_Depend_Code_Class $method The method class node.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitMethod()
     */
    public function visitMethod(PHP_Depend_Code_Method $method)
    {
        $this->fireStartMethod($method);
        ++$this->mts;
        $this->fireEndMethod($method);
    }

    /**
     * Calculates metrics for the given <b>$package</b> instance.
     *
     * @param PHP_Depend_Code_Package $package The context package instance.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitPackage()
     */
    public function visitPackage(PHP_Depend_Code_Package $package)
    {
        $this->fireStartPackage($package);

        foreach ($package->getTypes() as $type) {
            $type->accept($this);
        }

        foreach ($package->getFunctions() as $function) {
            $function->accept($this);
        }

        $this->fireEndPackage($package);
    }
}
