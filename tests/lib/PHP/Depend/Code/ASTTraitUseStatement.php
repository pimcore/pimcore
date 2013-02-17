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
 * @category   PHP
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://www.pdepend.org/
 * @since      1.0.0
 */

/**
 * This node class represents a strait use statement.
 *
 * @category   PHP
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://www.pdepend.org/
 * @since      1.0.0
 */
class PHP_Depend_Code_ASTTraitUseStatement extends PHP_Depend_Code_ASTStatement
{
    /**
     * The type of this class.
     */
    const CLAZZ = __CLASS__;

    /**
     * @var PHP_Depend_Code_Method[]
     */
    private $allMethods;

    /**
     * Returns an array with all aliased or directly imported methods.
     *
     * @return PHP_Depend_Code_Method[]
     */
    public function getAllMethods()
    {
        if (false === is_array($this->allMethods)) {
            $this->allMethods = array();
            foreach ($this->nodes as $node) {
                if ($node instanceof PHP_Depend_Code_ASTTraitReference) {
                    $this->collectMethods($node);
                }
            }
        }
        return $this->allMethods;
    }

    /**
     * This method tests if the given {@link PHP_Depend_Code_Method} is excluded
     * by precedence statement in this use statement. It will return <b>true</b>
     * if the given <b>$method</b> is excluded, otherwise the return value of
     * this method will be <b>false</b>.
     *
     * @param PHP_Depend_Code_Method $method The method to test for exclusion.
     *
     * @return boolean
     */
    public function hasExcludeFor(PHP_Depend_Code_Method $method)
    {
        $methodName   = strtolower($method->getName());
        $methodParent = $method->getParent();

        $precedences = $this->findChildrenOfType(
            PHP_Depend_Code_ASTTraitAdaptationPrecedence::CLAZZ
        );

        foreach ($precedences as $precedence) {
            if (strtolower($precedence->getImage()) !== $methodName) {
                continue;
            }
            $children = $precedence->getChildren();
            for ($i = 1, $count = count($children); $i < $count; ++$i) {
                if ($methodParent === $children[$i]->getType()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Collects all directly defined methods or method aliases for the given
     * {@link PHP_Depend_Code_ASTTraitReference}
     *
     * @param PHP_Depend_Code_ASTTraitReference $reference Context trait reference.
     *
     * @return void
     */
    private function collectMethods(PHP_Depend_Code_ASTTraitReference $reference)
    {
        foreach ($reference->getType()->getAllMethods() as $method) {
            foreach ($this->getAliasesFor($method) as $alias) {
                $this->allMethods[] = $alias;
            }
        }
    }

    /**
     * Returns an <b>array</b> with all aliases for the given method. If no
     * alias exists for the given method, this method will simply return the
     * an <b>array</b> with the original method.
     *
     * @param PHP_Depend_Code_Method $method The imported trait method.
     *
     * @return PHP_Depend_Code_Method[]
     */
    private function getAliasesFor(PHP_Depend_Code_Method $method)
    {
        $name = strtolower($method->getName());

        $newNames = array();
        foreach ($this->getAliases() as $alias) {
            $name2 = strtolower($alias->getImage());
            if ($name2 !== $name) {
                continue;
            }

            $modifier = $method->getModifiers();
            if (-1 < $alias->getNewModifier()) {
                $modifier &= ~(
                    PHP_Depend_ConstantsI::IS_PUBLIC |
                    PHP_Depend_ConstantsI::IS_PROTECTED |
                    PHP_Depend_ConstantsI::IS_PRIVATE
                );
                $modifier |= $alias->getNewModifier();
            }

            $newName = $method->getName();
            if ($alias->getNewName()) {
                $newName = $alias->getNewName();
            }

            if (0 === count($alias->getChildren())) {
                $newMethod = clone $method;
                $newMethod->setName($newName);
                $newMethod->setModifiers($modifier);

                $newNames[] = $newMethod;
                continue;
            }

            if ($alias->getChild(0)->getType() !== $method->getParent()) {
                continue;
            }

            $newMethod = clone $method;
            $newMethod->setName($newName);
            $newMethod->setModifiers($modifier);

            $newNames[] = $newMethod;
        }

        if (count($newNames) > 0) {
            return $newNames;
        }
        return array($method);
    }

    /**
     * Returns an <b>array</b> with all alias statements declared in this use
     * statement.
     *
     * @return PHP_Depend_Code_ASTTraitAdaptationAlias[]
     */
    private function getAliases()
    {
        return $this->findChildrenOfType(
            PHP_Depend_Code_ASTTraitAdaptationAlias::CLAZZ
        );
    }

    /**
     * Accept method of the visitor design pattern. This method will be called
     * by a visitor during tree traversal.
     *
     * @param PHP_Depend_Code_ASTVisitorI $visitor The calling visitor instance.
     * @param mixed                       $data    Optional previous calculated data.
     *
     * @return mixed
     */
    public function accept(PHP_Depend_Code_ASTVisitorI $visitor, $data = null)
    {
        return $visitor->visitTraitUseStatement($this, $data);
    }
}
