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
 * @subpackage Metrics_CrapIndex
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * This analyzer calculates the C.R.A.P. index for methods an functions when a
 * clover coverage report was supplied. This report can be supplied by using the
 * command line option <b>--coverage-report=</b>.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Metrics_CrapIndex
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
class PHP_Depend_Metrics_CrapIndex_Analyzer
       extends PHP_Depend_Metrics_AbstractAnalyzer
    implements PHP_Depend_Metrics_AggregateAnalyzerI,
               PHP_Depend_Metrics_NodeAwareI
{
    /**
     * Type of this analyzer class.
     */
    const CLAZZ = __CLASS__;

    /**
     * Metrics provided by the analyzer implementation.
     */
    const M_CRAP_INDEX = 'crap';

    /**
     * The report option name.
     */
    const REPORT_OPTION = 'coverage-report';

    /**
     * Calculated crap metrics.
     *
     * @var array(string=>array)
     */
    private $metrics = null;

    /**
     * The coverage report instance representing the supplied coverage report
     * file.
     *
     * @var PHP_Depend_Util_Coverage_Report
     */
    private $report = null;

    /**
     *
     * @var PHP_Depend_Metrics_CyclomaticComplexity_Analyzer
     */
    private $ccnAnalyzer = array();

    /**
     * Returns <b>true</b> when this analyzer is enabled.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return isset($this->options[self::REPORT_OPTION]);
    }

    /**
     * Returns the calculated metrics for the given node or an empty <b>array</b>
     * when no metrics exist for the given node.
     *
     * @param PHP_Depend_Code_NodeI $node The context source node instance.
     *
     * @return array(string=>float)
     */
    public function getNodeMetrics(PHP_Depend_Code_NodeI $node)
    {
        if (isset($this->metrics[$node->getUuid()])) {
            return $this->metrics[$node->getUuid()];
        }
        return array();
    }

    /**
     * Returns an array with analyzer class names that are required by the crap
     * index analyzers.
     *
     * @return array(string)
     */
    public function getRequiredAnalyzers()
    {
        return array(PHP_Depend_Metrics_CyclomaticComplexity_Analyzer::CLAZZ);
    }

    /**
     * Adds an analyzer that this analyzer depends on.
     *
     * @param PHP_Depend_Metrics_AnalyzerI $analyzer An analyzer this analyzer
     *        depends on.
     *
     * @return void
     */
    public function addAnalyzer(PHP_Depend_Metrics_AnalyzerI $analyzer)
    {
        $this->ccnAnalyzer = $analyzer;
    }

    /**
     * Performs the crap index analysis.
     *
     * @param PHP_Depend_Code_NodeIterator $packages The context source tree.
     *
     * @return void
     */
    public function analyze(PHP_Depend_Code_NodeIterator $packages)
    {
        if ($this->isEnabled() && $this->metrics === null) {
            $this->doAnalyze($packages);
        }
    }

    /**
     * Performs the crap index analysis.
     *
     * @param PHP_Depend_Code_NodeIterator $packages The context source tree.
     *
     * @return void
     */
    private function doAnalyze(PHP_Depend_Code_NodeIterator $packages)
    {
        $this->metrics = array();
        
        $this->ccnAnalyzer->analyze($packages);

        $this->fireStartAnalyzer();

        foreach ($packages as $package) {
            $package->accept($this);
        }

        $this->fireEndAnalyzer();
    }

    /**
     * Visits the given method.
     *
     * @param PHP_Depend_Code_Method $method The context method.
     *
     * @return void
     */
    public function visitMethod(PHP_Depend_Code_Method $method)
    {
        if ($method->isAbstract() === false) {
            $this->visitCallable($method);
        }
    }

    /**
     * Visits the given function.
     *
     * @param PHP_Depend_Code_Function $function The context function.
     *
     * @return void
     */
    public function visitFunction(PHP_Depend_Code_Function $function)
    {
        $this->visitCallable($function);
    }

    /**
     * Visits the given callable instance.
     *
     * @param PHP_Depend_Code_AbstractCallable $callable The context callable.
     *
     * @return void
     */
    private function visitCallable(PHP_Depend_Code_AbstractCallable $callable)
    {
        $this->metrics[$callable->getUuid()] = array(
            self::M_CRAP_INDEX => $this->calculateCrapIndex($callable)
        );
    }

    /**
     * Calculates the crap index for the given callable.
     *
     * @param PHP_Depend_Code_AbstractCallable $callable The context callable.
     *
     * @return float
     */
    private function calculateCrapIndex(PHP_Depend_Code_AbstractCallable $callable)
    {
        $report = $this->createOrReturnCoverageReport();

        $complexity = $this->ccnAnalyzer->getCcn2($callable);
        $coverage   = $report->getCoverage($callable);

        if ($coverage == 0) {
            return pow($complexity, 2) + $complexity;
        } else if ($coverage > 99.5) {
            return $complexity;
        }
        return pow($complexity, 2) * pow(1 - $coverage / 100, 3) + $complexity;
    }

    /**
     * Returns a previously created report instance or creates a new report
     * instance.
     *
     * @return PHP_Depend_Util_Coverage_Report
     */
    private function createOrReturnCoverageReport()
    {
        if ($this->report === null) {
            $this->report = $this->createCoverageReport();
        }
        return $this->report;
    }

    /**
     * Creates a new coverage report instance.
     *
     * @return PHP_Depend_Util_Coverage_Report
     */
    private function createCoverageReport()
    {
        $factory = new PHP_Depend_Util_Coverage_Factory();
        return $factory->create($this->options['coverage-report']);
    }
}
