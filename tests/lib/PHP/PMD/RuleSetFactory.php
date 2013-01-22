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

require_once 'PHP/PMD/AbstractRule.php';
require_once 'PHP/PMD/RuleSet.php';
require_once 'PHP/PMD/RuleClassFileNotFoundException.php';
require_once 'PHP/PMD/RuleClassNotFoundException.php';
require_once 'PHP/PMD/RuleSetNotFoundException.php';

/**
 * This factory class is used to create the {@link PHP_PMD_RuleSet} instance
 * that PHP_PMD will use to analyze the source code.
 *
 * @category  PHP
 * @package   PHP_PMD
 * @author    Manuel Pichler <mapi@phpmd.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: 1.4.1
 * @link      http://phpmd.org
 */
class PHP_PMD_RuleSetFactory
{
    /**
     * Is the strict mode active?
     *
     * @var boolean
     * @since 1.2.0
     */
    private $strict = false;

    /**
     * The data directory set by PEAR or a dynamic property set within the class
     * constructor.
     *
     * @var string
     */
    private $location = 'C:\php\pear\data';

    /**
     * The minimum priority for rules to load.
     *
     * @var integer
     */
    private $minimumPriority = PHP_PMD_Rule::LOWEST_PRIORITY;

    /**
     * Constructs a new default rule-set factory instance.
     */
    public function __construct()
    {
        // PEAR installer workaround
        if (strpos($this->location, '@data_dir') === 0) {
            $this->location = dirname(__FILE__) . '/../../../resources';
        } else {
            $this->location .= '/PHP_PMD/resources';
        }
    }

    /**
     * Activates the strict mode for all rule sets.
     *
     * @return void
     * @since 1.2.0
     */
    public function setStrict()
    {
        $this->strict = true;
    }

    /**
     * Sets the minimum priority that a rule must have.
     *
     * @param integer $minimumPriority The minimum priority value.
     *
     * @return void
     */
    public function setMinimumPriority($minimumPriority)
    {
        $this->minimumPriority = $minimumPriority;
    }

    /**
     * Creates an array of rule-set instances for the given argument.
     *
     * @param string $ruleSetFileNames Comma-separated string of rule-set filenames
     *                                 or identifier.
     *
     * @return array(PHP_PMD_RuleSet)
     */
    public function createRuleSets($ruleSetFileNames)
    {
        $ruleSets = array();

        $ruleSetFileName = strtok($ruleSetFileNames, ',');
        while ($ruleSetFileName !== false) {
            $ruleSets[] = $this->createSingleRuleSet($ruleSetFileName);

            $ruleSetFileName = strtok(',');
        }
        return $ruleSets;
    }

    /**
     * Creates a single rule-set instance for the given filename or identifier.
     *
     * @param string $ruleSetOrFileName The rule-set filename or identifier.
     *
     * @return PHP_PMD_RuleSet
     */
    public function createSingleRuleSet($ruleSetOrFileName)
    {
        $fileName = $this->createRuleSetFileName($ruleSetOrFileName);
        return $this->parseRuleSetNode($fileName);
    }

    /**
     * This method creates the filename for a rule-set identifier or it returns
     * the input when it is already a filename.
     *
     * @param string $ruleSetOrFileName The rule-set filename or identifier.
     *
     * @return string
     */
    private function createRuleSetFileName($ruleSetOrFileName)
    {
        if (file_exists($ruleSetOrFileName) === true) {
            return $ruleSetOrFileName;
        }

        $fileName = $this->location . '/' . $ruleSetOrFileName;
        if (file_exists($fileName) === true) {
            return $fileName;
        }

        $fileName = $this->location . '/rulesets/' . $ruleSetOrFileName . '.xml';
        if (file_exists($fileName) === true) {
            return $fileName;
        }

        $fileName = getcwd() . '/rulesets/' . $ruleSetOrFileName . '.xml';
        if (file_exists($fileName) === true) {
            return $fileName;
        }

        throw new PHP_PMD_RuleSetNotFoundException($ruleSetOrFileName);
    }

    /**
     * This method parses the rule-set definition in the given file.
     *
     * @param string $fileName The filename of a rule-set definition.
     *
     * @return PHP_PMD_RuleSet
     */
    private function parseRuleSetNode($fileName)
    {
        // Hide error messages
        $libxml = libxml_use_internal_errors(true);

        $xml = simplexml_load_file($fileName);
        if ($xml === false) {
            // Reset error handling to previous setting
            libxml_use_internal_errors($libxml);

            throw new RuntimeException(trim(libxml_get_last_error()->message));
        }

        $ruleSet = new PHP_PMD_RuleSet();
        $ruleSet->setFileName($fileName);
        $ruleSet->setName((string) $xml['name']);

        if ($this->strict) {
            $ruleSet->setStrict();
        }

        foreach ($xml->children() as $node) {
            if ($node->getName() === 'description') {
                $ruleSet->setDescription((string) $node);
            } else if ($node->getName() === 'rule') {
                $this->parseRuleNode($ruleSet, $node);
            }
        }

        return $ruleSet;
    }

