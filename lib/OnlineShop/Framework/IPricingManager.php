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


interface OnlineShop_Framework_IPricingManager
{
    /**
     * @param OnlineShop_Framework_IPriceInfo $priceinfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function applyProductRules(OnlineShop_Framework_IPriceInfo $priceinfo);

    /**
     * @param OnlineShop_Framework_ICart $cart
     *
     * @return OnlineShop_Framework_IPricingManager
     */
    public function applyCartRules(OnlineShop_Framework_ICart $cart);

    /**
     * Factory
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function getRule();

    /**
     * Factory
     * @param string $type
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getCondition($type);

    /**
     * Factory
     * @param $type
     *
     * @return OnlineShop_Framework_Pricing_IAction
     */
    public function getAction($type);

    /**
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function getEnvironment();

    /**
     * @param OnlineShop_Framework_IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function getPriceInfo(OnlineShop_Framework_IPriceInfo $priceInfo);
}