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
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config\HelperContainer;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Pimcore\Config\Config;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PricingManager implements IPricingManager
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Rule[]
     */
    protected $rules;

    /**
     * @var SessionInterface
     */
    protected $containerSession;

    /**
     * @param Config $config
     */
    public function __construct(Config $config, SessionInterface $containerSession)
    {
        $this->config = new HelperContainer($config, 'pricingmanager');
        $this->containerSession = $containerSession;
    }

    /**
     * @param PriceSystemIPriceInfo $priceInfo
     *
     * @return PriceSystemIPriceInfo
     */
    public function applyProductRules(PriceSystemIPriceInfo $priceInfo)
    {
        if ((string)$this->config->disabled == 'true') {
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
        if ((string)$this->config->disabled == 'true') {
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

        $priceCalculator = $cart->getPriceCalculator();
        // clean up discount pricing modificators in cart price calculator
        $priceModificators = $priceCalculator->getModificators();
        if ($priceModificators) {
            foreach ($priceModificators as $priceModificator) {
                if ($priceModificator instanceof Discount) {
                    $priceCalculator->removeModificator($priceModificator);
                }
            }
        }

        // execute all valid rules
        foreach ($this->getValidRules() as $rule) {
            /* @var IRule $rule */
            $env->setRule($rule);

            // test rule
            if ($rule->check($env) === false) {
                continue;
            }

            // execute rule
            $rule->executeOnCart($env);

            // is this a stop rule?
            if ($rule->getBehavior() == 'stopExecute') {
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
        $environment = new Environment();
        $environment->setSession($this->containerSession->getBag(SessionConfigurator::ATTRIBUTE_BAG_PRICING_ENVIRONMENT));

        return $environment;
    }

    /**
     * Factory
     *
     * @return IRule
     */
    public function getRule()
    {
        $class = $this->config->rule->class;

        return new $class();
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
        $class = $this->config->condition->$type->class;
        if ($class == '') {
            throw new InvalidConfigException(sprintf('getCondition class "%s" not found.', $class));
        }

        return new $class();
    }

    /**
     * Factory
     *
     * @param string $type
     *
     * @return IAction
     */
    public function getAction($type)
    {
        $class = $this->config->action->$type->class;

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
        if ((string)$this->config->disabled == 'true') {
            return $priceInfo;
            // TODO make getPriceInfo private as this call is only used internally where the enabled check is alread applied?
        }

        $class = $this->config->priceInfo->class;
        if ($class == '') {
            throw new \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException(sprintf('getPriceInfo class "%s" not found.', $class));
        }

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
