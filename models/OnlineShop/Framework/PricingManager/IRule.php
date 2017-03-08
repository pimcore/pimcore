<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop\Framework\PricingManager;

interface IRule
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param $id
     *
     * @return IRule
     */
    public function setId($id);

    /**
     * @param string $name
     *
     * @return IRule
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $label
     * @param string $locale
     *
     * @return IRule
     */
    public function setLabel($label, $locale = null);

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getLabel($locale = null);

    /**
     * @param $description
     * @param string $locale
     *
     * @return IRule
     */
    public function setDescription($description, $locale = null);

    /**
     * @param string $locale
     *
     * @return string mixed
     */
    public function getDescription($locale = null);

    /**
     * @param ICondition
     *
     * @return IRule
     */
    public function setCondition(ICondition $condition);

    /**
     * @return ICondition
     */
    public function getCondition();

    /**
     * @param array $action
     *
     * @return IRule
     */
    public function setActions(array $action);

    /**
     * @return array|IAction
     */
    public function getActions();

    /**
     * @param boolean $active
     *
     * @return IRule
     */
    public function setActive($active);

    /**
     * @return boolean
     */
    public function getActive();

    /**
     * @param $behavior
     *
     * @return IRule
     */
    public function setBehavior($behavior);

    /**
     * @return string
     */
    public function getBehavior();

    /**
     * test all conditions if this rule is valid
     * @param IEnvironment $environment
     *
     * @return boolean
     */
    public function check(IEnvironment $environment);

    /**
     * check if rule has at least one action that changes product price (and not cart price)
     *
     * @return bool
     */
    public function hasProductActions();

    /**
     * execute rule actions based on current product
     * @param IEnvironment $environment
     *
     * @return IRule
     */
    public function executeOnProduct(IEnvironment $environment);

    /**
     * execute rule actions based on current cart
     * @param IEnvironment $environment
     *
     * @return IRule
     */
    public function executeOnCart(IEnvironment $environment);

    /**
     * @param int $prio
     *
     * @return IRule
     */
    public function setPrio($prio);

    /**
     * @return int
     */
    public function getPrio();

    /**
     * @return IRule
     */
    public function save();

    /**
     * delete item
     */
    public function delete();
}