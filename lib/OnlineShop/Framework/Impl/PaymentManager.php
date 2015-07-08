<?php

/**
 * Class OnlineShop_Framework_Impl_PaymentManager
 */
class OnlineShop_Framework_Impl_PaymentManager implements OnlineShop_Framework_IPaymentManager
{
    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @var OnlineShop_Framework_IPayment[]
     */
    protected $instance = [];

    /**
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->config = $config;
    }


    /**
     * @param $name
     *
     * @return OnlineShop_Framework_IPayment
     */
    public function getProvider($name)
    {
        $arrProvider = $this->config->provider->class ? [$this->config->provider] : $this->config->provider;


        foreach($arrProvider as $provider)
        {
            if($provider->name == $name)
            {
                if(!array_key_exists($name, $this->instance))
                {
                    $class = $provider->class;
                    $this->instance[$name] = new $class( $provider );
                }

                return $this->instance[$name];
            }
        }
    }
}