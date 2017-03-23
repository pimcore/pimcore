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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager;

use Pimcore\Config\Config;

/**
 * Class PaymentManager
 */
class PaymentManager implements IPaymentManager
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment[]
     */
    protected $instance = [];

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }


    /**
     * @param $name
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment
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