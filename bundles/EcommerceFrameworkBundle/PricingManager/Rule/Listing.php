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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;

/**
 * @method Rule[] load()
 * @method Rule|false current()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    /**
     * @var bool
     */
    protected $validate;

    /**
     * @param bool $state
     */
    public function setValidation($state)
    {
        $this->validate = (bool)$state;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return in_array($key, ['prio', 'name']);
    }

    /**
     * @return RuleInterface[]
     */
    public function getRules()
    {
        return $this->getData();
    }

    /**
     * @param RuleInterface[] $rules
     *
     * @return $this
     */
    public function setRules(array $rules)
    {
        return $this->setData($rules);
    }
}
