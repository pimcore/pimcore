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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem;
use OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator;
use OnlineShop\Framework\Model\ICheckoutable;
use OnlineShop\Framework\PriceSystem\TaxManagement\TaxCalculationService;
use OnlineShop\Framework\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Model\Object\OnlineShopTaxClass;
use Pimcore\Model\WebsiteSetting;

/**
 * Class AbstractPriceSystem
 *
 * abstract implementation for price systems
 */
abstract class AbstractPriceSystem implements IPriceSystem {

    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }


     /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $abstractProduct
     * @param int | string $quantityScale
     *    quantityScale - numeric or string (allowed values: \OnlineShop\Framework\PriceSystem\IPriceInfo::MIN_PRICE
     * @param \OnlineShop\Framework\Model\ICheckoutable[] $products
     * @return IPriceInfo
     */
    public function getPriceInfo(\OnlineShop\Framework\Model\ICheckoutable $abstractProduct, $quantityScale = null, $products = null) {
        return $this->initPriceInfoInstance($quantityScale,$abstractProduct,$products);
    }


    /**
     * returns shop-instance specific implementation of priceInfo, override this method in your own price system to
     * set any price values
     * @param $quantityScale
     * @param $product
     * @param $products
     * @return IPriceInfo
     */
    protected function initPriceInfoInstance($quantityScale,$product,$products) {
        $priceInfo = $this->createPriceInfoInstance($quantityScale,$product,$products);

        if($quantityScale !== IPriceInfo::MIN_PRICE)
        {
            $priceInfo->setQuantity($quantityScale);
        }

        $priceInfo->setProduct($product);
        $priceInfo->setProducts($products);
        $priceInfo->setPriceSystem($this);

        // apply pricing rules
        $priceInfoWithRules = \OnlineShop\Framework\Factory::getInstance()->getPricingManager()->applyProductRules( $priceInfo );

        return $priceInfoWithRules;


    }


    /**
     * @param $quantityScale
     * @param $product
     * @param $products
     *
     * @internal param $infoConstructorParams
     * @return AbstractPriceInfo
     */
    abstract function createPriceInfoInstance($quantityScale,$product,$products);

    /**
     * Sample implementation for getting the correct OnlineShopTaxClass. In this case Tax Class is retrieved from
     * Website Setting and if no Website Setting is set it creates an empty new Tax Class.
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @return OnlineShopTaxClass
     */
    protected function getDefaultTaxClass() {
        $taxClass =  WebsiteSetting::getByName("defaultTaxClass");

        if($taxClass) {
            $taxClass = OnlineShopTaxClass::getById($taxClass->getData());
        }

        if(empty($taxClass)) {
            $taxClass = new OnlineShopTaxClass();
            $taxClass->setTaxEntryCombinationType(TaxEntry::CALCULATION_MODE_COMBINE);
        }

        return $taxClass;
    }

    /**
     * Returns OnlineShopTaxClass for given ICheckoutable.
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @param ICheckoutable $product
     * @param $environment
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForProduct(ICheckoutable $product) {
        return $this->getDefaultTaxClass();
    }

    /**
     * Returns OnlineShopTaxClass for given ICartPriceModificator
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @param ICartPriceModificator $modificator
     * @param $environment
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForPriceModification(ICartPriceModificator $modificator) {
        return $this->getDefaultTaxClass();
    }

    /**
     * @return TaxCalculationService
     */
    protected function getTaxCalculationService() {
        return new TaxCalculationService();
    }
}

