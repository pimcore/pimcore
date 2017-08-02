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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManager;
use Pimcore\Model\Object\OnlineShopTaxClass;
use Pimcore\Model\WebsiteSetting;

abstract class AbstractPriceSystem implements IPriceSystem
{
    /**
     * @var IPricingManager
     */
    protected $pricingManager;

    /**
     * @param IPricingManager $pricingManager
     */
    public function __construct(IPricingManager $pricingManager)
    {
        $this->pricingManager = $pricingManager;
    }

    /**
     * @inheritdoc
     */
    public function getPriceInfo(ICheckoutable $product, $quantityScale = null, $products = null): IPriceInfo
    {
        return $this->initPriceInfoInstance($quantityScale, $product, $products);
    }

    /**
     * Returns shop-instance specific implementation of priceInfo, override this method in your own price system to
     * set any price values
     *
     * @param null|int|string $quantityScale Numeric or string (allowed values: IPriceInfo::MIN_PRICE)
     * @param ICheckoutable $product
     * @param ICheckoutable[] $products
     *
     * @return IPriceInfo
     */
    protected function initPriceInfoInstance($quantityScale, ICheckoutable $product, $products)
    {
        $priceInfo = $this->createPriceInfoInstance($quantityScale, $product, $products);

        if ($quantityScale !== IPriceInfo::MIN_PRICE) {
            $priceInfo->setQuantity($quantityScale);
        }

        $priceInfo->setProduct($product);
        $priceInfo->setProducts($products);
        $priceInfo->setPriceSystem($this);

        // apply pricing rules
        $priceInfoWithRules = $this->pricingManager->applyProductRules($priceInfo);

        return $priceInfoWithRules;
    }

    /**
     * @param null|int|string $quantityScale Numeric or string (allowed values: IPriceInfo::MIN_PRICE)
     * @param ICheckoutable $product
     * @param ICheckoutable[] $products
     *
     * @return AbstractPriceInfo
     */
    abstract public function createPriceInfoInstance($quantityScale, ICheckoutable $product, $products);

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
            $taxClass = OnlineShopTaxClass::getById($taxClass->getData());
        }

        if (empty($taxClass)) {
            $taxClass = new OnlineShopTaxClass();
            $taxClass->setTaxEntryCombinationType(TaxEntry::CALCULATION_MODE_COMBINE);
        }

        return $taxClass;
    }

    /**
     * Returns OnlineShopTaxClass for given ICheckoutable.
     *
     * @param ICheckoutable $product
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForProduct(ICheckoutable $product)
    {
        return $this->getDefaultTaxClass();
    }

    /**
     * Returns OnlineShopTaxClass for given ICartPriceModificator
     *
     * @param ICartPriceModificator $modificator
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForPriceModification(ICartPriceModificator $modificator)
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