    /**
     * This method parses a single rule xml node. Bases on the structure of the
     * xml node this method delegates the parsing process to another method in
     * this class.
     *
     * @param PHP_PMD_RuleSet  $ruleSet The parent rule-set instance.
     * @param SimpleXMLElement $node    The unparsed rule xml node.
     *
     * @return void
     */
    private function parseRuleNode(
        PHP_PMD_RuleSet $ruleSet,
        SimpleXMLElement $node
    ) {
        if (substr($node['ref'], -3, 3) === 'xml') {
            $this->parseRuleSetReferenceNode($ruleSet, $node);
        } else if ('' === (string) $node['ref']) {
            $this->parseSingleRuleNode($ruleSet, $node);
        } else {
            $this->parseRuleReferenceNode($ruleSet, $node);
        }
    }

    /**
     * This method parses a complete rule set that was includes a reference in
     * the currently parsed ruleset.
     *
     * @param PHP_PMD_RuleSet  $ruleSet     The parent rule-set instance.
     * @param SimpleXMLElement $ruleSetNode The unparsed rule xml node.
     *
     * @return void
     */
    private function parseRuleSetReferenceNode(
        PHP_PMD_RuleSet $ruleSet,
        SimpleXMLElement $ruleSetNode
    ) {
        $rules = $this->parseRuleSetReference($ruleSetNode);
        foreach ($rules as $rule) {
            if ($this->isIncluded($rule, $ruleSetNode)) {
                $ruleSet->addRule($rule);
            }
        }
    }

    /**
     * Parses a rule-set xml file referenced by the given rule-set xml element.
     *
     * @param SimpleXMLElement $ruleSetNode The context rule-set xml element.
     *
     * @return PHP_PMD_RuleSet
     * @since 0.2.3
     */
    private function parseRuleSetReference(SimpleXMLElement $ruleSetNode)
    {
        $ruleSetFactory = new PHP_PMD_RuleSetFactory();
        $ruleSetFactory->setMinimumPriority($this->minimumPriority);

        return $ruleSetFactory->createSingleRuleSet((string) $ruleSetNode['ref']);
    }

    /**
     * Checks if the given rule is included/not excluded by the given rule-set
     * reference node.
     *
     * @param PHP_PMD_Rule     $rule        The currently processed rule.
     * @param SimpleXMLElement $ruleSetNode The context rule-set xml element.
     *
     * @return boolean
     * @since 0.2.3
     */
    private function isIncluded(PHP_PMD_Rule $rule, SimpleXMLElement $ruleSetNode)
    {
        foreach ($ruleSetNode->exclude as $exclude) {
            if ($rule->getName() === (string) $exclude['name']) {
                return false;
            }
        }
        return true;
    }

    /**
     * This method will create a single rule instance and add it to the given
     * {@link PHP_PMD_RuleSet} object.
     *
     * @param PHP_PMD_RuleSet  $ruleSet  The parent rule-set instance.
     * @param SimpleXMLElement $ruleNode The context rule-set xml node.
     *
     * @return void
     * @throws PHP_PMD_RuleClassFileNotFoundException When a class file does not
     *                                                exist.
     * @throws PHP_PMD_RuleClassNotFoundException When a configured rule class
     *                                            does not exist.
     */
    private function parseSingleRuleNode(
        PHP_PMD_RuleSet $ruleSet,
        SimpleXMLElement $ruleNode
    ) {
        $className = (string) $ruleNode['class'];
        $fileName  = strtr($className, '_', '/') . '.php';

        if (class_exists($className) === false) {
            $handle = @fopen($fileName, 'r', true);
            if ($handle === false) {
                throw new PHP_PMD_RuleClassFileNotFoundException($className);
            }
            fclose($handle);

            include_once $fileName;

            if (class_exists($className) === false) {
                throw new PHP_PMD_RuleClassNotFoundException($className);
            }
        }

        /* @var $rule PHP_PMD_Rule */
        $rule = new $className();
        $rule->setName((string) $ruleNode['name']);
        $rule->setMessage((string) $ruleNode['message']);
        $rule->setExternalInfoUrl((string) $ruleNode['externalInfoUrl']);

        $rule->setRuleSetName($ruleSet->getName());

        if (trim($ruleNode['since']) !== '') {
            $rule->setSince((string) $ruleNode['since']);
        }

        foreach ($ruleNode->children() as $node) {
            if ($node->getName() === 'description') {
                $rule->setDescription((string) $node);
            } else if ($node->getName() === 'example') {
                $rule->addExample((string) $node);
            } else if ($node->getName() === 'priority') {
                $rule->setPriority((integer) $node);
            } else if ($node->getName() === 'properties') {
                $this->parsePropertiesNode($rule, $node);
            }
        }

        if ($rule->getPriority() <= $this->minimumPriority) {
            $ruleSet->addRule($rule);
        }
    }

