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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\CartPriceModificatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CartPriceCalculator implements CartPriceCalculatorInterface
{
    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var bool
     */
    protected $isCalculated = false;

    /**
     * @var PriceInterface
     */
    protected $subTotal;

    /**
     * @var PriceInterface
     */
    protected $grandTotal;

    /**
     * Standard modificators are handled as configuration as they may
     * be reinitialized on demand (e.g. inside AJAX calls).
     *
     * @var array
     */
    protected $modificatorConfig = [];

    /**
     * @var CartPriceModificatorInterface[]
     */
    protected $modificators = [];

    /**
     * @var ModificatedPriceInterface[]
     */
    protected $modifications = [];

    /**
     * @var RuleInterface[]
     */
    protected $appliedPricingRules = [];

    /**
     * @var PricingManagerInterface
     */
    protected $pricingManager;

    /**
     * @param EnvironmentInterface $environment
     * @param CartInterface $cart
     * @param array $modificatorConfig
     */
    public function __construct(EnvironmentInterface $environment, CartInterface $cart, array $modificatorConfig = [])
    {
        $this->environment = $environment;
        $this->cart = $cart;

        $this->setModificatorConfig($modificatorConfig);
        $this->initModificators();
    }

    /**
     * (Re-)initialize standard price modificators, e.g. after removing an item from a cart
     * within the same request, such as an AJAX-call.
     */
    public function initModificators()
    {
        $this->reset();

        $this->modificators = [];
        foreach ($this->modificatorConfig as $config) {
            $this->modificators[] = $this->buildModificator($config);
        }
    }

    protected function buildModificator(array $config): CartPriceModificatorInterface
    {
        /** @var CartPriceModificatorInterface $modificator */
        $modificator = null;

        $className = $config['class'];
        if (!empty($config['options'])) {
            $modificator = new $className($config['options']);
        } else {
            $modificator = new $className();
        }

        return $modificator;
    }

    protected function setModificatorConfig(array $modificatorConfig)
    {
        $resolver = new OptionsResolver();
        $this->configureModificatorResolver($resolver);

        foreach ($modificatorConfig as $config) {
            $this->modificatorConfig[] = $resolver->resolve($config);
        }
    }

    protected function configureModificatorResolver(OptionsResolver $resolver)
    {
        $resolver->setDefined(['class', 'options']);
        $resolver->setAllowedTypes('class', 'string');

        $resolver->setDefaults([
            'options' => [],
        ]);
    }

    /**
     * @throws UnsupportedException
     */
    public function calculate($ignorePricingRules = false)
    {
        // sum up all item prices
        $subTotalNet = Decimal::zero();
        $subTotalGross = Decimal::zero();

        /** @var Currency $currency */
        $currency = null;

        /** @var TaxEntry[] $subTotalTaxes */
        $subTotalTaxes = [];

        /** @var TaxEntry[] $grandTotalTaxes */
        $grandTotalTaxes = [];

        foreach ($this->cart->getItems() as $item) {
            if (!is_object($item->getPrice())) {
                continue;
            }

            if (null === $currency) {
                $currency = $item->getPrice()->getCurrency();
            }

            if ($currency->getShortName() !== $item->getPrice()->getCurrency()->getShortName()) {
                throw new UnsupportedException(sprintf(
                    'Different currencies within one cart are not supported. See cart %s and product %s)',
                    $this->cart->getId(),
                    $item->getProduct()->getId()
                ));
            }

            $itemPrice = $item->getTotalPrice();
            $subTotalNet = $subTotalNet->add($itemPrice->getNetAmount());
            $subTotalGross = $subTotalGross->add($itemPrice->getGrossAmount());

            $taxEntries = $item->getTotalPrice()->getTaxEntries();
            foreach ($taxEntries as $taxEntry) {
                $taxId = $taxEntry->getTaxId();
                if (empty($subTotalTaxes[$taxId])) {
                    $subTotalTaxes[$taxId] = clone $taxEntry;
                    $grandTotalTaxes[$taxId] = clone $taxEntry;
                } else {
                    $subTotalTaxes[$taxId]->setAmount(
                        $subTotalTaxes[$taxId]->getAmount()->add($taxEntry->getAmount())
                    );

                    $grandTotalTaxes[$taxId]->setAmount(
                        $grandTotalTaxes[$taxId]->getAmount()->add($taxEntry->getAmount())
                    );
                }
            }
        }

        // by default currency is retrieved from item prices. if there are no items, its loaded from the default locale
        // defined in the environment
        if (null === $currency) {
            $currency = $this->getDefaultCurrency();
        }

        // populate subTotal price, set net and gross amount, set tax entries and set tax entry combination mode to fixed
        $this->subTotal = $this->getDefaultPriceObject($subTotalGross, $currency);
        $this->subTotal->setNetAmount($subTotalNet);
        $this->subTotal->setTaxEntries($subTotalTaxes);
        $this->subTotal->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_FIXED);

        // consider all price modificators
        $currentSubTotal = $this->getDefaultPriceObject($subTotalGross, $currency);
        $currentSubTotal->setNetAmount($subTotalNet);
        $currentSubTotal->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_FIXED);

        $this->modifications = [];
        foreach ($this->getModificators() as $modificator) {
            /* @var CartPriceModificatorInterface $modificator */
            $modification = $modificator->modify($currentSubTotal, $this->cart);
            if ($modification !== null) {
                $this->modifications[$modificator->getName()] = $modification;

                $currentSubTotal->setNetAmount(
                    $currentSubTotal->getNetAmount()->add($modification->getNetAmount())
                );

                $currentSubTotal->setGrossAmount(
                    $currentSubTotal->getGrossAmount()->add($modification->getGrossAmount())
                );

                $taxEntries = $modification->getTaxEntries();
                foreach ($taxEntries as $taxEntry) {
                    $taxId = $taxEntry->getTaxId();
                    if (empty($grandTotalTaxes[$taxId])) {
                        $grandTotalTaxes[$taxId] = clone $taxEntry;
                    } else {
                        $grandTotalTaxes[$taxId]->setAmount(
                            $grandTotalTaxes[$taxId]->getAmount()->add($taxEntry->getAmount())
                        );
                    }
                }
            }
        }

        $currentSubTotal->setTaxEntries($grandTotalTaxes);

        $this->grandTotal = $currentSubTotal;
        $this->isCalculated = true;

        if (!$ignorePricingRules) {
            // apply pricing rules
            $this->appliedPricingRules = $this->getPricingManager()->applyCartRules($this->cart);

            //check if some pricing rule needs recalculation of sums
            if (!$this->isCalculated) {
                $this->calculate(true);
            }
        }
    }

    public function setPricingManager(PricingManagerInterface $pricingManager)
    {
        $this->pricingManager = $pricingManager;
    }

    public function getPricingManager()
    {
        if (empty($this->pricingManager)) {
            $this->pricingManager = Factory::getInstance()->getPricingManager();
        }

        return $this->pricingManager;
    }

    /**
     * gets default currency object based on the default currency locale defined in the environment
     *
     * @return Currency
     */
    protected function getDefaultCurrency()
    {
        return $this->environment->getDefaultCurrency();
    }

    /**
     * Possibility to overwrite the price object that should be used
     *
     * @param Decimal $amount
     * @param Currency $currency
     *
     * @return PriceInterface
     */
    protected function getDefaultPriceObject(Decimal $amount, Currency $currency): PriceInterface
    {
        return new Price($amount, $currency);
    }

    /**
     * @return PriceInterface $price
     */
    public function getGrandTotal(): PriceInterface
    {
        if (!$this->isCalculated) {
            $this->calculate();
        }

        return $this->grandTotal;
    }

    /**
     * @return ModificatedPriceInterface[] $priceModification
     */
    public function getPriceModifications(): array
    {
        if (!$this->isCalculated) {
            $this->calculate();
        }

        return $this->modifications;
    }

    /**
     * @return PriceInterface $price
     */
    public function getSubTotal(): PriceInterface
    {
        if (!$this->isCalculated) {
            $this->calculate();
        }

        return $this->subTotal;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->isCalculated = false;
    }

    /**
     * @param CartPriceModificatorInterface $modificator
     *
     * @return CartPriceCalculatorInterface
     */
    public function addModificator(CartPriceModificatorInterface $modificator)
    {
        $this->reset();
        $this->modificators[] = $modificator;

        return $this;
    }

    /**
     * @return CartPriceModificatorInterface[]
     */
    public function getModificators(): array
    {
        return $this->modificators;
    }

    /**
     * @param CartPriceModificatorInterface $modificator
     *
     * @return CartPriceCalculatorInterface
     */
    public function removeModificator(CartPriceModificatorInterface $modificator)
    {
        foreach ($this->modificators as $key => $mod) {
            if ($mod === $modificator) {
                unset($this->modificators[$key]);
            }
        }

        return $this;
    }

    /**
     * @return RuleInterface[]
     *
     * @throws UnsupportedException
     */
    public function getAppliedPricingRules(): array
    {
        if (!$this->isCalculated) {
            $this->calculate();
        }

        $itemRules = [];

        foreach ($this->cart->getItems() as $item) {
            $priceInfo = $item->getPriceInfo();
            if ($priceInfo instanceof PriceInfoInterface) {
                $itemRules = array_merge($itemRules, $priceInfo->getRules());
            }
        }

        $itemRules = array_merge($this->appliedPricingRules, $itemRules);
        $uniqueItemRules = [];
        foreach ($itemRules as $rule) {
            $uniqueItemRules[$rule->getId()] = $rule;
        }

        return array_values($uniqueItemRules);
    }

    /**
     * @return bool
     */
    public function isCalculated(): bool
    {
        return $this->isCalculated;
    }
}
