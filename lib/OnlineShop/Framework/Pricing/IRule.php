<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


interface OnlineShop_Framework_Pricing_IRule
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param $id
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setId($id);

    /**
     * @param string $name
     *
     * @return OnlineShop_Framework_Pricing_IRule
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
     * @return OnlineShop_Framework_Pricing_IRule
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
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setDescription($description, $locale = null);

    /**
     * @param string $locale
     *
     * @return string mixed
     */
    public function getDescription($locale = null);

    /**
     * @param OnlineShop_Framework_Pricing_ICondition
     *
     * @return OnlineShop_Framework_IRule
     */
    public function setCondition(OnlineShop_Framework_Pricing_ICondition $condition);

    /**
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function getCondition();

    /**
     * @param array $action
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setActions(array $action);

    /**
     * @return array|OnlineShop_Framework_Pricing_IAction
     */
    public function getActions();

    /**
     * @param boolean $active
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setActive($active);

    /**
     * @return boolean
     */
    public function getActive();

    /**
     * @param $behavior
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setBehavior($behavior);

    /**
     * @return string
     */
    public function getBehavior();

    /**
     * test all conditions if this rule is valid
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment);

    /**
     * execute rule actions based on current product
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function executeOnProduct(OnlineShop_Framework_Pricing_IEnvironment $environment);

    /**
     * execute rule actions based on current cart
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function executeOnCart(OnlineShop_Framework_Pricing_IEnvironment $environment);

    /**
     * @param int $prio
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setPrio($prio);

    /**
     * @return int
     */
    public function getPrio();

    /**
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function save();

    /**
     * delete item
     */
    public function delete();
}