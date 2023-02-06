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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Listing;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Listing;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    protected string $ruleClass = '\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule';

    public function load(): array
    {
        $rules = [];

        // load objects
        $ruleIds = $this->db->fetchFirstColumn('SELECT id FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($ruleIds as $id) {
            $rules[] = call_user_func([$this->getRuleClass(), 'getById'], $id);
        }

        $this->model->setRules($rules);

        return $rules;
    }

    public function setRuleClass(string $cartClass): void
    {
        $this->ruleClass = $cartClass;
    }

    public function getRuleClass(): string
    {
        return $this->ruleClass;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM `' . \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Dao::TABLE_NAME . '`' . $this->getCondition());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
