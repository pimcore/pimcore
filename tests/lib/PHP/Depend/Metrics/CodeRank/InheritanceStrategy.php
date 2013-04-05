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
 * Collects class and package metrics based on inheritance.
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
class PHP_Depend_Metrics_CodeRank_InheritanceStrategy
       extends PHP_Depend_Visitor_AbstractVisitor
    implements PHP_Depend_Metrics_CodeRank_CodeRankStrategyI
{
    /**
     * All found nodes.
     *
     * @var array(string=>array) $_nodes
     */
    private $nodes = array();

    /**
     * Returns the collected nodes.
     *
     * @return array(string=>array)
     */
    public function getCollectedNodes()
    {
        return $this->nodes;
    }

    /**
     * Visits a code class object.
     *
     * @param PHP_Depend_Code_Class $class The context code class.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitClass()
     * @see PHP_Depend_Metrics_CodeRank_Analyzer::visitType()
     */
    public function visitClass(PHP_Depend_Code_Class $class)
    {
        $this->fireStartClass($class);
        $this->visitType($class);
        $this->fireEndClass($class);
    }

    /**
     * Visits a code interface object.
     *
     * @param PHP_Depend_Code_Interface $interface The context code interface.
     *
     * @return void
     * @see PHP_Depend_VisitorI::visitInterface()
     * @see PHP_Depend_Metrics_CodeRank_Analyzer::visitType()
     */
    public function visitInterface(PHP_Depend_Code_Interface $interface)
    {
        $this->fireStartInterface($interface);
        $this->visitType($interface);
        $this->fireEndInterface($interface);
    }

    /**
     * Generic visitor method for classes and interfaces. Both visit methods
     * delegate calls to this method.
     *
     * @param PHP_Depend_Code_AbstractClassOrInterface $type The context type
     *        instance.
     *
     * @return void
     */
    protected function visitType(PHP_Depend_Code_AbstractClassOrInterface $type)
    {
        $pkg = $type->getPackage();

        $this->initNode($pkg);
        $this->initNode($type);

        foreach ($type->getDependencies() as $dep) {

            $depPkg = $dep->getPackage();

            $this->initNode($dep);
            $this->initNode($depPkg);

            $this->nodes[$type->getUuid()]['in'][] = $dep->getUuid();
            $this->nodes[$dep->getUuid()]['out'][] = $type->getUuid();

            // No self references
            if ($pkg !== $depPkg) {
                $this->nodes[$pkg->getUuid()]['in'][]     = $depPkg->getUuid();
                $this->nodes[$depPkg->getUuid()]['out'][] = $pkg->getUuid();
            }
        }
    }

    /**
     * Initializes the temporary node container for the given <b>$node</b>.
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return void
     */
    protected function initNode(PHP_Depend_Code_NodeI $node)
    {
        if (!isset($this->nodes[$node->getUuid()])) {
            $this->nodes[$node->getUuid()] = array(
                'in'    =>  array(),
                'out'   =>  array(),
                'name'  =>  $node->getName(),
                'type'  =>  get_class($node)
            );
        }
    }

}
