<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
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
     * @return AbstractSetProductEntry[]|null
     */
    abstract public function getMandatoryProductEntries(): ?array;

    /**
     * returns optional products for a set product
     *
     * @throws UnsupportedException
     *
     * @return AbstractSetProductEntry[]|null
     */
    abstract public function getOptionalProductEntries(): ?array;

    /**
     * checks if product is bookable
     * default implementation checks if set product is active, given products are bookable and set product has a valid price
     * if no products given, mandatory products are used
     *
     * @param int $quantityScale
     * @param AbstractSetProductEntry[]|null $products
     *
     * @return bool
     */
    public function getOSIsBookable(int $quantityScale = 1, array $products = null): bool
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
     * @param int|null $quantityScale
     * @param array|null $products
     *
     * @return PriceInterface
     *
     * @throws UnsupportedException
     */
    public function getOSPrice(int $quantityScale = null, array $products = null): PriceInterface
    {
        return $this->getOSPriceInfo($quantityScale, $products)->getPrice();
    }

    /**
     * Delivers priceinfo with min price for given products or with  default mandatory products of set product
     *
     * @param int|null $quantityScale
     * @param array|null $products
     *
     * @return PriceInfoInterface
     *
     * @throws UnsupportedException
     */
    public function getOSPriceInfo(int $quantityScale = null, ?array $products = null): PriceInfoInterface
    {
        if (!is_array($products)) {
            $products = $this->getMandatoryProductEntries();
        }

        return $this->getPriceSystemImplementation()->getPriceInfo($this, $quantityScale, $products);
    }

    /**
     * @param int|null $quantity
     * @param AbstractSetProductEntry[]|null $products
     *
     * @return AvailabilityInterface
     *
     * @throws UnsupportedException
     */
    public function getOSAvailabilityInfo(int $quantity = null, ?array $products = null): AvailabilityInterface
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
     * @param  AbstractSetProductEntry[] $products
     *
     * @return void
     *
     * @throws UnsupportedException
     */
    protected function checkMandatoryProducts(array $products): void
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
