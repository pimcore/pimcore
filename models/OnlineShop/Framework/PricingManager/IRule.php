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