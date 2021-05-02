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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AbstractPriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;

/**
 * Abstract base class for pimcore objects who should be used as set products in the online shop framework
 */
abstract class AbstractSetProduct extends AbstractProduct
{
    /**
     * returns mandatory products for a set product
     *
     * @throws UnsupportedException
     *
     * @return AbstractSetProductEntry[]
     */
    abstract public function getMandatoryProductEntries(): ?array;

    /**
     * returns optional products for a set product
     *
     * @throws UnsupportedException
     *
     * @return AbstractSetProductEntry[]
     */
    abstract public function getOptionalProductEntries(): ?array;

    /**
     * checks if product is bookable
     * default implementation checks if set product is active, given products are bookable and set product has a valid price
     * if no products given, mandatory products are used
     *
     * @param int $quantityScale
     * @param AbstractSetProductEntry[] $products
     *
     * @return bool
     */
    public function getOSIsBookable($quantityScale = 1, $products = null): bool
    {
        if ($this->isActive()) {
            if (empty($products)) {
                $products = $this->getMandatoryProductEntries();
            }
            if (!empty($products)) {
                foreach ($products as $productEntry) {
                    if ($productEntry->getQuantity() > 0) {
                        if (!$productEntry->getProduct()->getOSIsBookable($productEntry->getQuantity())) {
                            return false;
                        }
                    }
                }
            }
            //set is only bookable when price is valid!!! //
            $priceInfo = $this->getOSPriceInfo($quantityScale, $products);

            return $priceInfo != null;
        } else {
            return false;
        }
    }

    /**
     * Delivers min price for given products or with default mandatory products of set product
     *
     * @throws UnsupportedException
     *
     * @param int $quantityScale
     * @param array|null $products
     *
     * @return PriceInterface
     */
    public function getOSPrice($quantityScale = null, $products = null): ?PriceInterface
    {
        if ($this->getOSPriceInfo($quantityScale, $products)) {
            return $this->getOSPriceInfo($quantityScale, $products)->getPrice();
        }

        return null;
    }

    /**
     * Delivers priceinfo with min price for given products or with  default mandatory products of set product
     *
     * @throws UnsupportedException
     *
     * @param int $quantityScale
     * @param array|null $products
     *
     * @return PriceInfoInterface|AbstractPriceInfo
     */
    public function getOSPriceInfo($quantityScale = null, $products = null): ?PriceInfoInterface
    {
        if (!is_array($products)) {
            $products = $this->getMandatoryProductEntries();
        }

        return $this->getPriceSystemImplementation()->getPriceInfo($this, $quantityScale, $products);
    }

    /**
     * @param int $quantity
     * @param AbstractSetProductEntry[] $products
     *
     * @return AvailabilityInterface
     */
    public function getOSAvailabilityInfo($quantity = null, $products = null): ?AvailabilityInterface
    {
        if ($quantity === null) {
            $quantity = 1;
        }

        if (!is_array($products)) {
            $products = $this->getMandatoryProductEntries();
        }

        return $this->getAvailabilitySystemImplementation()->getAvailabilityInfo($this, $quantity, $products);
    }

    /**
     * checks if all mandatory of set products are set in given product list
     *
     * @throws UnsupportedException
     *
     * @param  AbstractSetProductEntry[] $products
     *
     * @return void
     */
    protected function checkMandatoryProducts($products)
    {
        $mandatoryProducts = $this->getMandatoryProductEntries();
        $mandatoryProductIds = [];
        foreach ($mandatoryProducts as $p) {
            $mandatoryProductIds[$p->getId()] = $p->getQuantity();
        }

        foreach ($products as $product) {
            if ($mandatoryProductIds[$product->getId()]) {
                $mandatoryProductIds[$product->getId()] -= $product->getQuantity();
                if ($mandatoryProductIds[$product->getId()] == 0) {
                    unset($mandatoryProductIds[$product->getId()]);
                }
            }
        }

        if (count($mandatoryProductIds) > 0) {
            throw new UnsupportedException('Not all mandatory Products in product list.');
        }
    }
}
