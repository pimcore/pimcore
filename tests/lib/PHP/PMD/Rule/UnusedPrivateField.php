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

require_once 'PHP/PMD/AbstractRule.php';
require_once 'PHP/PMD/Rule/IClassAware.php';

/**
 * This rule collects all private fields in a class that aren't used in any
 * method of the analyzed class.
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
class PHP_PMD_Rule_UnusedPrivateField
       extends PHP_PMD_AbstractRule
    implements PHP_PMD_Rule_IClassAware
{
    /**
     * Collected private fields/variable declarators in the currently processed
     * class.
     *
     * @var PHP_PMD_Node_ASTNode[]
     */
    private $fields = array();

    /**
     * This method checks that all private class properties are at least accessed
     * by one method.
     *
     * @param PHP_PMD_AbstractNode $node The context source code node.
     *
     * @return void
     */
    public function apply(PHP_PMD_AbstractNode $node)
    {
        foreach ($this->collectUnusedPrivateFields($node) as $field) {
            $this->addViolation($field, array($field->getImage()));
        }
    }

    /**
     * This method collects all private fields that aren't used by any class
     * method.
     *
     * @param PHP_PMD_Node_Class $class The context class node.
     *
     * @return array(PHP_PMD_AbstractNode)
     */
    private function collectUnusedPrivateFields(PHP_PMD_Node_Class $class)
    {
        $this->fields = array();

        $this->collectPrivateFields($class);
        $this->removeUsedFields($class);

        return $this->fields;
    }

    /**
     * This method collects all private fields in the given class and stores
     * them in the <b>$_fields</b> property.
     *
     * @param PHP_PMD_Node_Class $class The context class instance.
     *
     * @return void
     */
    private function collectPrivateFields(PHP_PMD_Node_Class $class)
    {
        foreach ($class->findChildrenOfType('FieldDeclaration') as $declaration) {
            if ($declaration->isPrivate()) {
                $this->collectPrivateField($declaration);
            }
        }
    }

    /**
     * This method extracts all variable declarators from the given field
     * declaration and stores them in the <b>$_fields</b> property.
     *
     * @param PHP_PMD_Node_ASTNode $declaration The context field declaration.
     *
     * @return void
     */
    private function collectPrivateField(PHP_PMD_Node_ASTNode $declaration)
    {
        $fields = $declaration->findChildrenOfType('VariableDeclarator');
        foreach ($fields as $field) {
            $this->fields[$field->getImage()] = $field;
        }
    }

    /**
     * This method extracts all property postfix nodes from the given class and
     * removes all fields from the <b>$_fields</b> property that are accessed by
     * one of the postfix nodes.
     *
     * @param PHP_PMD_Node_Class $class The context class instance.
     *
     * @return void
     */
    private function removeUsedFields(PHP_PMD_Node_Class $class)
    {
        foreach ($class->findChildrenOfType('PropertyPostfix') as $postfix) {
            if ($this->isInScopeOfClass($class, $postfix)) {
                $this->removeUsedField($postfix);
            }
        }
    }

    /**
     * This method removes the field from the <b>$_fields</b> property that is
     * accessed through the given property postfix node.
     *
     * @param PHP_PMD_Node_ASTNode $postfix The context postfix node.
     *
     * @return void
     */
    private function removeUsedField(PHP_PMD_Node_ASTNode $postfix)
    {
        if ($postfix->getParent()->isStatic()) {
            $image = '';
            $child = $postfix->getFirstChildOfType('Variable');
        } else {
            $image = '$';
            $child = $postfix->getFirstChildOfType('Identifier');
        }


        if ($this->isValidPropertyNode($child)) {
            unset($this->fields[$image . $child->getImage()]);
        }
    }

    /**
     * Checks if the given node is a valid property node.
     *
     * @param PHP_PMD_Node_ASTNode $node The context property node.
     *
     * @return boolean
     * @since 0.2.6
     */
    protected function isValidPropertyNode(PHP_PMD_Node_ASTNode $node = null)
    {
        if ($node === null) {
            return false;
        }
        
        $parent = $node->getParent();
        while (!$parent->isInstanceOf('PropertyPostfix')) {
            if ($parent->isInstanceOf('CompoundVariable')) {
                return false;
            }
            $parent = $parent->getParent();
        }
        return true;
    }

    /**
     * This method checks that the given property postfix is accessed on an
     * instance or static reference to the given class.
     *
     * @param PHP_PMD_Node_Class   $class   The context class node instance.
     * @param PHP_PMD_Node_ASTNode $postfix The context property postfix node.
     *
     * @return boolean
     */
    protected function isInScopeOfClass(
        PHP_PMD_Node_Class $class,
        PHP_PMD_Node_ASTNode $postfix
    ) {
        $owner = $postfix->getParent()->getChild(0);
        if ($owner->isInstanceOf('PropertyPostfix')) {
            $owner = $owner->getParent()->getParent()->getChild(0);
        }
        return (
            $owner->isInstanceOf('SelfReference') ||
            $owner->isInstanceOf('StaticReference') ||
            strcasecmp($owner->getImage(), '$this') === 0 ||
            strcasecmp($owner->getImage(), $class->getImage()) === 0
        );
    }
}
