<?php

declare(strict_types=1);

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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\CartPriceModificatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManagerLocatorInterface;
use Pimcore\Model\DataObject\OnlineShopTaxClass;
use Pimcore\Model\WebsiteSetting;

abstract class AbstractPriceSystem implements PriceSystemInterface
{
    /**
     * @var PricingManagerLocatorInterface
     */
    protected $pricingManagers;

    public function __construct(PricingManagerLocatorInterface $pricingManagers)
    {
        $this->pricingManagers = $pricingManagers;
    }

    /**
     * @inheritdoc
     */
    public function getPriceInfo(CheckoutableInterface $product, $quantityScale = null, $products = null): PriceInfoInterface
    {
        return $this->initPriceInfoInstance($quantityScale, $product, $products);
    }

    /**
     * Returns shop-instance specific implementation of priceInfo, override this method in your own price system to
     * set any price values
     *
     * @param null|int|string $quantityScale Numeric or string (allowed values: PriceInfoInterface::MIN_PRICE)
     * @param CheckoutableInterface $product
     * @param CheckoutableInterface[] $products
     *
     * @return PriceInfoInterface
     */
    protected function initPriceInfoInstance($quantityScale, CheckoutableInterface $product, $products)
    {
        $priceInfo = $this->createPriceInfoInstance($quantityScale, $product, $products);

        if ($quantityScale !== PriceInfoInterface::MIN_PRICE) {
            $priceInfo->setQuantity($quantityScale);
        }

        $priceInfo->setProduct($product);
        $priceInfo->setProducts($products);
        $priceInfo->setPriceSystem($this);

        // apply pricing rules
        $priceInfoWithRules = $this->getPricingManager()->applyProductRules($priceInfo);

        return $priceInfoWithRules;
    }

    protected function getPricingManager(): PricingManagerInterface
    {
        return $this->pricingManagers->getPricingManager();
    }

    /**
     * @param null|int|string $quantityScale Numeric or string (allowed values: PriceInfoInterface::MIN_PRICE)
     * @param CheckoutableInterface $product
     * @param CheckoutableInterface[] $products
     *
     * @return AbstractPriceInfo
     */
    abstract public function createPriceInfoInstance($quantityScale, CheckoutableInterface $product, $products);

    /**
     * Sample implementation for getting the correct OnlineShopTaxClass. In this case Tax Class is retrieved from
     * Website Setting and if no Website Setting is set it creates an empty new Tax Class.
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @return OnlineShopTaxClass
     */
    protected function getDefaultTaxClass()
    {
        $taxClass = WebsiteSetting::getByName('defaultTaxClass');

        if ($taxClass) {
            $taxClass = $taxClass->getData();
        }

        if (empty($taxClass)) {
            $taxClass = new OnlineShopTaxClass();
            $taxClass->setTaxEntryCombinationType(TaxEntry::CALCULATION_MODE_COMBINE);
        }

        return $taxClass;
    }

    /**
     * Returns OnlineShopTaxClass for given CheckoutableInterface.
     *
     * @param CheckoutableInterface $product
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForProduct(CheckoutableInterface $product)
    {
        return $this->getDefaultTaxClass();
    }

    /**
     * Returns OnlineShopTaxClass for given CartPriceModificatorInterface
     *
     * @param CartPriceModificatorInterface $modificator
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForPriceModification(CartPriceModificatorInterface $modificator)
    {
        return $this->getDefaultTaxClass();
    }

    /**
     * @return TaxCalculationService
     */
    protected function getTaxCalculationService()
    {
        return new TaxCalculationService();
    }
}
