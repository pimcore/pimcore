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
 * This analyzer collects different lines of code metrics.
 *
 * It collects the total Lines Of Code(<b>loc</b>), the None Comment Lines Of
 * Code(<b>ncloc</b>), the Comment Lines Of Code(<b>cloc</b>) and a approximated
 * Executable Lines Of Code(<b>eloc</b>) for files, classes, interfaces,
 * methods, properties and function.
 *
 * The current implementation has a limitation, that affects inline comments.
 * The following code will suppress one line of code.
 *
 * <code>
 * function foo() {
 *     foobar(); // Bad behaviour...
 * }
 * </code>
 *
 * The same rule applies to class methods. mapi, <b>PLEASE, FIX THIS ISSUE.</b>
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
class PHP_Depend_Metrics_NodeLoc_Analyzer
       extends PHP_Depend_Metrics_AbstractCachingAnalyzer
    implements PHP_Depend_Metrics_AnalyzerI,
               PHP_Depend_Metrics_NodeAwareI,
               PHP_Depend_Metrics_FilterAwareI,
               PHP_Depend_Metrics_ProjectAwareI
{
    /**
     * Type of this analyzer class.
     */
    const CLAZZ = __CLASS__;

    /**
     * Metrics provided by the analyzer implementation.
     */
    const M_LINES_OF_CODE             = 'loc',
          M_COMMENT_LINES_OF_CODE     = 'cloc',
          M_EXECUTABLE_LINES_OF_CODE  = 'eloc',
          M_LOGICAL_LINES_OF_CODE     = 'lloc',
          M_NON_COMMENT_LINES_OF_CODE = 'ncloc';

    /**
     * Collected project metrics.
     *
     * @var array(string=>integer)
     */
    private $projectMetrics = array(
        self::M_LINES_OF_CODE              =>  0,
        self::M_COMMENT_LINES_OF_CODE      =>  0,
        self::M_EXECUTABLE_LINES_OF_CODE   =>  0,
        self::M_LOGICAL_LINES_OF_CODE      =>  0,
        self::M_NON_COMMENT_LINES_OF_CODE  =>  0
    );

    /**
     * Executable lines of code in a class. The method calculation increases
     * this property with each method's ELOC value.
     *
     * @var integer
     * @since 0.9.12
     */
    private $classExecutableLines = 0;

    /**
     * Logical lines of code in a class. The method calculation increases this
     * property with each method's LLOC value.
     *
     * @var integer
     * @since 0.9.13
     */
    private $classLogicalLines = 0;

    /**
     * This method will return an <b>array</b> with all generated metric values
     * for the given <b>$node</b> instance. If there are no metrics for the
     * requested node, this method will return an empty <b>array</b>.
     *
     * <code>
     * array(
     *     'loc'    =>  23,
     *     'cloc'   =>  17,
     *     'eloc'   =>  17,
     *     'ncloc'  =>  42
     * )
     * </code>
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return array
     */
    public function getNodeMetrics(PHP_Depend_Code_NodeI $node)
    {
        $metrics = array();
        if (isset($this->metrics[$node->getUuid()])) {
            $metrics = $this->metrics[$node->getUuid()];
        }
        return $metrics;
    }

    /**
     * Provides the project summary as an <b>array</b>.
     *
     * <code>
     * array(
     *     'loc'    =>  23,
     *     'cloc'   =>  17,
     *     'ncloc'  =>  42
     * )
     * </code>
     *
     * @return array
     */
    public function getProjectMetrics()
    {
        return $this->projectMetrics;
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
        if ($this->metrics === null) {
            $this->loadCache();
            $this->fireStartAnalyzer();

            $this->metrics = array();
            foreach ($packages as $package) {
                $package->accept($this);
            }

            $this->fireEndAnalyzer();
            $this->unloadCache();
        }
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

        $class->getSourceFile()->accept($this);

        $this->classExecutableLines = 0;
        $this->classLogicalLines    = 0;

        foreach ($class->getMethods() as $method) {
            $method->accept($this);
        }

        if ($this->restoreFromCache($class)) {
            return $this->fireEndClass($class);
        }

        list($cloc) = $this->linesOfCode($class->getTokens(), true);

        $loc   = $class->getEndLine() - $class->getStartLine() + 1;
        $ncloc = $loc - $cloc;

        $this->metrics[$class->getUuid()] = array(
            self::M_LINES_OF_CODE              =>  $loc,
            self::M_COMMENT_LINES_OF_CODE      =>  $cloc,
            self::M_EXECUTABLE_LINES_OF_CODE   =>  $this->classExecutableLines,
            self::M_LOGICAL_LINES_OF_CODE      =>  $this->classLogicalLines,
            self::M_NON_COMMENT_LINES_OF_CODE  =>  $ncloc,
        );

        $this->fireEndClass($class);
    }

    /**
     * Visits a file node.
     *
     * @param PHP_Depend_Code_File $file The current file node.
     *
     * @return void
     * @see PHP_Depend_Visitor_AbstractVisitor::visitFile()
     */
    public function visitFile(PHP_Depend_Code_File $file)
    {
        // Skip for dummy files
        if ($file->getFileName() === null) {
            return;
        }
        // Check for initial file
        $uuid = $file->getUuid();
        if (isset($this->metrics[$uuid])) {
            return;
        }

        $this->fireStartFile($file);

        if ($this->restoreFromCache($file)) {
            $this->updateProjectMetrics($uuid);
            return $this->fireEndFile($file);
        }

        list($cloc, $eloc, $lloc) = $this->linesOfCode($file->getTokens());

        $loc   = $file->getEndLine();
        $ncloc = $loc - $cloc;

        $this->metrics[$uuid] = array(
            self::M_LINES_OF_CODE              =>  $loc,
            self::M_COMMENT_LINES_OF_CODE      =>  $cloc,
            self::M_EXECUTABLE_LINES_OF_CODE   =>  $eloc,
            self::M_LOGICAL_LINES_OF_CODE      =>  $lloc,
            self::M_NON_COMMENT_LINES_OF_CODE  =>  $ncloc
        );

        $this->updateProjectMetrics($uuid);

        $this->fireEndFile($file);
    }

    /**
     * Visits a function node.
     *
     * @param PHP_Depend_Code_Function $function The current function node.
     *
     * @return void
     * @see PHP_Depend_Visitor_AbstractVisitor::visitFunction()
     */
    public function visitFunction(PHP_Depend_Code_Function $function)
    {
        $this->fireStartFunction($function);

        $function->getSourceFile()->accept($this);

        if ($this->restoreFromCache($function)) {
            return $this->fireEndFunction($function);
        }

        list($cloc, $eloc, $lloc) = $this->linesOfCode(
            $function->getTokens(),
            true
        );

        $loc   = $function->getEndLine() - $function->getStartLine() + 1;
        $ncloc = $loc - $cloc;

        $this->metrics[$function->getUuid()] = array(
            self::M_LINES_OF_CODE              =>  $loc,
            self::M_COMMENT_LINES_OF_CODE      =>  $cloc,
            self::M_EXECUTABLE_LINES_OF_CODE   =>  $eloc,
            self::M_LOGICAL_LINES_OF_CODE      =>  $lloc,
            self::M_NON_COMMENT_LINES_OF_CODE  =>  $ncloc
        );

        $this->fireEndFunction($function);
    }

    /**
     * Visits a code interface object.
     *
     * @param PHP_Depend_Code_Interface $interface The context code interface.
     *
     * @return void
     */
    public function visitInterface(PHP_Depend_Code_Interface $interface)
    {
        $this->fireStartInterface($interface);

        $interface->getSourceFile()->accept($this);

        foreach ($interface->getMethods() as $method) {
            $method->accept($this);
        }

        if ($this->restoreFromCache($interface)) {
            return $this->fireEndInterface($interface);
        }

        list($cloc) = $this->linesOfCode($interface->getTokens(), true);

        $loc   = $interface->getEndLine() - $interface->getStartLine() + 1;
        $ncloc = $loc - $cloc;

        $this->metrics[$interface->getUuid()] = array(
            self::M_LINES_OF_CODE              =>  $loc,
            self::M_COMMENT_LINES_OF_CODE      =>  $cloc,
            self::M_EXECUTABLE_LINES_OF_CODE   =>  0,
            self::M_LOGICAL_LINES_OF_CODE      =>  0,
            self::M_NON_COMMENT_LINES_OF_CODE  =>  $ncloc
        );

        $this->fireEndInterface($interface);
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
        $this->fireStartMethod($method);

        if ($this->restoreFromCache($method)) {
            return $this->fireEndMethod($method);
        }
        
        if ($method->isAbstract()) {
            $cloc = 0;
            $eloc = 0;
            $lloc = 0;
        } else {
            list($cloc, $eloc, $lloc) = $this->linesOfCode(
                $method->getTokens(),
                true
            );
        }
        $loc   = $method->getEndLine() - $method->getStartLine() + 1;
        $ncloc = $loc - $cloc;

        $this->metrics[$method->getUuid()] = array(
            self::M_LINES_OF_CODE              =>  $loc,
            self::M_COMMENT_LINES_OF_CODE      =>  $cloc,
            self::M_EXECUTABLE_LINES_OF_CODE   =>  $eloc,
            self::M_LOGICAL_LINES_OF_CODE      =>  $lloc,
            self::M_NON_COMMENT_LINES_OF_CODE  =>  $ncloc
        );

        $this->classExecutableLines += $eloc;
        $this->classLogicalLines    += $lloc;

        $this->fireEndMethod($method);
    }

    /**
     * Updates the project metrics based on the node metrics identifier by the
     * given <b>$uuid</b>.
     *
     * @param string $uuid The unique identifier of a node.
     *
     * @return void
     */
    private function updateProjectMetrics($uuid)
    {
        foreach ($this->metrics[$uuid] as $metric => $value) {
            $this->projectMetrics[$metric] += $value;
        }
    }

    /**
     * Counts the Comment Lines Of Code (CLOC) and a pseudo Executable Lines Of
     * Code (ELOC) values.
     *
     * ELOC = Non Whitespace Lines + Non Comment Lines
     *
     * <code>
     * array(
     *     0  =>  23,  // Comment Lines Of Code
     *     1  =>  42   // Executable Lines Of Code
     * )
     * </code>
     *
     * @param array   $tokens The raw token stream.
     * @param boolean $search Optional boolean flag, search start.
     *
     * @return array
     */
    private function linesOfCode(array $tokens, $search = false)
    {
        $clines = array();
        $elines = array();
        $llines = 0;

        $count = count($tokens);
        if ($search === true) {
            for ($i = 0; $i < $count; ++$i) {
                $token = $tokens[$i];

                if ($token->type === PHP_Depend_TokenizerI::T_CURLY_BRACE_OPEN) {
                    break;
                }
            }
        } else {
            $i = 0;
        }

        for (; $i < $count; ++$i) {
            $token = $tokens[$i];

            if ($token->type === PHP_Depend_TokenizerI::T_COMMENT
                || $token->type === PHP_Depend_TokenizerI::T_DOC_COMMENT
            ) {
                $lines =& $clines;
            } else {
                $lines =& $elines;
            }

            switch ($token->type) {

            // These statement are terminated by a semicolon
            //case PHP_Depend_TokenizerI::T_RETURN:
            //case PHP_Depend_TokenizerI::T_THROW:

            case PHP_Depend_TokenizerI::T_IF:
            case PHP_Depend_TokenizerI::T_TRY:
            case PHP_Depend_TokenizerI::T_CASE:
            case PHP_Depend_TokenizerI::T_GOTO:
            case PHP_Depend_TokenizerI::T_CATCH:
            case PHP_Depend_TokenizerI::T_WHILE:
            case PHP_Depend_TokenizerI::T_ELSEIF:
            case PHP_Depend_TokenizerI::T_SWITCH:
            case PHP_Depend_TokenizerI::T_DEFAULT:
            case PHP_Depend_TokenizerI::T_FOREACH:
            case PHP_Depend_TokenizerI::T_FUNCTION:
            case PHP_Depend_TokenizerI::T_SEMICOLON:
                ++$llines;
                break;

            case PHP_Depend_TokenizerI::T_DO:
            case PHP_Depend_TokenizerI::T_FOR:
                // Because statements at least require one semicolon
                --$llines;
                break;
            }

            if ($token->startLine === $token->endLine) {
                $lines[$token->startLine] = true;
            } else {
                for ($j = $token->startLine; $j <= $token->endLine; ++$j) {
                    $lines[$j] = true;
                }
            }
            unset($lines);
        }
        return array(count($clines), count($elines), $llines);
    }
}
