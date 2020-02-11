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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface as PriceSystemPriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class PriceInfo implements PriceInfoInterface
{
    /**
     * @var PriceInfoInterface
     */
    protected $priceInfo;

    /**
     * @var Decimal
     */
    protected $amount;

    /**
     * @var RuleInterface[]
     */
    protected $rules = [];

    /**
     * @var RuleInterface[]
     */
    protected $validRules = null;

    /**
     * @var bool
     */
    protected $rulesApplied = false;

    /**
     * @var string
     */
    protected $priceEnvironmentHash = null;

    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @inheritdoc
     */
    public function __construct(PriceSystemPriceInfoInterface $priceInfo, EnvironmentInterface $environment)
    {
        $this->amount = Decimal::create(0);
        $this->priceInfo = $priceInfo;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function addRule(RuleInterface $rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    /**
     * @inheritdoc
     */
    public function setEnvironment(EnvironmentInterface $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * Checks if environment changed based on hash
     * if so, resets valid rules
     *
     * @return bool
     */
    protected function environmentHashChanged()
    {
        $hash = $this->getEnvironment() ? $this->getEnvironment()->getHash() : '';
        if ($this->priceEnvironmentHash != $hash) {
            $this->validRules = null;
            $this->rulesApplied = false;
            $this->priceEnvironmentHash = $hash;

            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getRules(bool $forceRecalc = false): array
    {
        if ($forceRecalc || $this->validRules === null) {
            $env = $this->getEnvironment();
            $this->validRules = [];
            foreach ($this->rules as $rule) {
                $env->setRule($rule);

                if ($rule->check($env) === true) {
                    $this->validRules[] = $rule;

                    // is this a stop rule?
                    if ($rule->getBehavior() == 'stopExecute') {
                        break;
                    }
                }
            }
        }

        return $this->validRules;
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): PriceInterface
    {
        $price = clone $this->priceInfo->getPrice();
        if ($price == null) {
            return null;
        }

        if (!$this->rulesApplied || $this->environmentHashChanged()) {
            $this->setAmount($price->getAmount());
            $env = $this->getEnvironment();

            foreach ($this->getRules() as $rule) {
                /* @var RuleInterface $rule */
                $env->setRule($rule);

                // execute rule
                $rule->executeOnProduct($env);
            }
            $this->rulesApplied = true;

            if ($this->getAmount()->isNegative()) {
                $this->setAmount(Decimal::create(0));
            }
        }

        $price->setAmount($this->getAmount(), PriceInterface::PRICE_MODE_GROSS, true);

        return $price;
    }

    /**
     * @inheritdoc
     */
    public function getTotalPrice(): PriceInterface
    {
        $price = clone $this->priceInfo->getPrice();
        $price->setAmount(
            $this->getPrice()->getAmount()->mul($this->getQuantity()),
            PriceInterface::PRICE_MODE_GROSS,
            true
        );

        return $price;
    }

    /**
     * @inheritdoc
     */
    public function isMinPrice(): bool
    {
        return $this->priceInfo->isMinPrice();
    }

    /**
     * @inheritdoc
     */
    public function getQuantity()
    {
        return $this->priceInfo->getQuantity();
    }

    /**
     * @inheritdoc
     */
    public function setQuantity($quantity)
    {
        return $this->priceInfo->setQuantity($quantity);
    }

    /**
     * @inheritdoc
     */
    public function setPriceSystem(PriceSystemInterface $priceSystem)
    {
        $this->priceInfo->setPriceSystem($priceSystem);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProduct(CheckoutableInterface $product)
    {
        $this->priceInfo->setProduct($product);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProduct()
    {
        return $this->priceInfo->getProduct();
    }

    /**
     * @inheritdoc
     */
    public function setAmount(Decimal $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAmount(): Decimal
    {
        return $this->amount;
    }

    /**
     * loop through any other calls
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->priceInfo, $name], $arguments);
    }

    /**
     * @inheritdoc
     */
    public function getOriginalPrice(): PriceInterface
    {
        return $this->priceInfo->getPrice();
    }

    /**
     * @inheritdoc
     */
    public function getOriginalTotalPrice(): PriceInterface
    {
        return $this->priceInfo->getTotalPrice();
    }

    /**
     * @inheritdoc
     */
    public function hasDiscount(): bool
    {
        return $this->getPrice()->getAmount()->lessThan(
            $this->getOriginalPrice()->getAmount()
        );
    }

    /**
     * @inheritdoc
     */
    public function getDiscount(): PriceInterface
    {
        $discount = $this->getPrice()->getAmount()->sub($this->getOriginalPrice()->getAmount());

        $price = clone $this->priceInfo->getPrice();
        $price->setAmount($discount);

        return $price;
    }

    /**
     * @inheritdoc
     */
    public function getTotalDiscount(): PriceInterface
    {
        $discount = $this->getTotalPrice()->getAmount()->sub($this->getOriginalTotalPrice()->getAmount());

        $price = clone $this->priceInfo->getPrice();
        $price->setAmount($discount);

        return $price;
    }

    /**
     * @inheritdoc
     */
    public function getDiscountPercent()
    {
        $percent = $this->getPrice()->getAmount()->discountPercentageOf(
            $this->getOriginalPrice()->getAmount()
        );

        return round($percent, 2);
    }

    /**
     * @inheritdoc
     */
    public function hasRulesApplied(): bool
    {
        return (bool)$this->rulesApplied;
    }
}
