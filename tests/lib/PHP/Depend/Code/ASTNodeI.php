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
 * @since      0.9.6
 */

// @codeCoverageIgnoreStart

/**
 * Root interface for an ast node.
 *
 * @category   PHP
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://www.pdepend.org/
 * @since      0.9.6
 */
interface PHP_Depend_Code_ASTNodeI
{
    /**
     * Returns the source image of this ast node.
     *
     * @return string
     */
    function getImage();

    /**
     * Returns the start line for this ast node.
     *
     * @return integer
     */
    function getStartLine();

    /**
     * Returns the start column for this ast node.
     *
     * @return integer
     */
    function getStartColumn();

    /**
     * Returns the end line for this ast node.
     *
     * @return integer
     */
    function getEndLine();

    /**
     * Returns the end column for this ast node.
     *
     * @return integer
     */
    function getEndColumn();

    /**
     * Returns the node instance for the given index or throws an exception.
     *
     * @param integer $index Index of the requested node.
     *
     * @return PHP_Depend_Code_ASTNodeI
     * @throws OutOfBoundsException When no node exists at the given index.
     */
    function getChild($index);

    /**
     * This method returns all direct children of the actual node.
     *
     * @return PHP_Depend_Code_ASTNodeI[]
     */
    function getChildren();

    /**
     * This method will search recursive for the first child node that is an
     * instance of the given <b>$targetType</b>. The returned value will be
     * <b>null</b> if no child exists for that.
     *
     * @param string $targetType Searched class or interface type.
     *
     * @return PHP_Depend_Code_ASTNodeI
     */
    function getFirstChildOfType($targetType);

    /**
     * This method will search recursive for all child nodes that are an
     * instance of the given <b>$targetType</b>. The returned value will be
     * an empty <b>array</b> if no child exists for that.
     *
     * @param string $targetType Searched class or interface type.
     *
     * @return arrayPHP_Depend_Code_ASTNodeI[]
     */
    function findChildrenOfType($targetType);

    /**
     * This method adds a new child node to this node instance.
     *
     * @param PHP_Depend_Code_ASTNodeI $node The new child node.
     *
     * @return void
     */
    function addChild(PHP_Depend_Code_ASTNodeI $node);

    /**
     * Returns the parent node of this node or <b>null</b> when this node is
     * the root of a node tree.
     *
     * @return PHP_Depend_Code_ASTNodeI
     */
    function getParent();

    /**
     * Traverses up the node tree and finds all parent nodes that are  instances
     * of <b>$parentType</b>.
     *
     * @param string $parentType Class/interface type you are looking for,
     *
     * @return array(PHP_Depend_Code_ASTNodeI)
     */
    function getParentsOfType($parentType);

    /**
     * Sets the parent node of this node.
     *
     * @param PHP_Depend_Code_ASTNodeI $node The parent node of this node.
     *
     * @return void
     */
    function setParent(PHP_Depend_Code_ASTNodeI $node);

    /**
     * Accept method of the visitor design pattern. This method will be called
     * by a visitor during tree traversal.
     *
     * @param PHP_Depend_Code_ASTVisitorI $visitor The calling visitor instance.
     * @param array(string=>integer)      $data    Optional previous calculated data.
     *
     * @return void
     * @since 0.9.8
     */
    function accept(PHP_Depend_Code_ASTVisitorI $visitor, $data = null);

    /**
     * This method can be called by the PHP_Depend runtime environment or a
     * utilizing component to free up memory. This methods are required for
     * PHP version < 5.3 where cyclic references can not be resolved
     * automatically by PHP's garbage collector.
     *
     * @return void
     * @since 0.9.12
     */
    function free();
}

// @codeCoverageIgnoreStart
