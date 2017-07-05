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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo;

interface IPricingManager
{
    /**
     * @param IPriceInfo $priceinfo
     *
     * @return IPriceInfo
     */
    public function applyProductRules(IPriceInfo $priceinfo);

    /**
     * @param ICart $cart
     *
     * @return IPricingManager
     */
    public function applyCartRules(ICart $cart);

    /**
     * Factory
     *
     * @return IRule
     */
    public function getRule();

    /**
     * Factory
     *
     * @param string $type
     *
     * @return ICondition
     *
     * @throws InvalidConfigException
     */
    public function getCondition($type);

    /**
     * Factory
     *
     * @param $type
     *
     * @return IAction
     */
    public function getAction($type);

    /**
     * @return IEnvironment
     */
    public function getEnvironment();

    /**
     * @param IPriceInfo $priceInfo
     *
     * @return IPriceInfo
     */
    public function getPriceInfo(IPriceInfo $priceInfo);
}
