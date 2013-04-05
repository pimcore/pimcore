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
 * Generates a chart with the aggregated metrics.
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
class PHP_Depend_Log_Jdepend_Chart
       extends PHP_Depend_Visitor_AbstractVisitor
    implements PHP_Depend_Log_CodeAwareI,
               PHP_Depend_Log_FileAwareI
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
     * The context source code.
     *
     * @var PHP_Depend_Code_NodeIterator $_code
     */
    private $code = null;

    /**
     * The context analyzer instance.
     *
     * @var PHP_Depend_Metrics_Dependency_Analyzer $analyzer
     */
    private $analyzer = null;

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
        return array(PHP_Depend_Metrics_Dependency_Analyzer::CLAZZ);
    }

    /**
     * Sets the context code nodes.
     *
     * @param PHP_Depend_Code_NodeIterator $code The code nodes.
     *
     * @return void
     */
    public function setCode(PHP_Depend_Code_NodeIterator $code)
    {
        $this->code = $code;
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
        if ($analyzer instanceof PHP_Depend_Metrics_Dependency_Analyzer) {
            $this->analyzer = $analyzer;

            return true;
        }
        return false;
    }

    /**
     * Closes the logger process and writes the output file.
     *
     * @return void
     * @throws PHP_Depend_Log_NoLogOutputException If the no log target exists.
     */
    public function close()
    {
        // Check for configured log file
        if ($this->logFile === null) {
            throw new PHP_Depend_Log_NoLogOutputException($this);
        }

        $bias = 0.1;

        $svg = new DOMDocument('1.0', 'UTF-8');
        $svg->load(dirname(__FILE__) . '/chart.svg');

        $bad   = $svg->getElementById('jdepend.bad');
        $good  = $svg->getElementById('jdepend.good');
        $layer = $svg->getElementById('jdepend.layer');

        $max = 0;
        $min = 0;

        $items = array();
        foreach ($this->code as $package) {

            if (!$package->isUserDefined()) {
                continue;
            }

            $metrics = $this->analyzer->getStats($package);

            if (count($metrics) === 0) {
                continue;
            }

            $size = $metrics['cc'] + $metrics['ac'];
            if ($size > $max) {
                $max = $size;
            } else if ($min === 0 || $size < $min) {
                $min = $size;
            }

            $items[] = array(
                'size'         =>  $size,
                'abstraction'  =>  $metrics['a'],
                'instability'  =>  $metrics['i'],
                'distance'     =>  $metrics['d'],
                'name'         =>  $package->getName()
            );
        }

        $diff = (($max - $min) / 10);

        // Sort items by size
        usort(
            $items,
            create_function('$a, $b', 'return ($a["size"] - $b["size"]);')
        );

        foreach ($items as $item) {
            if ($item['distance'] < $bias) {
                $ellipse = $good->cloneNode(true);
            } else {
                $ellipse = $bad->cloneNode(true);
            }
            $r = 15;
            if ($diff !== 0) {
                $r = 5 + (($item['size'] - $min) / $diff);
            }

            $a = $r / 15;
            $e = (50 - $r) + ($item['abstraction'] * 320);
            $f = (20 - $r + 190) - ($item['instability'] * 190);

            $transform = "matrix({$a}, 0, 0, {$a}, {$e}, {$f})";

            $ellipse->removeAttribute('xml:id');
            $ellipse->setAttribute('id', uniqid('pdepend_'));
            $ellipse->setAttribute('title', $item['name']);
            $ellipse->setAttribute('transform', $transform);

            $layer->appendChild($ellipse);
        }

        $bad->parentNode->removeChild($bad);
        $good->parentNode->removeChild($good);

        $temp  = PHP_Depend_Util_FileUtil::getSysTempDir();
        $temp .= '/' . uniqid('pdepend_') . '.svg';
        $svg->save($temp);

        PHP_Depend_Util_ImageConvert::convert($temp, $this->logFile);

        // Remove temp file
        unlink($temp);
    }

}
