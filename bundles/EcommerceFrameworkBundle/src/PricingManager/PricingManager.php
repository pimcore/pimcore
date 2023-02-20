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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Discount;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface as PriceSystemPriceInfoInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\VisitorInfoStorageInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PricingManager implements PricingManagerInterface
{
    protected bool $enabled = true;

    /**
     * Condition name => class mapping
     *
     * @var array
     */
    protected array $conditionMapping = [];

    /**
     * Action name => class mapping
     *
     * @var array
     */
    protected array $actionMapping = [];

    protected array $options;

    protected ?VisitorInfoStorageInterface $visitorInfoStorage = null;

    /**
     * @var RuleInterface[]|null
     */
    protected ?array $rules = null;

    public function __construct(
        array $conditionMapping,
        array $actionMapping,
        array $options = [],
        VisitorInfoStorageInterface $visitorInfoStorage = null
    ) {
        $this->conditionMapping = $conditionMapping;
        $this->actionMapping = $actionMapping;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->visitorInfoStorage = $visitorInfoStorage;

        $this->options = $resolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $classProperties = ['rule_class', 'price_info_class', 'environment_class'];

        $resolver->setRequired($classProperties);

        $resolver->setDefaults([
            'rule_class' => Rule::class,
            'price_info_class' => PriceInfo::class,
            'environment_class' => Environment::class,
        ]);

        foreach ($classProperties as $classProperty) {
            $resolver->setAllowedTypes($classProperty, 'string');
        }
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function applyProductRules(PriceSystemPriceInfoInterface $priceInfo): PriceInfoInterface|PriceSystemPriceInfoInterface
    {
        if (!$this->enabled) {
            return $priceInfo;
        }

        // create new price info with pricing rules
        $priceInfoWithRules = $this->getPriceInfo($priceInfo);

        // add all valid rules to the price info
        foreach ($this->getValidRules() as $rule) {
            $priceInfoWithRules->addRule($rule);
        }

        return $priceInfoWithRules;
    }

    /**
     * @param CartInterface $cart
     *
     * @return RuleInterface[]
     */
    public function applyCartRules(CartInterface $cart): array
    {
        $appliedRules = [];

        if (!$this->enabled) {
            return $appliedRules;
        }

        // configure environment
        $env = $this->getEnvironment();
        $env->setCart($cart);
        $env->setExecutionMode(EnvironmentInterface::EXECUTION_MODE_CART);
        $env->setProduct(null);
        if ($this->visitorInfoStorage && $this->visitorInfoStorage->hasVisitorInfo()) {
            $env->setVisitorInfo($this->visitorInfoStorage->getVisitorInfo());
        }

        $categories = [];
        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();
            if ($product instanceof CheckoutableInterface && method_exists($product, 'getCategories')) {
                $productCategories = $product->getCategories();
                if (is_array($productCategories)) {
                    foreach ($productCategories as $c) {
                        $categories[$c->getId()] = $c;
                    }
                }
            }
        }

        $env->setCategories(array_values($categories));

        // clean up discount pricing modificators in cart price calculator
        $priceCalculator = $cart->getPriceCalculator();
        $priceModificators = $priceCalculator->getModificators();

        foreach ($priceModificators as $priceModificator) {
            if ($priceModificator instanceof Discount) {
                $priceCalculator->removeModificator($priceModificator);
            }
        }

        // execute all valid rules
        foreach ($this->getValidRules() as $rule) {
            $env->setRule($rule);

            // test rule
            if ($rule->check($env) === false) {
                continue;
            }

            // execute rule
            $rule->executeOnCart($env);
            $appliedRules[] = $rule;

            // is this a stop rule?
            if ($rule->getBehavior() === Rule::ATTRIBUTE_BEHAVIOR_LASTRULE) {
                break;
            }
        }

        return $appliedRules;
    }

    /**
     * @return RuleInterface[]
     */
    public function getValidRules(): array
    {
        if (is_null($this->rules)) {
            $rules = $this->getRuleListing();
            $rules->setCondition('active = 1');
            $rules->setOrderKey('prio');
            $rules->setOrder('ASC');

            $rules->getDao()->setRuleClass($this->options['rule_class']);

            $this->rules = $rules->getRules();
        }

        return $this->rules;
    }

    public function getEnvironment(): EnvironmentInterface
    {
        $class = $this->options['environment_class'];

        /** @var EnvironmentInterface $environment */
        $environment = new $class();

        return $environment;
    }

    public function getRuleListing(): Rule\Listing
    {
        $class = $this->options['rule_class'] . '\\Listing';

        return new $class;
    }

    public function getConditionMapping(): array
    {
        return $this->conditionMapping;
    }

    public function getActionMapping(): array
    {
        return $this->actionMapping;
    }

    /**
     * Factory
     *
     * @param string $type
     *
     * @return ConditionInterface
     *
     * @throws InvalidConfigException
     */
    public function getCondition(string $type): ConditionInterface
    {
        if (!isset($this->conditionMapping[$type])) {
            throw new InvalidConfigException(sprintf('ConditionInterface for type "%s" is not registered', $type));
        }

        $class = $this->conditionMapping[$type];

        return new $class();
    }

    /**
     * Factory
     *
     * @param string $type
     *
     * @return ActionInterface
     *
     * @throws InvalidConfigException
     */
    public function getAction(string $type): ActionInterface
    {
        if (!isset($this->actionMapping[$type])) {
            throw new InvalidConfigException(sprintf('ActionInterface for type "%s" is not registered', $type));
        }

        $class = $this->actionMapping[$type];

        return new $class();
    }

    /**
     * @param PriceSystemPriceInfoInterface $priceInfo
     *
     * @return PriceInfoInterface
     *
     * @throws InvalidConfigException
     */
    public function getPriceInfo(PriceSystemPriceInfoInterface $priceInfo): PriceInfoInterface
    {
        // TODO make getPriceInfo private as this call is only used internally where the enabled check is alread applied?
        if (!$this->enabled) {
            throw new \RuntimeException('Can\'t build a pricing manager price info as the pricing manager is disabled');
        }

        $class = $this->options['price_info_class'];

        // create environment
        $environment = $this->getEnvironment();
        $environment->setProduct($priceInfo->getProduct());

        if ($priceInfo->getProduct() && method_exists($priceInfo->getProduct(), 'getCategories')) {
            $environment->setCategories((array)$priceInfo->getProduct()->getCategories());
        }

        if ($this->visitorInfoStorage && $this->visitorInfoStorage->hasVisitorInfo()) {
            $environment->setVisitorInfo($this->visitorInfoStorage->getVisitorInfo());
        }

        $priceInfoWithRules = new $class($priceInfo, $environment);
        $environment->setPriceInfo($priceInfoWithRules);

        return $priceInfoWithRules;
    }
}