    /**
     * This method parses a single rule that was included from a different
     * rule-set.
     *
     * @param PHP_PMD_RuleSet  $ruleSet  The parent rule-set instance.
     * @param SimpleXMLElement $ruleNode The unparsed rule xml node.
     *
     * @return void
     */
    private function parseRuleReferenceNode(
        PHP_PMD_RuleSet $ruleSet,
        SimpleXMLElement $ruleNode
    ) {
        $ref = (string) $ruleNode['ref'];

        $fileName = substr($ref, 0, strpos($ref, '.xml/') + 4);
        $fileName = $this->createRuleSetFileName($fileName);

        $ruleName = substr($ref, strpos($ref, '.xml/') + 5);

        $ruleSetFactory = new PHP_PMD_RuleSetFactory();

        $ruleSetRef = $ruleSetFactory->createSingleRuleSet($fileName);
        $rule       = $ruleSetRef->getRuleByName($ruleName);

        if (trim($ruleNode['name']) !== '') {
            $rule->setName((string) $ruleNode['name']);
        }
        if (trim($ruleNode['message']) !== '') {
            $rule->setMessage((string) $ruleNode['message']);
        }
        if (trim($ruleNode['externalInfoUrl']) !== '') {
            $rule->setExternalInfoUrl((string) $ruleNode['externalInfoUrl']);
        }

        foreach ($ruleNode->children() as $node) {
            if ($node->getName() === 'description') {
                $rule->setDescription((string) $node);
            } else if ($node->getName() === 'example') {
                $rule->addExample((string) $node);
            } else if ($node->getName() === 'priority') {
                $rule->setPriority((integer) $node);
            } else if ($node->getName() === 'properties') {
                $this->parsePropertiesNode($rule, $node);
            }
        }

        if ($rule->getPriority() <= $this->minimumPriority) {
            $ruleSet->addRule($rule);
        }
    }

    /**
     * This method parses a xml properties structure and adds all found properties
     * to the given <b>$rule</b> object.
     *
     * <code>
     *   ...
     *   <properties>
     *       <property name="foo" value="42" />
     *       <property name="bar" value="23" />
     *       ...
     *   </properties>
     *   ...
     * </code>
     *
     * @param PHP_PMD_Rule     $rule           The context rule object.
     * @param SimpleXMLElement $propertiesNode The raw properties xml node.
     *
     * @return void
     */
    private function parsePropertiesNode(
        PHP_PMD_Rule $rule,
        SimpleXMLElement $propertiesNode
    ) {
        foreach ($propertiesNode->children() as $node) {
            if ($node->getName() === 'property') {
                $this->addProperty($rule, $node);
            }
        }
    }

    /**
     * Adds an additional property to the given <b>$rule</b> instance.
     *
     * @param PHP_PMD_Rule     $rule The context rule object.
     * @param SimpleXMLElement $node The raw xml property node.
     *
     * @return void
     */
    private function addProperty(PHP_PMD_Rule $rule, SimpleXMLElement $node)
    {
        $name  = trim($node['name']);
        $value = trim($this->getPropertyValue($node));
        if ($name !== '' && $value !== '') {
            $rule->addProperty($name, $value);
        }
    }

    /**
     * Returns the value of a property node. This value can be expressed in
     * two different notations. First version is an attribute named <b>value</b>
     * and the second valid notation is a child element named <b>value</b> that
     * contains the value as character data.
     *
     * @param SimpleXMLElement $propertyNode The raw xml property node.
     *
     * @return string
     * @since 0.2.5
     */
    private function getPropertyValue(SimpleXMLElement $propertyNode)
    {
        if (isset($propertyNode->value)) {
            return (string) $propertyNode->value;
        }
        return (string) $propertyNode['value'];
    }
}
