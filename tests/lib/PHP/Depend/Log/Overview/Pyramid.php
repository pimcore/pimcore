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
 * @subpackage Log
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * This logger generates a system overview pyramid, as described in the book
 * <b>Object-Oriented Metrics in Practice</b>.
 *
 * http://www.springer.com/computer/programming/book/978-3-540-24429-5
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Log
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
class PHP_Depend_Log_Overview_Pyramid implements PHP_Depend_Log_FileAwareI
{
    /**
     * The type of this class.
     */
    const CLAZZ = __CLASS__;

    /**
     * The output file name.
     *
     * @var string $_logFile
     */
    private $logFile = null;

    /**
     * The used coupling analyzer.
     *
     * @var PHP_Depend_Metrics_Coupling_Analyzer $_coupling
     */
    private $coupling = null;

    /**
     * The used cyclomatic complexity analyzer.
     *
     * @var PHP_Depend_Metrics_CyclomaticComplexity_Analyzer $_cyclomaticComplexity
     */
    private $cyclomaticComplexity = null;

    /**
     * The used inheritance analyzer.
     *
     * @var PHP_Depend_Metrics_Inheritance_Analyzer $_inheritance
     */
    private $inheritance = null;

    /**
     * The used node count analyzer.
     *
     * @var PHP_Depend_Metrics_NodeCount_Analyzer $_nodeCount
     */
    private $nodeCount = null;

    /**
     * The used node loc analyzer.
     *
     * @var PHP_Depend_Metrics_NodeLoc_Analyzer $_nodeLoc
     */
    private $nodeLoc = null;

    /**
     * Holds defined thresholds for the computed proportions. This set is based
     * on java thresholds, we should find better values for php projects.
     *
     * @var array(string => array) $_thresholds
     */
    private $thresholds = array(
        'cyclo-loc'     =>  array(0.16, 0.20, 0.24),
        'loc-nom'       =>  array(7, 10, 13),
        'nom-noc'       =>  array(4, 7, 10),
        'noc-nop'       =>  array(6, 17, 26),
        'calls-nom'     =>  array(2.01, 2.62, 3.2),
        'fanout-calls'  =>  array(0.56, 0.62, 0.68),
        'andc'          =>  array(0.25, 0.41, 0.57),
        'ahh'           =>  array(0.09, 0.21, 0.32)
    );

    /**
     * Sets the output log file.
     *
     * @param string $logFile The output log file.
     *
     * @return void
     */
    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Returns an <b>array</b> with accepted analyzer types. These types can be
     * concrete analyzer classes or one of the descriptive analyzer interfaces.
     *
     * @return array(string)
     */
    public function getAcceptedAnalyzers()
    {
        return array(
            'PHP_Depend_Metrics_Coupling_Analyzer',
            'PHP_Depend_Metrics_CyclomaticComplexity_Analyzer',
            'PHP_Depend_Metrics_Inheritance_Analyzer',
            'PHP_Depend_Metrics_NodeCount_Analyzer',
            'PHP_Depend_Metrics_NodeLoc_Analyzer'
        );
    }

    /**
     * Adds an analyzer to log. If this logger accepts the given analyzer it
     * with return <b>true</b>, otherwise the return value is <b>false</b>.
     *
     * @param PHP_Depend_Metrics_AnalyzerI $analyzer The analyzer to log.
     *
     * @return boolean
     */
    public function log(PHP_Depend_Metrics_AnalyzerI $analyzer)
    {
        if ($analyzer instanceof PHP_Depend_Metrics_CyclomaticComplexity_Analyzer) {
            $this->cyclomaticComplexity = $analyzer;
        } else if ($analyzer instanceof PHP_Depend_Metrics_Coupling_Analyzer) {
            $this->coupling = $analyzer;
        } else if ($analyzer instanceof PHP_Depend_Metrics_Inheritance_Analyzer) {
            $this->inheritance = $analyzer;
        } else if ($analyzer instanceof PHP_Depend_Metrics_NodeCount_Analyzer) {
            $this->nodeCount = $analyzer;
        } else if ($analyzer instanceof PHP_Depend_Metrics_NodeLoc_Analyzer) {
            $this->nodeLoc = $analyzer;
        } else {
            return false;
        }
        return true;
    }

