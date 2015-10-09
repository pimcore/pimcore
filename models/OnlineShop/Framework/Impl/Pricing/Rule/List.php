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


class OnlineShop_Framework_Impl_Pricing_Rule_List extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array|OnlineShop_Framework_Pricing_IRule
     */
    protected $rules;

    /**
     * @var boolean
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
        return in_array($key, array('prio', 'name'));
    }

    /**
     * @return array
     */
    function getRules()
    {
        // load rules if not loaded yet
        if(empty($this->rules))
            $this->load();

        return $this->rules;
    }

    /**
     * @param array $rules
     * @return void
     */
    function setRules(array $rules)
    {
        $this->rules = $rules;
    }

}
