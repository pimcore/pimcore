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
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo as PriceSystemIPriceInfo;

interface IPricingManager
{
    /**
     * @param PriceSystemIPriceInfo $priceinfo
     *
     * @return PriceSystemIPriceInfo
     */
    public function applyProductRules(PriceSystemIPriceInfo $priceinfo);

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
     * Get map from action name to used class
     *
     * @return array
     */
    public function getActionMapping(): array;

    /**
     * Get map from condition name to used class
     *
     * @return array
     */
    public function getConditionMapping(): array;

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
     * Factory
     *
     * @return IEnvironment
     */
    public function getEnvironment();

    /**
     * Wraps price info in pricing manager price info
     *
     * @param PriceSystemIPriceInfo $priceInfo
     *
     * @return PriceSystemIPriceInfo|IPriceInfo
     */
    public function getPriceInfo(PriceSystemIPriceInfo $priceInfo);
}