    /**
     * Closes the logger process and writes the output file.
     *
     * @return void
     */
    public function close()
    {
        // Check for configured log file
        if ($this->logFile === null) {
            throw new PHP_Depend_Log_NoLogOutputException($this);
        }

        $metrics     = $this->collectMetrics();
        $proportions = $this->computeProportions($metrics);

        $svg = new DOMDocument('1.0', 'UTF-8');
        $svg->load(dirname(__FILE__) . '/pyramid.svg');

        $items = array_merge($metrics, $proportions);
        foreach ($items as $name => $value) {
            $svg->getElementById("pdepend.{$name}")->nodeValue = $value;

            if (($threshold = $this->computeThreshold($name, $value)) === null) {
                continue;
            }
            if (($color = $svg->getElementById("threshold.{$threshold}")) === null) {
                continue;
            }
            if (($rect = $svg->getElementById("rect.{$name}")) === null) {
                continue;
            }
            preg_match('/fill:(#[^;"]+)/', $color->getAttribute('style'), $match);

            $style = $rect->getAttribute('style');
            $style = preg_replace('/fill:#[^;"]+/', "fill:{$match[1]}", $style);
            $rect->setAttribute('style', $style);
        }

        $temp  = PHP_Depend_Util_FileUtil::getSysTempDir();
        $temp .= '/' . uniqid('pdepend_') . '.svg';
        $svg->save($temp);

        PHP_Depend_Util_ImageConvert::convert($temp, $this->logFile);

        // Remove temp file
        unlink($temp);
    }

    /**
     * Computes the threshold (low, average, high) for the given value and metric.
     * If no threshold is defined for the given name, this method will return
     * <b>null</b>.
     *
     * @param string $name  The metric/field identfier.
     * @param mixed  $value The metric/field value.
     *
     * @return string
     */
    private function computeThreshold($name, $value)
    {
        if (!isset($this->thresholds[$name])) {
            return null;
        }

        $threshold = $this->thresholds[$name];
        if ($value <= $threshold[0]) {
            return 'low';
        } else if ($value >= $threshold[2]) {
            return 'high';
        } else {
            $low = $value - $threshold[0];
            $avg = $threshold[1] - $value;

            if ($low < $avg) {
                return 'low';
            }
        }
        return 'average';
    }

    /**
     * Computes the proportions between the given metrics.
     *
     * @param array $metrics The aggregated project metrics.
     *
     * @return array(string => float)
     */
    private function computeProportions(array $metrics)
    {
        $orders = array(
            array('cyclo', 'loc', 'nom', 'noc', 'nop'),
            array('fanout', 'calls', 'nom')
        );

        $proportions = array();
        foreach ($orders as $names) {
            for ($i = 1, $c = count($names); $i < $c; ++$i) {
                $value1 = $metrics[$names[$i]];
                $value2 = $metrics[$names[$i - 1]];

                $identifier = "{$names[$i - 1]}-{$names[$i]}";

                $proportions[$identifier] = 0;
                if ($value1 > 0) {
                    $proportions[$identifier] = round($value2 / $value1, 3);
                }
            }
        }

        return $proportions;
    }

    /**
     * Aggregates the required metrics from the registered analyzers.
     *
     * @return array(string => mixed)
     * @throws RuntimeException If one of the required analyzers isn't set.
     */
    private function collectMetrics()
    {
        if ($this->coupling === null) {
            throw new RuntimeException('Missing Coupling analyzer.');
        }
        if ($this->cyclomaticComplexity === null) {
            throw new RuntimeException('Missing Cyclomatic Complexity analyzer.');
        }
        if ($this->inheritance === null) {
            throw new RuntimeException('Missing Inheritance analyzer.');
        }
        if ($this->nodeCount === null) {
            throw new RuntimeException('Missing Node Count analyzer.');
        }
        if ($this->nodeLoc === null) {
            throw new RuntimeException('Missing Node LOC analyzer.');
        }

        $coupling    = $this->coupling->getProjectMetrics();
        $cyclomatic  = $this->cyclomaticComplexity->getProjectMetrics();
        $inheritance = $this->inheritance->getProjectMetrics();
        $nodeCount   = $this->nodeCount->getProjectMetrics();
        $nodeLoc     = $this->nodeLoc->getProjectMetrics();

        return array(
            'cyclo'   =>  $cyclomatic['ccn2'],
            'loc'     =>  $nodeLoc['eloc'],
            'nom'     =>  ($nodeCount['nom'] + $nodeCount['nof']),
            'noc'     =>  $nodeCount['noc'],
            'nop'     =>  $nodeCount['nop'],
            'ahh'     =>  round($inheritance['ahh'], 3),
            'andc'    =>  round($inheritance['andc'], 3),
            'fanout'  =>  $coupling['fanout'],
            'calls'   =>  $coupling['calls']
        );
    }
}
