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
 * This analyzer collects different count metrics for code artifacts like
 * classes, methods, functions or packages.
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
class PHP_Depend_Metrics_NodeCount_Analyzer
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
    const M_NUMBER_OF_PACKAGES   = 'nop',
          M_NUMBER_OF_CLASSES    = 'noc',
          M_NUMBER_OF_INTERFACES = 'noi',
          M_NUMBER_OF_METHODS    = 'nom',
          M_NUMBER_OF_FUNCTIONS  = 'nof';

    /**
     * Number Of Packages.
     *
     * @var integer $_nop
     */
    private $nop = 0;

    /**
     * Number Of Classes.
     *
     * @var integer $_noc
     */
    private $noc = 0;

    /**
     * Number Of Interfaces.
     *
     * @var integer $_noi
     */
    private $noi = 0;

    /**
     * Number Of Methods.
     *
     * @var integer $_nom
     */
    private $nom = 0;

    /**
     * Number Of Functions.
     *
     * @var integer $_nof
     */
    private $nof = 0;

    /**
     * Collected node metrics
     *
     * @var array(string=>array) $_nodeMetrics
     */
    private $nodeMetrics = null;

    /**
     * This method will return an <b>array</b> with all generated metric values
     * for the given <b>$node</b> instance. If there are no metrics for the
     * requested node, this method will return an empty <b>array</b>.
     *
     * <code>
     * array(
     *     'noc'  =>  23,
     *     'nom'  =>  17,
     *     'nof'  =>  42
     * )
     * </code>
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return array(string=>mixed)
     */
    public function getNodeMetrics(PHP_Depend_Code_NodeI $node)
    {
        $metrics = array();
        if (isset($this->nodeMetrics[$node->getUuid()])) {
            $metrics = $this->nodeMetrics[$node->getUuid()];
        }
        return $metrics;
    }

    /**
     * Provides the project summary as an <b>array</b>.
     *
     * <code>
     * array(
     *     'nop'  =>  23,
     *     'noc'  =>  17,
     *     'noi'  =>  23,
     *     'nom'  =>  42,
     *     'nof'  =>  17
     * )
     * </code>
     *
     * @return array(string=>mixed)
     */
    public function getProjectMetrics()
    {
        return array(
            self::M_NUMBER_OF_PACKAGES    =>  $this->nop,
            self::M_NUMBER_OF_CLASSES     =>  $this->noc,
            self::M_NUMBER_OF_INTERFACES  =>  $this->noi,
            self::M_NUMBER_OF_METHODS     =>  $this->nom,
            self::M_NUMBER_OF_FUNCTIONS   =>  $this->nof
        );
    }

    /**
     * Processes all {@link PHP_Depend_Code_Package} code nodes.
     *
     * @param PHP_Depend_Code_NodeIterator $packages All code packages.
     *
     * @return void
     */
    public function analyze(PHP_Depend_Code_NodeIterator $packages)
    {
        // Check for previous run
        if ($this->nodeMetrics === null) {

            $this->fireStartAnalyzer();

            // Init node metrics
            $this->nodeMetrics = array();

            // Process all packages
            foreach ($packages as $package) {
                $package->accept($this);
            }

            $this->fireEndAnalyzer();
        }
    }

    /**
     * Visits a class node.
     *
     * @param PHP_Depend_Code_Class $class The current class node.
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

        // Update global class count
        ++$this->noc;

        // Update parent package
        $packageUUID = $class->getPackage()->getUuid();
        ++$this->nodeMetrics[$packageUUID][self::M_NUMBER_OF_CLASSES];

        $this->nodeMetrics[$class->getUuid()] = array(
            self::M_NUMBER_OF_METHODS  =>  0
        );

        foreach ($class->getMethods() as $method) {
            $method->accept($this);
        }

        $this->fireEndClass($class);
    }

    /**
     * Visits a function node.
     *
     * @param PHP_Depend_Code_Function $function The current function node.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitFunction()
     */
    public function visitFunction(PHP_Depend_Code_Function $function)
    {
        $this->fireStartFunction($function);

        // Update global function count
        ++$this->nof;

        // Update parent package
        $packageUUID = $function->getPackage()->getUuid();
        ++$this->nodeMetrics[$packageUUID][self::M_NUMBER_OF_FUNCTIONS];

        $this->fireEndFunction($function);
    }

    /**
     * Visits a code interface object.
     *
     * @param PHP_Depend_Code_Interface $interface The context code interface.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitInterface()
     */
    public function visitInterface(PHP_Depend_Code_Interface $interface)
    {
        if (false === $interface->isUserDefined()) {
            return;
        }

        $this->fireStartInterface($interface);

        // Update global class count
        ++$this->noi;

        // Update parent package
        $packageUUID = $interface->getPackage()->getUuid();
        ++$this->nodeMetrics[$packageUUID][self::M_NUMBER_OF_INTERFACES];

        $this->nodeMetrics[$interface->getUuid()] = array(
            self::M_NUMBER_OF_METHODS  =>  0
        );

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

        // Update global method count
        ++$this->nom;

        $parent = $method->getParent();

        // Update parent class or interface
        $parentUUID = $parent->getUuid();
        ++$this->nodeMetrics[$parentUUID][self::M_NUMBER_OF_METHODS];

        // Update parent package
        $packageUUID = $parent->getPackage()->getUuid();
        ++$this->nodeMetrics[$packageUUID][self::M_NUMBER_OF_METHODS];

        $this->fireEndMethod($method);
    }

    /**
     * Visits a package node.
     *
     * @param PHP_Depend_Code_Class $package The package class node.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitPackage()
     */
    public function visitPackage(PHP_Depend_Code_Package $package)
    {
        $this->fireStartPackage($package);

        // Update package count
        ++$this->nop;

        $this->nodeMetrics[$package->getUuid()] = array(
            self::M_NUMBER_OF_CLASSES     =>  0,
            self::M_NUMBER_OF_INTERFACES  =>  0,
            self::M_NUMBER_OF_METHODS     =>  0,
            self::M_NUMBER_OF_FUNCTIONS   =>  0
        );


        foreach ($package->getClasses() as $class) {
            $class->accept($this);
        }
        foreach ($package->getInterfaces() as $interface) {
            $interface->accept($this);
        }
        foreach ($package->getFunctions() as $function) {
            $function->accept($this);
        }

        $this->fireEndPackage($package);
    }
}
