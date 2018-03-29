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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class CartAmount implements ICartAmount
{
    CONST CALCULATION_MODE_ONLY_CART = "only_cart";
    CONST CALCULATION_MODE_PRODUCT_AND_CART = "product_and_cart";

    /**
     * @var float
     */
    protected $limit;

    /**
     * @var string
     */
    protected $mode = self::CALCULATION_MODE_ONLY_CART;

    /**
     * @param IEnvironment $environment
     *
     * @return bool
     */
    public function check(IEnvironment $environment)
    {
        if($this->mode == self::CALCULATION_MODE_PRODUCT_AND_CART) {
            return $this->checkProductAndCart($environment);
        } else {
            return $this->checkOnlyCart($environment);
        }
    }

    /**
     * @param IEnvironment $environment
     * @return bool
     * @throws \TypeError
     */
    protected function checkOnlyCart(IEnvironment $environment) {
        if (!$environment->getCart() || $environment->getProduct() !== null) {
            return false;
        }
        $calculator = $environment->getCart()->getPriceCalculator();
        return $calculator->getSubTotal()->getAmount()->greaterThanOrEqual(Decimal::create($this->getLimit()));
    }

    /**
     * @param IEnvironment $environment
     * @return bool
     * @throws \TypeError
     */
    protected function checkProductAndCart(IEnvironment $environment) {
        if ($environment->getCart() && $environment->getProduct() !== null) {
            $calculator = $environment->getCart()->getPriceCalculator();

            return $calculator->getSubTotal()->getAmount()->greaterThanOrEqual(Decimal::create($this->getLimit()));
        }

        return false;
    }

    /**
     * @param float $limit
     *
     * @return ICartAmount
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return float
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode(string $mode)
    {
        $this->mode = $mode;
    }


    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type' => 'CartAmount',
            'limit' => $this->getLimit(),
            'mode' => $this->getMode()
        ]);
    }

    /**
     * @param string $string
     *
     * @return ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $this->setLimit($json->limit);
        $this->setMode($json->mode);

        return $this;
    }
}
