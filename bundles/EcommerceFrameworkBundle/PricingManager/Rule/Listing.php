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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;

/**
 * @method Rule[] load()
 * @method Rule current()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    /**
     * @var RuleInterface[]
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $rules;

    /**
     * @var bool
     */
    protected $validate;

    public function __construct()
    {
        $this->rules = & $this->data;
    }

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
     * @return self
     */
    public function setRules(array $rules)
    {
        return $this->setData($rules);
    }
}
