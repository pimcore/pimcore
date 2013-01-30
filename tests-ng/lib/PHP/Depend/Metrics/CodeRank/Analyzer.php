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
 * Calculates the code ranke metric for classes and packages.
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
class PHP_Depend_Metrics_CodeRank_Analyzer
       extends PHP_Depend_Metrics_AbstractAnalyzer
    implements PHP_Depend_Metrics_AnalyzerI,
               PHP_Depend_Metrics_NodeAwareI
{
    /**
     * Type of this analyzer class.
     */
    const CLAZZ = __CLASS__;

    /**
     * Metrics provided by the analyzer implementation.
     */
    const M_CODE_RANK         = 'cr',
          M_REVERSE_CODE_RANK = 'rcr';

    /**
     * The used damping factor.
     */
    const DAMPING_FACTOR = 0.85;

    /**
     * Number of loops for the code range calculation.
     */
    const ALGORITHM_LOOPS = 25;

    /**
     * Option key for the code rank mode.
     */
    const STRATEGY_OPTION = 'coderank-mode';

    /**
     * All found nodes.
     *
     * @var array(string=>array) $_nodes
     */
    private $nodes = array();

    /**
     * List of node collect strategies.
     *
     * @var array(PHP_Depend_Metrics_CodeRank_CodeRankStrategyI) $_strategies
     */
    private $strategies = array();

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
     * @param PHP_Depend_Code_NodeIterator $packages All code packages.
     *
     * @return void
     */
    public function analyze(PHP_Depend_Code_NodeIterator $packages)
    {
        if ($this->nodeMetrics === null) {

            $this->fireStartAnalyzer();

            $factory = new PHP_Depend_Metrics_CodeRank_StrategyFactory();
            if (isset($this->options[self::STRATEGY_OPTION])) {
                foreach ($this->options[self::STRATEGY_OPTION] as $identifier) {
                    $this->strategies[] = $factory->createStrategy($identifier);
                }
            } else {
                $this->strategies[] = $factory->createDefaultStrategy();
            }

            // Register all listeners
            foreach ($this->getVisitListeners() as $listener) {
                foreach ($this->strategies as $strategy) {
                    $strategy->addVisitListener($listener);
                }
            }

            // First traverse package tree
            foreach ($packages as $package) {
                // Traverse all strategies
                foreach ($this->strategies as $strategy) {
                    $package->accept($strategy);
                }
            }

            // Collect all nodes
            foreach ($this->strategies as $strategy) {
                $collected    = $strategy->getCollectedNodes();
                $this->nodes = array_merge_recursive($collected, $this->nodes);
            }

            // Init node metrics
            $this->nodeMetrics = array();

            // Calculate code rank metrics
            $this->buildCodeRankMetrics();

            $this->fireEndAnalyzer();
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
        if (isset($this->nodeMetrics[$node->getUuid()])) {
            return $this->nodeMetrics[$node->getUuid()];
        }
        return array();
    }

    /**
     * Generates the forward and reverse code rank for the given <b>$nodes</b>.
     *
     * @return void
     */
    protected function buildCodeRankMetrics()
    {
        foreach (array_keys($this->nodes) as $uuid) {
            $this->nodeMetrics[$uuid] = array(
                self::M_CODE_RANK          =>  0,
                self::M_REVERSE_CODE_RANK  =>  0
            );
        }
        foreach ($this->computeCodeRank('out', 'in') as $uuid => $rank) {
            $this->nodeMetrics[$uuid][self::M_CODE_RANK] = $rank;
        }
        foreach ($this->computeCodeRank('in', 'out') as $uuid => $rank) {
            $this->nodeMetrics[$uuid][self::M_REVERSE_CODE_RANK] = $rank;
        }
    }

    /**
     * Calculates the code rank for the given <b>$nodes</b> set.
     *
     * @param string $id1 Identifier for the incoming edges.
     * @param string $id2 Identifier for the outgoing edges.
     *
     * @return array(string=>float)
     */
    protected function computeCodeRank($id1, $id2)
    {
        $dampingFactory = self::DAMPING_FACTOR;

        $ranks = array();

        foreach (array_keys($this->nodes) as $name) {
            $ranks[$name] = 1;
        }

        for ($i = 0; $i < self::ALGORITHM_LOOPS; $i++) {
            foreach ($this->nodes as $name => $info) {
                $rank = 0;
                foreach ($info[$id1] as $ref) {
                    $previousRank = $ranks[$ref];
                    $refCount     = count($this->nodes[$ref][$id2]);

                    $rank += ($previousRank / $refCount);
                }
                $ranks[$name] = ((1 - $dampingFactory)) + $dampingFactory * $rank;
            }
        }
        return $ranks;
    }
}
