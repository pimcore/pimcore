<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

interface RuleInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     *
     * @return RuleInterface
     */
    public function setId($id);

    /**
     * @param string $name
     *
     * @return RuleInterface
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
     * @return RuleInterface
     */
    public function setLabel($label, $locale = null);

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getLabel($locale = null);

    /**
     * @param string $description
     * @param string|null $locale
     *
     * @return RuleInterface
     */
    public function setDescription($description, $locale = null);

    /**
     * @param string|null $locale
     *
     * @return string mixed
     */
    public function getDescription($locale = null);

    /**
     * @param ConditionInterface $condition
     *
     * @return RuleInterface
     */
    public function setCondition(ConditionInterface $condition);

    /**
     * @return ConditionInterface|null
     */
    public function getCondition();

    /**
     * @param array $action
     *
     * @return RuleInterface
     */
    public function setActions(array $action);

    /**
     * @return array|ActionInterface
     */
    public function getActions();

    /**
     * @param bool $active
     *
     * @return RuleInterface
     */
    public function setActive($active);

    /**
     * @return bool
     */
    public function getActive();

    /**
     * @param string $behavior
     *
     * @return RuleInterface
     */
    public function setBehavior($behavior);

    /**
     * @return string
     */
    public function getBehavior();

    /**
     * test all conditions if this rule is valid
     *
     * @param EnvironmentInterface $environment
     *
     * @return bool
     */
    public function check(EnvironmentInterface $environment);

    /**
     * checks if rule has at least one action that changes product price (and not cart price)
     *
     * @return bool
     */
    public function hasProductActions();

    /**
     * checks if rule has at least one action that changes cart price
     *
     * @return bool
     */
    public function hasCartActions();

    /**
     * execute rule actions based on current product
     *
     * @param EnvironmentInterface $environment
     *
     * @return RuleInterface
     */
    public function executeOnProduct(EnvironmentInterface $environment);

    /**
     * execute rule actions based on current cart
     *
     * @param EnvironmentInterface $environment
     *
     * @return RuleInterface
     */
    public function executeOnCart(EnvironmentInterface $environment);

    /**
     * @param string $typeClass
     *
     * @return ConditionInterface[]
     */
    public function getConditionsByType(string $typeClass): array;

    /**
     * @param int $prio
     *
     * @return RuleInterface
     */
    public function setPrio($prio);

    /**
     * @return int
     */
    public function getPrio();

    /**
     * @return RuleInterface
     */
    public function save();

    /**
     * delete item
     */
    public function delete();
}
