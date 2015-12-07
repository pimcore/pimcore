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

namespace OnlineShop\Framework\PricingManager\Rule\Listing;

class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    /**
     * @var string
     */
    protected $ruleClass = '\OnlineShop\Framework\PricingManager\Rule';

    /**
     * @return array
     */
    public function load() {
        $rules = array();

        // load objects
        $ruleIds = $this->db->fetchCol("SELECT id FROM " . \OnlineShop\Framework\PricingManager\Rule\Dao::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($ruleIds as $id)
            $rules[] = call_user_func(array($this->getRuleClass(), 'getById'), $id);

        $this->model->setRules($rules);

        return $rules;
    }

    public function setRuleClass($cartClass)
    {
        $this->ruleClass = $cartClass;
    }

    public function getRuleClass()
    {
        return $this->ruleClass;
    }

}