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
 * Base interface for a PHPMD rule.
 *
 * @category  PHP
 * @package   PHP_PMD
 * @author    Manuel Pichler <mapi@phpmd.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: 1.4.1
 * @link      http://phpmd.org
 * @since     1.1.0
 */
interface PHP_PMD_Rule
{
    /**
     * The default lowest rule priority.
     */
    const LOWEST_PRIORITY = 5;

    /**
     * Returns the name for this rule instance.
     *
     * @return string
     */
    function getName();

    /**
     * Sets the name for this rule instance.
     *
     * @param string $name The rule name.
     *
     * @return void
     */
    function setName($name);

    /**
     * Returns the version since when this rule is available or <b>null</b>.
     *
     * @return string
     */
    function getSince();

    /**
     * Sets the version since when this rule is available.
     *
     * @param string $since The version number.
     *
     * @return void
     */
    function setSince($since);

    /**
     * Returns the violation message text for this rule.
     *
     * @return string
     */
    function getMessage();

    /**
     * Sets the violation message text for this rule.
     *
     * @param string $message The violation message
     *
     * @return void
     */
    function setMessage($message);

    /**
     * Returns an url will external information for this rule.
     *
     * @return string
     */
    function getExternalInfoUrl();

    /**
     * Sets an url will external information for this rule.
     *
     * @param string $externalInfoUrl The info url.
     *
     * @return void
     */
    function setExternalInfoUrl($externalInfoUrl);

    /**
     * Returns the description text for this rule instance.
     *
     * @return string
     */
    function getDescription();

    /**
     * Sets the description text for this rule instance.
     *
     * @param string $description The description text.
     *
     * @return void
     */
    function setDescription($description);

    /**
     * Returns a list of examples for this rule.
     *
     * @return array(string)
     */
    function getExamples();

    /**
     * Adds a code example for this rule.
     *
     * @param string $example The code example.
     *
     * @return void
     */
    function addExample($example);

    /**
     * Returns the priority of this rule.
     *
     * @return integer
     */
    function getPriority();

    /**
     * Set the priority of this rule.
     *
     * @param integer $priority The rule priority
     *
     * @return void
     */
    function setPriority($priority);

    /**
     * Returns the name of the parent rule-set instance.
     *
     * @return string
     */
    function getRuleSetName();

    /**
     * Sets the name of the parent rule set instance.
     *
     * @param string $ruleSetName The rule-set name.
     *
     * @return void
     */
    function setRuleSetName($ruleSetName);

    /**
     * Returns the violation report for this rule.
     *
     * @return PHP_PMD_Report
     */
    function getReport();

    /**
     * Sets the violation report for this rule.
     *
     * @param PHP_PMD_Report $report The report instance.
     *
     * @return void
     */
    function setReport(PHP_PMD_Report $report);

    /**
     * Adds a configuration property to this rule instance.
     *
     * @param string $name  The property name.
     * @param string $value The property value.
     *
     * @return void
     */
    function addProperty($name, $value);

    /**
     * Returns the value of a configured property as a boolean or throws an
     * exception when no property with <b>$name</b> exists.
     *
     * @param string $name The property identifier.
     *
     * @return boolean
     * @throws OutOfBoundsException When no property for <b>$name</b> exists.
     */
    function getBooleanProperty($name);

    /**
     * Returns the value of a configured property as an integer or throws an
     * exception when no property with <b>$name</b> exists.
     *
     * @param string $name The property identifier.
     *
     * @return integer
     * @throws OutOfBoundsException When no property for <b>$name</b> exists.
     */
    function getIntProperty($name);

    /**
     * This method should implement the violation analysis algorithm of concrete
     * rule implementations. All extending classes must implement this method.
     *
     * @param PHP_PMD_AbstractNode $node The current context for analysis.
     *
     * @return void
     */
    function apply(PHP_PMD_AbstractNode $node);
}
