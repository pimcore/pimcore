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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

/**
 * Price system implementation for attribute price system
 */
class AttributePriceSystem extends CachingPriceSystem implements IPriceSystem
{
    /**
     * @inheritdoc
     */
    public function createPriceInfoInstance($quantityScale, ICheckoutable $product, $products): IPriceInfo
    {
        $taxClass = $this->getTaxClassForProduct($product);

        $amount = $this->calculateAmount($product, $products);
        $price = $this->getPriceClassInstance($amount);
        $totalPrice = $this->getPriceClassInstance($amount->mul($quantityScale));

        if ($taxClass) {
            $price->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
            $price->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));

            $totalPrice->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
            $totalPrice->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));
        }

        $taxCalculationService = $this->getTaxCalculationService();
        $taxCalculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_GROSS);
        $taxCalculationService->updateTaxes($totalPrice, TaxCalculationService::CALCULATION_FROM_GROSS);

        return new AttributePriceInfo($price, $quantityScale, $totalPrice);
    }

    /**
     * @inheritdoc
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit)
    {
        throw new UnsupportedException(__METHOD__  . ' is not supported for ' . get_class($this));
    }

    /**
     * Calculates prices from product
     *
     * @param ICheckoutable $product
     * @param ICheckoutable[] $products
     *
     * @return Decimal
     */
    protected function calculateAmount(ICheckoutable $product, $products): Decimal
    {
        $getter = 'get' . ucfirst($this->config->attributename);
        if (method_exists($product, $getter)) {
            if (!empty($products)) {
                // TODO where to start using price value object?
                $sum = 0;
                foreach ($products as $p) {
                    if ($p instanceof AbstractSetProductEntry) {
                        $sum += $p->getProduct()->$getter() * $p->getQuantity();
                    } else {
                        $sum += $p->$getter();
                    }
                }

                return Decimal::create($sum);
            } else {
                return Decimal::create($product->$getter());
            }
        }

        return Decimal::zero();
    }

    /**
     * Returns default currency based on environment settings
     *
     * @return Currency
     */
    protected function getDefaultCurrency(): Currency
    {
        return Factory::getInstance()->getEnvironment()->getDefaultCurrency();
    }

    /**
     * Creates instance of IPrice
     *
     * @param Decimal $amount
     *
     * @return IPrice
     *
     * @throws \Exception
     */
    protected function getPriceClassInstance(Decimal $amount): IPrice
    {
        if ($this->config->priceClass) {
            $price = new $this->config->priceClass($amount, $this->getDefaultCurrency(), false);
            if (!$price instanceof IPrice) {
                throw new \Exception('Price Class does not implement IPrice');
            }
        } else {
            $price = new Price($amount, $this->getDefaultCurrency(), false);
        }

        return $price;
    }
}
