<?php
/**
 * This file is part of PHP_PMD.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2012, Manuel Pichler <mapi@phpmd.org>.
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
 * @category   PHP
 * @package    PHP_PMD
 * @subpackage Rule
 * @author     Manuel Pichler <mapi@phpmd.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://phpmd.org
 */

require_once 'PHP/PMD/Rule/AbstractLocalVariable.php';
require_once 'PHP/PMD/Rule/IFunctionAware.php';
require_once 'PHP/PMD/Rule/IMethodAware.php';

/**
 * This rule collects all formal parameters of a given function or method that
 * are not used in a statement of the artifact's body.
 *
 * @category   PHP
 * @package    PHP_PMD
 * @subpackage Rule
 * @author     Manuel Pichler <mapi@phpmd.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.4.1
 * @link       http://phpmd.org
 */
class PHP_PMD_Rule_UnusedFormalParameter
       extends PHP_PMD_Rule_AbstractLocalVariable
    implements PHP_PMD_Rule_IFunctionAware,
               PHP_PMD_Rule_IMethodAware
{
    /**
     * Collected ast nodes.
     *
     * @var PHP_PMD_Node_ASTNode[]
     */
    private $nodes = array();

    /**
     * This method checks that all parameters of a given function or method are
     * used at least one time within the artifacts body.
     *
     * @param PHP_PMD_AbstractNode $node The context source code node.
     *
     * @return void
     */
    public function apply(PHP_PMD_AbstractNode $node)
    {
        if ($this->isAbstractMethod($node)) {
            return;
        }

        if ($this->isInheritedSignature($node)) {
            return;
        }

        if ($this->isNotDeclaration($node)) {
            return;
        }

        $this->nodes = array();

        $this->collectParameters($node);
        $this->removeUsedParameters($node);

        foreach ($this->nodes as $node) {
            $this->addViolation($node, array($node->getImage()));
        }
    }

    /**
     * Returns <b>true</b> when the given node is an abstract method.
     *
     * @param PHP_PMD_AbstractNode $node The context method or function instance.
     *
     * @return boolean
     */
    private function isAbstractMethod(PHP_PMD_AbstractNode $node)
    {
        if ($node instanceof PHP_PMD_Node_Method) {
            return $node->isAbstract();
        }
        return false;
    }
    
    /**
     * Returns <b>true</b> when the given node is method with signature declared as inherited using
     * {@inheritdoc} annotation.
     *
     * @param PHP_PMD_AbstractNode $node The context method or function instance.
     *
     * @return boolean
     */
     private function isInheritedSignature(PHP_PMD_AbstractNode $node)
     {
        if ($node instanceof PHP_PMD_Node_Method) {
            return preg_match('/\@inheritdoc/', $node->getDocComment());
        }
        return false;
    }

    /**
     * Tests if the given <b>$node</b> is a method and if this method is also
     * the initial declaration.
     *
     * @param PHP_PMD_AbstractNode $node The context method or a function instance.
     *
     * @return boolean
     * @since 1.2.1
     */
    private function isNotDeclaration(PHP_PMD_AbstractNode $node)
    {
        if ($node instanceof PHP_PMD_Node_Method) {
            return !$node->isDeclaration();
        }
        return false;
    }

    /**
     * This method extracts all parameters for the given function or method node
     * and it stores the parameter images in the <b>$_images</b> property.
     *
     * @param PHP_PMD_AbstractNode $node The context function/method node.
     *
     * @return void
     */
    private function collectParameters(PHP_PMD_AbstractNode $node)
    {
        // First collect the formal parameters container
        $parameters = $node->getFirstChildOfType('FormalParameters');

        // Now get all declarators in the formal parameters container
        $declarators = $parameters->findChildrenOfType('VariableDeclarator');

        foreach ($declarators as $declarator) {
            $this->nodes[$declarator->getImage()] = $declarator;
        }
    }

    /**
     * This method collects all local variables in the body of the currently
     * analyzed method or function and removes those parameters that are
     * referenced by one of the collected variables.
     *
     * @param PHP_PMD_AbstractNode $node The context function or method instance.
     *
     * @return void
     */
    private function removeUsedParameters(PHP_PMD_AbstractNode $node)
    {
        $variables = $node->findChildrenOfType('Variable');
        foreach ($variables as $variable) {
            if ($this->isRegularVariable($variable)) {
                unset($this->nodes[$variable->getImage()]);
            }
        }

        /* If the method calls func_get_args() then all parameters are
         * automatically referenced */
        $functionCalls = $node->findChildrenOfType('FunctionPostfix');
        foreach ($functionCalls as $functionCall) {
            if ($functionCall->getImage() == 'func_get_args') {
                $this->nodes = array();
            }
        }
    }
}
