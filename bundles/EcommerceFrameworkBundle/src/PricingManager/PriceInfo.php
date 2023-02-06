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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface as PriceSystemPriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class PriceInfo implements PriceInfoInterface
{
    protected PriceSystemPriceInfoInterface $priceInfo;

    protected Decimal $amount;

    /**
     * @var RuleInterface[]
     */
    protected array $rules = [];

    /**
     * @var RuleInterface[]|null
     */
    protected ?array $validRules = null;

    protected bool $rulesApplied = false;

    protected string $priceEnvironmentHash = '';

    protected EnvironmentInterface $environment;

    public function __construct(PriceSystemPriceInfoInterface $priceInfo, EnvironmentInterface $environment)
    {
        $this->amount = Decimal::create(0);
        $this->priceInfo = $priceInfo;
        $this->environment = $environment;
    }

    public function addRule(RuleInterface $rule): static
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    public function setEnvironment(EnvironmentInterface $environment): static
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
    protected function environmentHashChanged(): bool
    {
        $hash = $this->getEnvironment()->getHash();
        if ($this->priceEnvironmentHash != $hash) {
            $this->validRules = null;
            $this->rulesApplied = false;
            $this->priceEnvironmentHash = $hash;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
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
                    if ($rule->getBehavior() == Rule::ATTRIBUTE_BEHAVIOR_LASTRULE) {
                        break;
                    }
                }
            }
        }

        return $this->validRules;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(): PriceInterface
    {
        $price = clone $this->priceInfo->getPrice();

        if (!$this->rulesApplied || $this->environmentHashChanged()) {
            $this->setAmount($price->getAmount());
            $env = $this->getEnvironment();

            foreach ($this->getRules() as $rule) {
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isMinPrice(): bool
    {
        return $this->priceInfo->isMinPrice();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity(): int|string
    {
        return $this->priceInfo->getQuantity();
    }

    /**
     * {@inheritdoc}
     */
    public function setQuantity(int|string $quantity): void
    {
        $this->priceInfo->setQuantity($quantity);
    }

    /**
     * {@inheritdoc}
     */
    public function setPriceSystem(PriceSystemInterface $priceSystem): static
    {
        $this->priceInfo->setPriceSystem($priceSystem);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setProduct(CheckoutableInterface $product): static
    {
        $this->priceInfo->setProduct($product);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct(): ?CheckoutableInterface
    {
        return $this->priceInfo->getProduct();
    }

    public function setAmount(Decimal $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): Decimal
    {
        return $this->amount;
    }

    /**
     * loop through any other calls
     */
    public function __call(string $name, array $arguments): mixed
    {
        return call_user_func_array([$this->priceInfo, $name], $arguments);
    }

    public function getOriginalPrice(): PriceInterface
    {
        return $this->priceInfo->getPrice();
    }

    public function getOriginalTotalPrice(): PriceInterface
    {
        return $this->priceInfo->getTotalPrice();
    }

    public function hasDiscount(): bool
    {
        return $this->getPrice()->getAmount()->lessThan(
            $this->getOriginalPrice()->getAmount()
        );
    }

    public function getDiscount(): PriceInterface
    {
        $discount = $this->getPrice()->getAmount()->sub($this->getOriginalPrice()->getAmount());

        $price = clone $this->priceInfo->getPrice();
        $price->setAmount($discount);

        return $price;
    }

    public function getTotalDiscount(): PriceInterface
    {
        $discount = $this->getTotalPrice()->getAmount()->sub($this->getOriginalTotalPrice()->getAmount());

        $price = clone $this->priceInfo->getPrice();
        $price->setAmount($discount);

        return $price;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountPercent(): float
    {
        $percent = $this->getPrice()->getAmount()->discountPercentageOf(
            $this->getOriginalPrice()->getAmount()
        );

        return round($percent, 2);
    }

    public function hasRulesApplied(): bool
    {
        return (bool)$this->rulesApplied;
    }
}
