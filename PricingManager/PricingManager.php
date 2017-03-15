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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager;

use OnlineShop\Framework\CartManager\CartPriceModificator\Discount;

/**
 * Class PricingManager
 */
class PricingManager implements IPricingManager
{

    /**
     * @var \Zend_Config
     */
    protected $config;

    /**
     * @var Rule\Listing
     */
    protected $rules;

    /**
     * @param \Zend_Config $config
     */
    public function __construct(\Zend_Config $config)
    {
        $this->config = new \OnlineShop\Framework\Tools\Config\HelperContainer($config, "pricingmanager");
    }


    /**
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo
     *
     * @return IPriceInfo
     */
    public function applyProductRules(\OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo)
    {
        if((string)$this->config->disabled == "true") {
            return $priceInfo;
        }

        // create new price info with pricing rules
        $priceInfoWithRules = $this->getPriceInfo( $priceInfo );


        // add all valid rules to the price info
        foreach($this->getValidRules() as $rule)
        {
            /* @var IRule $rule */

            if($rule->hasProductActions()) {
                $priceInfoWithRules->addRule($rule);
            }

        }

        return $priceInfoWithRules;
    }

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     *
     * @return IPricingManager
     */
    public function applyCartRules(\OnlineShop\Framework\CartManager\ICart $cart)
    {
        if((string)$this->config->disabled == "true") {
            return $this;
        }

        // configure environment
        $env = $this->getEnvironment();
        $env->setCart( $cart );
        $env->setProduct( null );

        $categories = array();
        foreach($cart->getItems() as $item) {
            if($product = $item->getProduct()) {
                if(method_exists($product, "getCategories")) {
                    $productCategories = $product->getCategories();
                    if (is_array($productCategories)) {
                        foreach($productCategories as $c) {
                            $categories[$c->getId()] = $c;
                        }
                    }
                }
            }
        }
        $env->setCategories(array_values($categories));


        //clean up discount pricing modificators in cart price calculator
        $priceCalculator = $cart->getPriceCalculator();
        $priceModificators = $priceCalculator->getModificators();
        if($priceModificators) {
            foreach($priceModificators as $priceModificator) {
                if($priceModificator instanceof Discount) {
                    $priceCalculator->removeModificator($priceModificator);
                }
            }
        }


        // execute all valid rules
        foreach($this->getValidRules() as $rule)
        {
            /* @var IRule $rule */
            $env->setRule($rule);

            // test rule
            if($rule->check($env) === false) {
                continue;
            }

            // execute rule
            $rule->executeOnCart( $env );

            // is this a stop rule?
            if($rule->getBehavior() == 'stopExecute') {
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
        if(empty($this->rules))
        {
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
        $environment->setSession( new \Zend_Session_Namespace('PricingManager') );

        return $environment;
    }

    /**
     * Factory
     * @return IRule
     */
    public function getRule()
    {
        $class = $this->config->rule->class;
        return new $class();
    }

    /**
     * @param string $type
     *
     * @return ICondition
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getCondition($type)
    {
        $class = $this->config->condition->$type->class;
        if($class == '')
            throw new \OnlineShop\Framework\Exception\InvalidConfigException(sprintf('getCondition class "%s" not found.', $class));

        return new $class();
    }

    /**
     * Factory
     * @param $type
     *
     * @return IAction
     */
    public function getAction($type)
    {
        $class = $this->config->action->$type->class;
        return new $class();
    }

    /**
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo
     *
     * @return IPriceInfo
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getPriceInfo(\OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo)
    {
        if((string)$this->config->disabled == "true") {
            return $priceInfo;
        }

        $class = $this->config->priceInfo->class;
        if($class == '')
        {
            throw new \OnlineShop\Framework\Exception\InvalidConfigException(sprintf('getPriceInfo class "%s" not found.', $class));
        }


        // create environment
        $environment = $this->getEnvironment();
        $environment->setProduct( $priceInfo->getProduct() );
        if(method_exists($priceInfo->getProduct(), "getCategories")) {
            $environment->setCategories( (array)$priceInfo->getProduct()->getCategories() );
        }


        $priceInfoWithRules = new $class( $priceInfo, $environment );
        $environment->setPriceInfo( $priceInfoWithRules );

        return $priceInfoWithRules;
    }
}
