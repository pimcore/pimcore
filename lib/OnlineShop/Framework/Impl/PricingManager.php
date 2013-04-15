<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:04
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_PricingManager implements OnlineShop_Framework_IPricingManager
{

    /**
     * @var OnlineShop_Framework_Pricing_IEnvironment
     */
    protected $environment;

    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->config = $config;
    }


    /**
     * @param OnlineShop_Framework_IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function applyProductRules(OnlineShop_Framework_IPriceInfo $priceInfo)
    {
        // configure environment
        $env = $this->getEnvironment();
        $env->setProduct( $priceInfo->getProduct() )
            ->setCategories( (array)$priceInfo->getProduct()->getCategories() );

        // create new price info with pricing rules
        $priceInfoWithRules = $this->getPriceInfo( $priceInfo );

        // add all valid rules to the price info
        foreach($this->getValidRules($env) as $rule)
        {
            /* @var OnlineShop_Framework_Pricing_IRule $rule */
            $priceInfoWithRules->addRule($rule);

            // is this a stop rule?
            if($rule->getBehavior() == 'stopExecute')
                break;
        }

        return $priceInfoWithRules;
    }

    /**
     * @param OnlineShop_Framework_ICart $cart
     *
     * @return OnlineShop_Framework_IPricingManager
     */
    public function applyCartRules(OnlineShop_Framework_ICart $cart)
    {
        // configure environment
        $env = $this->getEnvironment();
        $env->setCart( $cart );
        $env->setProduct(null);

        // execute all valid rules
        foreach($this->getValidRules($env) as $rule)
        {
            /* @var OnlineShop_Framework_Pricing_IRule $rule */
            $env->setRule($rule);

            // test rule
            if($rule->check($env) === false)
                continue;

            // execute rule
            $rule->executeOnCart( $env );

            // is this a stop rule?
            if($rule->getBehavior() == 'stopExecute')
                break;
        }

        return $this;
    }


    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return array|OnlineShop_Framework_Pricing_IRule
     */
    public function getValidRules(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        // load all active rules from database
        $rules = new OnlineShop_Framework_Impl_Pricing_Rule_List();
        $rules->setCondition('active = 1');
        $rules->setOrderKey('prio');
        $rules->setOrder('ASC');
        $list = $rules->getRules();

        return $list;

//        // test all rules
//        $validRules = array();
//        foreach($list as $rule)
//        {
//            /* @var OnlineShop_Framework_Pricing_IRule $rule */
//
//            $environment->setRule( $rule );
//
//            // test rule
//            if($rule->check($environment))
//                $validRules[] = $rule;
//
//        }
//
//        // finish
//        return $validRules;
    }

    /**
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function getEnvironment()
    {
        if(!$this->environment)
            $this->environment = new OnlineShop_Framework_Impl_Pricing_Environment();

        return $this->environment;
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
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getCondition($type)
    {
        $class = $this->config->condition->$type->class;
        if($class == '')
            throw new OnlineShop_Framework_Exception_InvalidConfigException(sprintf('getCondition class "%s" not found.', $class));

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
     * @param OnlineShop_Framework_IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getPriceInfo(OnlineShop_Framework_IPriceInfo $priceInfo)
    {
        $class = $this->config->priceInfo->class;
        if($class == '')
            throw new OnlineShop_Framework_Exception_InvalidConfigException(sprintf('getPriceInfo class "%s" not found.', $class));

        return new $class( $priceInfo );
    }
}