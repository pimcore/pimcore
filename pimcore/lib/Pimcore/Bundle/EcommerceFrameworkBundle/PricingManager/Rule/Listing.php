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

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule;

class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    /**
     * @var IRule[]
     */
    protected $rules;

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
     * @param $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return in_array($key, ['prio', 'name']);
    }

    /**
     * @return IRule[]
     */
    public function getRules()
    {
        // load rules if not loaded yet
        if (empty($this->rules)) {
            $this->load();
        }

        return $this->rules;
    }

    /**
     * @param IRule[] $rules
     *
     * @return void
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }
}
