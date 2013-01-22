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
 * Generates some class level based metrics. This analyzer is based on the
 * metrics specified in the following document.
 *
 * http://www.aivosto.com/project/help/pm-oo-misc.html
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
class PHP_Depend_Metrics_ClassLevel_Analyzer
       extends PHP_Depend_Metrics_AbstractAnalyzer
    implements PHP_Depend_Metrics_AggregateAnalyzerI,
               PHP_Depend_Metrics_FilterAwareI,
               PHP_Depend_Metrics_NodeAwareI
{
    /**
     * Type of this analyzer class.
     */
    const CLAZZ = __CLASS__;

    /**
     * Metrics provided by the analyzer implementation.
     */
    const M_IMPLEMENTED_INTERFACES       = 'impl',
          M_CLASS_INTERFACE_SIZE         = 'cis',
          M_CLASS_SIZE                   = 'csz',
          M_NUMBER_OF_PUBLIC_METHODS     = 'npm',
          M_PROPERTIES                   = 'vars',
          M_PROPERTIES_INHERIT           = 'varsi',
          M_PROPERTIES_NON_PRIVATE       = 'varsnp',
          M_WEIGHTED_METHODS             = 'wmc',
          M_WEIGHTED_METHODS_INHERIT     = 'wmci',
          M_WEIGHTED_METHODS_NON_PRIVATE = 'wmcnp';

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
     * @var array(string=>array)
     */
    private $nodeMetrics = null;

    /**
     * The internal used cyclomatic complexity analyzer.
     *
     * @var PHP_Depend_Metrics_CyclomaticComplexity_Analyzer
     */
    private $cyclomaticAnalyzer = null;

    /**
     * Processes all {@link PHP_Depend_Code_Package} code nodes.
     *
     * @param PHP_Depend_Code_NodeIterator $packages All code packages.
     *
     * @return void
     */
    public function analyze(PHP_Depend_Code_NodeIterator $packages)
    {
        if ($this->nodeMetrics === null) {
            // First check for the require cc analyzer
            if ($this->cyclomaticAnalyzer === null) {
                throw new RuntimeException('Missing required CC analyzer.');
            }

            $this->fireStartAnalyzer();

            $this->cyclomaticAnalyzer->analyze($packages);

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
     * This method must return an <b>array</b> of class names for required
     * analyzers.
     *
     * @return array(string)
     */
    public function getRequiredAnalyzers()
    {
        return array(
            PHP_Depend_Metrics_CyclomaticComplexity_Analyzer::CLAZZ
        );
    }

    /**
     * Adds a required sub analyzer.
     *
     * @param PHP_Depend_Metrics_AnalyzerI $analyzer The sub analyzer instance.
     *
     * @return void
     */
    public function addAnalyzer(PHP_Depend_Metrics_AnalyzerI $analyzer)
    {
        if ($analyzer instanceof PHP_Depend_Metrics_CyclomaticComplexity_Analyzer) {
            $this->cyclomaticAnalyzer = $analyzer;
        } else {
            throw new InvalidArgumentException('CC Analyzer required.');
        }
    }

    /**
     * This method will return an <b>array</b> with all generated metric values
     * for the given <b>$node</b>. If there are no metrics for the requested
     * node, this method will return an empty <b>array</b>.
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
     * Visits a class node.
     *
     * @param PHP_Depend_Code_Class $class The current class node.
     *
     * @return void
     * @see PHP_Depend_Visitor_AbstractVisitor::visitClass()
     */
    public function visitClass(PHP_Depend_Code_Class $class)
    {
        $this->fireStartClass($class);

        $impl  = $class->getInterfaces()->count();
        $varsi = $this->calculateVarsi($class);
        $wmci  = $this->calculateWmciForClass($class);

        $this->nodeMetrics[$class->getUuid()] = array(
            self::M_IMPLEMENTED_INTERFACES       => $impl,
            self::M_CLASS_INTERFACE_SIZE         => 0,
            self::M_CLASS_SIZE                   => 0,
            self::M_NUMBER_OF_PUBLIC_METHODS     => 0,
            self::M_PROPERTIES                   => 0,
            self::M_PROPERTIES_INHERIT           => $varsi,
            self::M_PROPERTIES_NON_PRIVATE       => 0,
            self::M_WEIGHTED_METHODS             => 0,
            self::M_WEIGHTED_METHODS_INHERIT     => $wmci,
            self::M_WEIGHTED_METHODS_NON_PRIVATE => 0
        );

        foreach ($class->getProperties() as $property) {
            $property->accept($this);
        }
        foreach ($class->getMethods() as $method) {
            $method->accept($this);
        }

        $this->fireEndClass($class);
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
        // Empty visit method, we don't want interface metrics
    }

    /**
     * Visits a trait node.
     *
     * @param PHP_Depend_Code_Trait $trait The current trait node.
     *
     * @return void
     * @since 1.0.0
     */
    public function visitTrait(PHP_Depend_Code_Trait $trait)
    {
        $this->fireStartTrait($trait);

        $wmci = $this->calculateWmciForTrait($trait);

        $this->nodeMetrics[$trait->getUuid()] = array(
            self::M_IMPLEMENTED_INTERFACES       => 0,
            self::M_CLASS_INTERFACE_SIZE         => 0,
            self::M_CLASS_SIZE                   => 0,
            self::M_NUMBER_OF_PUBLIC_METHODS     => 0,
            self::M_PROPERTIES                   => 0,
            self::M_PROPERTIES_INHERIT           => 0,
            self::M_PROPERTIES_NON_PRIVATE       => 0,
            self::M_WEIGHTED_METHODS             => 0,
            self::M_WEIGHTED_METHODS_INHERIT     => $wmci,
            self::M_WEIGHTED_METHODS_NON_PRIVATE => 0
        );

        foreach ($trait->getProperties() as $property) {
            $property->accept($this);
        }
        foreach ($trait->getMethods() as $method) {
            $method->accept($this);
        }

        $this->fireEndTrait($trait);
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

        // Get parent class uuid
        $uuid = $method->getParent()->getUuid();

        $ccn = $this->cyclomaticAnalyzer->getCcn2($method);

        // Increment Weighted Methods Per Class(WMC) value
        $this->nodeMetrics[$uuid][self::M_WEIGHTED_METHODS] += $ccn;
        // Increment Class Size(CSZ) value
        ++$this->nodeMetrics[$uuid][self::M_CLASS_SIZE];

        // Increment Non Private values
        if ($method->isPublic()) {
            ++$this->nodeMetrics[$uuid][self::M_NUMBER_OF_PUBLIC_METHODS];
            // Increment Non Private WMC value
            $this->nodeMetrics[$uuid][self::M_WEIGHTED_METHODS_NON_PRIVATE] += $ccn;
            // Increment Class Interface Size(CIS) value
            ++$this->nodeMetrics[$uuid][self::M_CLASS_INTERFACE_SIZE];
        }

        $this->fireEndMethod($method);
    }

    /**
     * Visits a property node.
     *
     * @param PHP_Depend_Code_Property $property The property class node.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitProperty()
     */
    public function visitProperty(PHP_Depend_Code_Property $property)
    {
        $this->fireStartProperty($property);

        // Get parent class uuid
        $uuid = $property->getDeclaringClass()->getUuid();

        // Increment VARS value
        ++$this->nodeMetrics[$uuid][self::M_PROPERTIES];
        // Increment Class Size(CSZ) value
        ++$this->nodeMetrics[$uuid][self::M_CLASS_SIZE];

        // Increment Non Private values
        if ($property->isPublic()) {
            // Increment Non Private VARS value
            ++$this->nodeMetrics[$uuid][self::M_PROPERTIES_NON_PRIVATE];
            // Increment Class Interface Size(CIS) value
            ++$this->nodeMetrics[$uuid][self::M_CLASS_INTERFACE_SIZE];
        }

        $this->fireEndProperty($property);
    }

    /**
     * Calculates the Variables Inheritance of a class metric, this method only
     * counts protected and public properties of parent classes.
     *
     * @param PHP_Depend_Code_Class $class The context class instance.
     *
     * @return integer
     */
    private function calculateVarsi(PHP_Depend_Code_Class $class)
    {
        // List of properties, this method only counts not overwritten properties
        $properties = array();
        // Collect all properties of the context class
        foreach ($class->getProperties() as $prop) {
            $properties[$prop->getName()] = true;
        }

        foreach ($class->getParentClasses() as $parent) {
            foreach ($parent->getProperties() as $prop) {
                if (!$prop->isPrivate() && !isset($properties[$prop->getName()])) {
                    $properties[$prop->getName()] = true;
                }
            }
        }
        return count($properties);
    }

    /**
     * Calculates the Weight Method Per Class metric, this method only counts
     * protected and public methods of parent classes.
     *
     * @param PHP_Depend_Code_Class $class The context class instance.
     *
     * @return integer
     */
    private function calculateWmciForClass(PHP_Depend_Code_Class $class)
    {
        $ccn = $this->calculateWmci($class);

        foreach ($class->getParentClasses() as $parent) {
            foreach ($parent->getMethods() as $method) {
                if ($method->isPrivate()) {
                    continue;
                }
                if (isset($ccn[($name = $method->getName())])) {
                    continue;
                }
                $ccn[$name] = $this->cyclomaticAnalyzer->getCcn2($method);
            }
        }

        return array_sum($ccn);
    }

    /**
     * Calculates the Weight Method Per Class metric for a trait.
     *
     * @param PHP_Depend_Code_Trait $trait The context trait instance.
     *
     * @return integer
     * @since 1.0.6
     */
    private function calculateWmciForTrait(PHP_Depend_Code_Trait $trait)
    {
        return array_sum($this->calculateWmci($trait));
    }

    /**
     * Calculates the Weight Method Per Class metric.
     *
     * @param PHP_Depend_Code_AbstractType $type The context type instance.
     *
     * @return integer[]
     * @since 1.0.6
     */
    private function calculateWmci(PHP_Depend_Code_AbstractType $type)
    {
        $ccn = array();

        foreach ($type->getMethods() as $method) {
            $ccn[$method->getName()] = $this->cyclomaticAnalyzer->getCcn2($method);
        }

        return $ccn;
    }
}
