<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Class OnlineShop_Framework_Impl_PricingManager
 */
class OnlineShop_Framework_Impl_PricingManager implements OnlineShop_Framework_IPricingManager
{

    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @var OnlineShop_Framework_Impl_Pricing_Rule_List
     */
    private $rules;

    /**
     * @param Zend_Config $config
     */
    public function __construct(\Zend_Config $config)
    {
        $this->config = $config;
    }


    /**
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
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
            /* @var OnlineShop_Framework_Pricing_IRule $rule */
            $priceInfoWithRules->addRule($rule);
        }

        return $priceInfoWithRules;
    }

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     *
     * @return OnlineShop_Framework_IPricingManager
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
                    foreach($productCategories as $c) {
                        $categories[$c->getId()] = $c;
                    }
                }
            }
        }
        $env->setCategories(array_values($categories));


        // execute all valid rules
        foreach($this->getValidRules() as $rule)
        {
            /* @var OnlineShop_Framework_Pricing_IRule $rule */
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
     * @return OnlineShop_Framework_Pricing_IRule[]
     */
    public function getValidRules()
    {
        if(empty($this->rules))
        {
            $rules = new OnlineShop_Framework_Impl_Pricing_Rule_List();
            $rules->setCondition('active = 1');
            $rules->setOrderKey('prio');
            $rules->setOrder('ASC');
            $this->rules = $rules->getRules();
        }

        return $this->rules;
    }

    /**
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function getEnvironment()
    {
        $environment = new OnlineShop_Framework_Impl_Pricing_Environment();
        $environment->setSession( new \Zend_Session_Namespace('PricingManager') );

        return $environment;
    }

    /**
     * Factory
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function getRule()
    {
        $class = $this->config->rule->class;
        return new $class();
    }

    /**
     * @param string $type
     *
     * @return OnlineShop_Framework_Pricing_ICondition
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
     * @return OnlineShop_Framework_Pricing_IAction
     */
    public function getAction($type)
    {
        $class = $this->config->action->$type->class;
        return new $class();
    }

    /**
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
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