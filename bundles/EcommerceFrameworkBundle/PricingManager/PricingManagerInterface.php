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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface as PriceSystemPriceInfoInterface;

interface PricingManagerInterface
{
    /**
     * @param PriceSystemPriceInfoInterface $priceinfo
     *
     * @return PriceSystemPriceInfoInterface
     */
    public function applyProductRules(PriceSystemPriceInfoInterface $priceinfo);

    /**
     * @param CartInterface $cart
     *
     * @return RuleInterface[] applied rules
     */
    public function applyCartRules(CartInterface $cart): array;

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
     * @return ConditionInterface
     *
     * @throws InvalidConfigException
     */
    public function getCondition($type);

    /**
     * Factory
     *
     * @param string $type
     *
     * @return ActionInterface
     */
    public function getAction($type);

    /**
     * Factory
     *
     * @return EnvironmentInterface
     */
    public function getEnvironment();

    /**
     * Wraps price info in pricing manager price info
     *
     * @param PriceSystemPriceInfoInterface $priceInfo
     *
     * @return PriceSystemPriceInfoInterface|PriceInfoInterface
     */
    public function getPriceInfo(PriceSystemPriceInfoInterface $priceInfo);
}

class_alias(PricingManagerInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManager');
