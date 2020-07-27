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

use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributePriceSystem extends CachingPriceSystem implements PriceSystemInterface
{
    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var string
     */
    protected $attributeName;

    /**
     * @var string
     */
    protected $priceType;

    /**
     * @var string
     */
    protected $priceClass;

    public function __construct(PricingManagerLocatorInterface $pricingManagers, EnvironmentInterface $environment, array $options = [])
    {
        parent::__construct($pricingManagers);

        $this->environment = $environment;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));
    }

    protected function processOptions(array $options)
    {
        $this->attributeName = $options['attribute_name'];
        $this->priceClass = $options['price_class'];
        $this->priceType = $options['price_type'];
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'attribute_name',
            'price_class',
        ]);

        $resolver->setDefaults([
            'attribute_name' => 'price',
            'price_class' => Price::class,
            'price_type' => TaxCalculationService::CALCULATION_FROM_GROSS,
        ]);

        $resolver->setAllowedTypes('attribute_name', 'string');
        $resolver->setAllowedTypes('price_class', 'string');
        $resolver->setAllowedTypes('price_type', 'string');
    }

    /**
     * @inheritdoc
     */
    public function createPriceInfoInstance($quantityScale, CheckoutableInterface $product, $products): PriceInfoInterface
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
        $taxCalculationService->updateTaxes($price, $this->priceType);
        $taxCalculationService->updateTaxes($totalPrice, $this->priceType);

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
     * @param CheckoutableInterface $product
     * @param CheckoutableInterface[] $products
     *
     * @return Decimal
     */
    protected function calculateAmount(CheckoutableInterface $product, $products): Decimal
    {
        $getter = 'get' . ucfirst($this->attributeName);

        if (is_callable([$product, $getter])) {
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
                return Decimal::create((float) $product->$getter());
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
        return $this->environment->getDefaultCurrency();
    }

    /**
     * Creates instance of PriceInterface
     *
     * @param Decimal $amount
     *
     * @return PriceInterface
     */
    protected function getPriceClassInstance(Decimal $amount): PriceInterface
    {
        $priceClass = $this->priceClass;
        $price = new $priceClass($amount, $this->getDefaultCurrency(), false);

        return $price;
    }
}
