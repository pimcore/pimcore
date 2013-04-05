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
 * @category  PHP
 * @package   PHP_PMD
 * @author    Manuel Pichler <mapi@phpmd.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id$
 * @link      http://phpmd.org
 */

/**
 * This class is a collection of concrete source analysis rules.
 *
 * @category  PHP
 * @package   PHP_PMD
 * @author    Manuel Pichler <mapi@phpmd.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: 1.4.1
 * @link      http://phpmd.org
 */
class PHP_PMD_RuleSet implements IteratorAggregate
{
    /**
     * Should this rule set force the strict mode.
     *
     * @var boolean
     * @since 1.2.0
     */
    private $strict = false;

    /**
     * The name of the file where this set is specified.
     *
     * @var string
     */
    private $fileName = '';

    /**
     * The name of this rule-set.
     *
     * @var string
     */
    private $name = '';

    /**
     * An optional description for this rule-set.
     *
     * @var string
     */
    private $description = '';

    /**
     * The violation report used by the rule-set.
     *
     * @var PHP_PMD_Report
     */
    private $report = null;

    /**
     * Mapping between marker interfaces and concrete context code node classes.
     *
     * @var array(string=>string)
     */
    private $applyTo = array(
        'PHP_PMD_Rule_IClassAware'      =>  'PHP_PMD_Node_Class',
        'PHP_PMD_Rule_IFunctionAware'   =>  'PHP_PMD_Node_Function',
        'PHP_PMD_Rule_IInterfaceAware'  =>  'PHP_PMD_Node_Interface',
        'PHP_PMD_Rule_IMethodAware'     =>  'PHP_PMD_Node_Method',
    );

    /**
     * Mapping of rules that apply to a concrete code node type.
     *
     * @var array(string=>array)
     */
    private $rules = array(
        'PHP_PMD_Node_Class'      =>  array(),
        'PHP_PMD_Node_Function'   =>  array(),
        'PHP_PMD_Node_Interface'  =>  array(),
        'PHP_PMD_Node_Method'     =>  array(),
    );

    /**
     * Returns the file name where the definition of this rule-set comes from.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Sets the file name where the definition of this rule-set comes from.
     *
     * @param string $fileName The file name.
     *
     * @return void
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Returns the name of this rule-set.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of this rule-set.
     *
     * @param string $name The name of this rule-set.
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the description text for this rule-set instance.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description text for this rule-set instance.
     *
     * @param string $description The description text.
     *
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Activates the strict mode for this rule set instance.
     *
     * @return void
     * @since 1.2.0
     */
    public function setStrict()
    {
        $this->strict = true;
    }

    /**
     * Returns the violation report used by the rule-set.
     *
     * @return PHP_PMD_Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Sets the violation report used by the rule-set.
     *
     * @param PHP_PMD_Report $report The violation report to use.
     *
     * @return void
     */
    public function setReport(PHP_PMD_Report $report)
    {
        $this->report = $report;
    }

    /**
     * This method returns a rule by its name or <b>null</b> if it doesn't exist.
     *
     * @param string $name The rule name.
     *
     * @return PHP_PMD_Rule
     */
    public function getRuleByName($name)
    {
        foreach ($this->getRules() as $rule) {
            if ($rule->getName() === $name) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * This method returns an iterator will all rules that belong to this
     * rule-set.
     *
     * @return Iterator
     */
    public function getRules()
    {
        $result = array();
        foreach ($this->rules as $rules) {
            foreach ($rules as $rule) {
                if (in_array($rule, $result, true) === false) {
                    $result[] = $rule;
                }
            }
        }

        return new ArrayIterator($result);
    }

    /**
     * Adds a new rule to this rule-set.
     *
     * @param PHP_PMD_Rule $rule Rule instance to add.
     *
     * @return void
     */
    public function addRule(PHP_PMD_Rule $rule)
    {
        foreach ($this->applyTo as $applyTo => $type) {
            if ($rule instanceof $applyTo) {
                $this->rules[$type][] = $rule;
            }
        }
    }

    /**
     * Applies all registered rules that match against the concrete node type.
     *
     * @param PHP_PMD_AbstractNode $node The context source code node.
     *
     * @return void
     */
    public function apply(PHP_PMD_AbstractNode $node)
    {
        // Current node type
        $className = get_class($node);

        // Check for valid node type
        if (!isset($this->rules[$className])) {
            return;
        }

        // Apply all rules to this node
        foreach ($this->rules[$className] as $rule) {
            if ($node->hasSuppressWarningsAnnotationFor($rule) && !$this->strict) {
                continue;
            }
            $rule->setReport($this->report);
            $rule->apply($node);
        }
    }

    /**
     * Returns an iterator with all rules that are part of this rule-set.
     *
     * @return Iterator
     */
    public function getIterator()
    {
        return $this->getRules();
    }
}
