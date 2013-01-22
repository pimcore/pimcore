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
 * This logger generates a summary xml document with aggregated project, class,
 * method and file metrics.
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
class PHP_Depend_Log_Summary_Xml
       extends PHP_Depend_Visitor_AbstractVisitor
    implements PHP_Depend_Log_CodeAwareI,
               PHP_Depend_Log_FileAwareI
{
    /**
     * The type of this class.
     */
    const CLAZZ = __CLASS__;

    /**
     * The log output file.
     *
     * @var string
     */
    private $logFile = null;

    /**
     * The raw {@link PHP_Depend_Code_Package} instances.
     *
     * @var PHP_Depend_Code_NodeIterator
     */
    protected $code = null;

    /**
     * Set of all analyzed files.
     *
     * @var PHP_Depend_Code_File[]
     */
    protected $fileSet = array();

    /**
     * List of all analyzers that implement the node aware interface
     * {@link PHP_Depend_Metrics_NodeAwareI}.
     *
     * @var PHP_Depend_Metrics_AnalyzerI[]
     */
    private $nodeAwareAnalyzers = array();

    /**
     * List of all analyzers that implement the node aware interface
     * {@link PHP_Depend_Metrics_ProjectAwareI}.
     *
     * @var PHP_Depend_Metrics_ProjectAwareI[]
     */
    private $projectAwareAnalyzers = array();

    /**
     * The internal used xml stack.
     *
     * @var DOMElement[]
     */
    private $xmlStack = array();

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
            'PHP_Depend_Metrics_NodeAwareI',
            'PHP_Depend_Metrics_ProjectAwareI'
        );
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
        $accepted = false;
        if ($analyzer instanceof PHP_Depend_Metrics_ProjectAwareI) {
            $this->projectAwareAnalyzers[] = $analyzer;

            $accepted = true;
        }
        if ($analyzer instanceof PHP_Depend_Metrics_NodeAwareI) {
            $this->nodeAwareAnalyzers[] = $analyzer;

            $accepted = true;
        }
        return $accepted;
    }

    /**
     * Closes the logger process and writes the output file.
     *
     * @return void
     * @throws PHP_Depend_Log_NoLogOutputException If the no log target exists.
     */
    public function close()
    {
        if ($this->logFile === null) {
            throw new PHP_Depend_Log_NoLogOutputException($this);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        $dom->formatOutput = true;

        $metrics = $dom->createElement('metrics');
        $metrics->setAttribute('generated', date('Y-m-d\TH:i:s'));
        $metrics->setAttribute('pdepend', '1.1.0');

        foreach ($this->getProjectMetrics() as $name => $value) {
            $metrics->setAttribute($name, $value);
        }

        array_push($this->xmlStack, $metrics);

        foreach ($this->code as $node) {
            $node->accept($this);
        }

        if (count($this->fileSet) > 0) {
            $filesXml = $dom->createElement('files');
            foreach ($this->fileSet as $file) {
                $fileXml = $dom->createElement('file');
                $fileXml->setAttribute('name', $file->getFileName());

                $this->writeNodeMetrics($fileXml, $file);

                $filesXml->appendChild($fileXml);
            }
            $metrics->insertBefore($filesXml, $metrics->firstChild);
        }

        $dom->appendChild($metrics);

        $dom->save($this->logFile);
    }

    /**
     * Returns an array with all collected project metrics.
     *
     * @return array(string=>mixed)
     * @since 0.9.10
     */
    private function getProjectMetrics()
    {
        $projectMetrics = array();
        foreach ($this->projectAwareAnalyzers as $analyzer) {
            $projectMetrics = array_merge(
                $projectMetrics,
                $analyzer->getProjectMetrics()
            );
        }
        ksort($projectMetrics);

        return $projectMetrics;
    }

    /**
     * Visits a class node.
     *
     * @param PHP_Depend_Code_Class $class The current class node.
     *
     * @return void
     */
    public function visitClass(PHP_Depend_Code_Class $class)
    {
        if (!$class->isUserDefined()) {
            return;
        }

        $xml = end($this->xmlStack);
        $doc = $xml->ownerDocument;

        $classXml = $doc->createElement('class');
        $classXml->setAttribute('name', $class->getName());

        $this->writeNodeMetrics($classXml, $class);
        $this->writeFileReference($classXml, $class->getSourceFile());

        $xml->appendChild($classXml);

        array_push($this->xmlStack, $classXml);

        foreach ($class->getMethods() as $method) {
            $method->accept($this);
        }
        foreach ($class->getProperties() as $property) {
            $property->accept($this);
        }

        array_pop($this->xmlStack);
    }

    /**
     * Visits a function node.
     *
     * @param PHP_Depend_Code_Function $function The current function node.
     *
     * @return void
     */
    public function visitFunction(PHP_Depend_Code_Function $function)
    {
        $xml = end($this->xmlStack);
        $doc = $xml->ownerDocument;

        $functionXml = $doc->createElement('function');
        $functionXml->setAttribute('name', $function->getName());

        $this->writeNodeMetrics($functionXml, $function);
        $this->writeFileReference($functionXml, $function->getSourceFile());

        $xml->appendChild($functionXml);
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
        // Empty implementation, because we don't want interface methods.
    }

    /**
     * Visits a method node.
     *
     * @param PHP_Depend_Code_Method $method The method class node.
     *
     * @return void
     */
    public function visitMethod(PHP_Depend_Code_Method $method)
    {
        $xml = end($this->xmlStack);
        $doc = $xml->ownerDocument;

        $methodXml = $doc->createElement('method');
        $methodXml->setAttribute('name', $method->getName());

        $this->writeNodeMetrics($methodXml, $method);

        $xml->appendChild($methodXml);
    }

    /**
     * Visits a package node.
     *
     * @param PHP_Depend_Code_Class $package The package class node.
     *
     * @return void
     */
    public function visitPackage(PHP_Depend_Code_Package $package)
    {
        $xml = end($this->xmlStack);
        $doc = $xml->ownerDocument;

        $packageXml = $doc->createElement('package');
        $packageXml->setAttribute('name', $package->getName());

        $this->writeNodeMetrics($packageXml, $package);

        array_push($this->xmlStack, $packageXml);

        foreach ($package->getTypes() as $type) {
            $type->accept($this);
        }
        foreach ($package->getFunctions() as $function) {
            $function->accept($this);
        }

        array_pop($this->xmlStack);

        if ($packageXml->firstChild === null) {
            return;
        }

        $xml->appendChild($packageXml);
    }

    /**
     * Aggregates all metrics for the given <b>$node</b> instance and adds them
     * to the <b>DOMElement</b>
     *
     * @param DOMElement            $xml  DOM Element that represents <b>$node</b>.
     * @param PHP_Depend_Code_NodeI $node The context code node instance.
     *
     * @return void
     */
    protected function writeNodeMetrics(DOMElement $xml, PHP_Depend_Code_NodeI $node)
    {
        $metrics = array();
        foreach ($this->nodeAwareAnalyzers as $analyzer) {
            $metrics = array_merge($metrics, $analyzer->getNodeMetrics($node));
        }
        ksort($metrics);

        foreach ($metrics as $name => $value) {
            $xml->setAttribute($name, $value);
        }
    }

    /**
     * Appends a file reference element to the given <b>$xml</b> element.
     *
     * <code>
     *   <class name="PHP_Depend">
     *     <file name="PHP/Depend.php" />
     *   </class>
     * </code>
     *
     * @param DOMElement           $xml  The parent xml element.
     * @param PHP_Depend_Code_File $file The code file instance.
     *
     * @return void
     */
    protected function writeFileReference(
        DOMElement $xml,
        PHP_Depend_Code_File $file = null
    ) {
        if (in_array($file, $this->fileSet, true) === false) {
            $this->fileSet[] = $file;
        }

        $fileXml = $xml->ownerDocument->createElement('file');
        $fileXml->setAttribute('name', $file->getFileName());

        $xml->appendChild($fileXml);
    }
}
