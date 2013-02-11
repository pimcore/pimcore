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
 * @subpackage Rule_Design
 * @author     Manuel Pichler <mapi@phpmd.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://phpmd.org
 * @since      1.1.0
 */

require_once 'PHP/PMD/AbstractRule.php';
require_once 'PHP/PMD/Rule/IMethodAware.php';

/**
 * This rule class detects parameters not named in camelCase.
 *
 * @category   PHP
 * @package    PHP_PMD
 * @subpackage Rule_Controversial
 * @author     Francis Besset <francis.besset@gmail.com>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.4.1
 * @link       http://phpmd.org
 * @since      1.1.0
 */
class PHP_PMD_Rule_Controversial_CamelCaseParameterName
       extends PHP_PMD_AbstractRule
    implements PHP_PMD_RULE_IMethodAware
{
    /**
     * This method checks if a parameter is not named in camelCase
     * and emits a rule violation.
     *
     * @param PHP_PMD_AbstractNode $node The context source code node.
     *
     * @return void
     */
    public function apply(PHP_PMD_AbstractNode $node)
    {
        foreach ($node->getParameters() as $parameter) {
            if (!preg_match('/^\$[a-z][a-zA-Z0-9]*$/', $parameter->getName())) {
                $this->addViolation(
                    $node,
                    array(
                        $parameter->getName(),
                    )
                );
            }
        }
    }
}
