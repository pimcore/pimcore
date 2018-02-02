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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Discount;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo as PriceSystemIPriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PricingManager implements IPricingManager
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Condition name => class mapping
     *
     * @var array
     */
    protected $conditionMapping = [];

    /**
     * Action name => class mapping
     *
     * @var array
     */
    protected $actionMapping = [];

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Rule[]
     */
    protected $rules;

    public function __construct(
        array $conditionMapping,
        array $actionMapping,
        SessionInterface $session,
        array $options = []
    ) {
        $this->conditionMapping = $conditionMapping;
        $this->actionMapping    = $actionMapping;
        $this->session          = $session;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $classProperties = ['rule_class', 'price_info_class', 'environment_class'];

        $resolver->setRequired($classProperties);

        $resolver->setDefaults([
            'rule_class'        => Rule::class,
            'price_info_class'  => PriceInfo::class,
            'environment_class' => Environment::class,
        ]);

        foreach ($classProperties as $classProperty) {
            $resolver->setAllowedTypes($classProperty, 'string');
        }
    }

    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param PriceSystemIPriceInfo $priceInfo
     *
     * @return PriceSystemIPriceInfo
     */
    public function applyProductRules(PriceSystemIPriceInfo $priceInfo)
    {
        if (!$this->enabled) {
            return $priceInfo;
        }

        // create new price info with pricing rules
        $priceInfoWithRules = $this->getPriceInfo($priceInfo);

        // add all valid rules to the price info
        foreach ($this->getValidRules() as $rule) {
            /* @var IRule $rule */
            $priceInfoWithRules->addRule($rule);
        }

        return $priceInfoWithRules;
    }

    /**
     * @param ICart $cart
     *
     * @return IPricingManager
     */
    public function applyCartRules(ICart $cart)
    {
        if (!$this->enabled) {
            return $this;
        }

        // configure environment
        $env = $this->getEnvironment();
        $env->setCart($cart);
        $env->setExecutionMode(IEnvironment::EXECUTION_MODE_CART);
        $env->setProduct(null);

        $categories = [];
        foreach ($cart->getItems() as $item) {
            if ($product = $item->getProduct()) {
                if (method_exists($product, 'getCategories')) {
                    $productCategories = $product->getCategories();
                    if (is_array($productCategories)) {
                        foreach ($productCategories as $c) {
                            $categories[$c->getId()] = $c;
                        }
                    }
                }
            }
        }

        $env->setCategories(array_values($categories));

        // clean up discount pricing modificators in cart price calculator
        $priceCalculator   = $cart->getPriceCalculator();
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

            // is this a stop rule?
            if ($rule->getBehavior() === 'stopExecute') {
                break;
            }
        }

        return $this;
    }

    /**
     * @return IRule[]
     */
    public function getValidRules()
    {
        if (empty($this->rules)) {
            $rules = new Rule\Listing();
            $rules->setCondition('active = 1');
            $rules->setOrderKey('prio');
            $rules->setOrder('ASC');

            $this->rules = $rules->getRules();
        }

        return $this->rules;
    }

    /**
     * @return IEnvironment
     */
    public function getEnvironment()
    {
        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $this->session->getBag(SessionConfigurator::ATTRIBUTE_BAG_PRICING_ENVIRONMENT);

        $class = $this->options['environment_class'];

        /** @var IEnvironment $environment */
        $environment = new $class();
        $environment->setSession($sessionBag);

        return $environment;
    }

    /**
     * Factory
     *
     * @return IRule
     */
    public function getRule()
    {
        $class = $this->options['rule_class'];

        return new $class();
    }

    /**
     * @return array
     */
    public function getConditionMapping(): array
    {
        return $this->conditionMapping;
    }

    /**
     * @return array
     */
    public function getActionMapping(): array
    {
        return $this->actionMapping;
    }

    /**
     * Factory
     *
     * @param string $type
     *
     * @return ICondition
     *
     * @throws InvalidConfigException
     */
    public function getCondition($type)
    {
        if (!isset($this->conditionMapping[$type])) {
            throw new InvalidConfigException(sprintf('ICondition for type "%s" is not registered', $type));
        }

        $class = $this->conditionMapping[$type];

        return new $class();
    }

    /**
     * Factory
     *
     * @param string $type
     *
     * @return IAction
     *
     * @throws InvalidConfigException
     */
    public function getAction($type)
    {
        if (!isset($this->actionMapping[$type])) {
            throw new InvalidConfigException(sprintf('IAction for type "%s" is not registered', $type));
        }

        $class = $this->actionMapping[$type];

        return new $class();
    }

    /**
     * @param PriceSystemIPriceInfo $priceInfo
     *
     * @return IPriceInfo
     *
     * @throws InvalidConfigException
     */
    public function getPriceInfo(PriceSystemIPriceInfo $priceInfo)
    {
        // TODO make getPriceInfo private as this call is only used internally where the enabled check is alread applied?
        if (!$this->enabled) {
            throw new \RuntimeException('Can\'t build a pricing manager price info as the pricing manager is disabled');
        }

        $class = $this->options['price_info_class'];

        // create environment
        $environment = $this->getEnvironment();
        $environment->setProduct($priceInfo->getProduct());

        if (method_exists($priceInfo->getProduct(), 'getCategories')) {
            $environment->setCategories((array)$priceInfo->getProduct()->getCategories());
        }

        $priceInfoWithRules = new $class($priceInfo, $environment);
        $environment->setPriceInfo($priceInfoWithRules);

        return $priceInfoWithRules;
    }
}
