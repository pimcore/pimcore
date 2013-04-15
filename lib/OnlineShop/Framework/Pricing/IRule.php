<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:10
 * To change this template use File | Settings | File Templates.
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
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setLabel($label);

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param $description
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setDescription($description);

    /**
     * @return string mixed
     */
    public function getDescription();

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