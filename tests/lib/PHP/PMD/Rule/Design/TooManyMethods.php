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
 */

require_once 'PHP/PMD/AbstractRule.php';
require_once 'PHP/PMD/Rule/IClassAware.php';

/**
 * This rule class will detect all classes with too much methods.
 *
 * @category   PHP
 * @package    PHP_PMD
 * @subpackage Rule_Design
 * @author     Manuel Pichler <mapi@phpmd.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.4.1
 * @link       http://phpmd.org
 */
class PHP_PMD_Rule_Design_TooManyMethods
       extends PHP_PMD_AbstractRule
    implements PHP_PMD_Rule_IClassAware
{
    /**
     * the default regex pattern for ignore method names. override with
     * "ignorepattern" rule setting.
     *
     * @var string
     */
    const DEFAULT_IGNORE_REGEXP = '(^(set|get))i';

    /**
     * Regular expression that filter all methods that are ignored by this rule.
     *
     * @var string
     */
    private $ignoreRegexp;

    /**
     * This method checks the number of methods with in a given class and checks
     * this number against a configured threshold.
     *
     * @param PHP_PMD_AbstractNode $node The context source code node.
     *
     * @return void
     */
    public function apply(PHP_PMD_AbstractNode $node)
    {
        try {
            $this->ignoreRegexp = $this->getStringProperty('ignorepattern');
        } catch (OutOfBoundsException $e) {
            $this->ignoreRegexp = self::DEFAULT_IGNORE_REGEXP;
        }

        $threshold = $this->getIntProperty('maxmethods');
        if ($node->getMetric('nom') <= $threshold) {
            return;
        }
        $nom = $this->countMethods($node);
        if ($nom <= $threshold) {
            return;
        }
        $this->addViolation(
            $node,
            array(
                $node->getType(),
                $node->getName(),
                $nom,
                $threshold
            )
        );
    }

    /**
     * Counts all methods within the given class/interface node.
     *
     * @param PHP_PMD_Node_AbstractType $node The context class node.
     *
     * @return integer
     */
    private function countMethods(PHP_PMD_Node_AbstractType $node)
    {
        $count = 0;
        foreach ($node->getMethodNames() as $name) {
            if (preg_match($this->ignoreRegexp, $name) === 0) {
                ++$count;
            }
        }
        return $count;
    }
}
