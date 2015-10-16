<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 16.10.15
 * Time: 09:54
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Condition_Tenant implements OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @var string[]
     */
    protected $tenant;


    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        $currentTenant = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        return in_array($currentTenant, $this->getTenant());
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'Tenant'
            , 'tenant' => implode(',', $this->getTenant())
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setTenant( explode(',', $json->tenant) );

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * @param string[] $tenant
     *
     * @return $this
     */
    public function setTenant(array $tenant)
    {
        $this->tenant = $tenant;
        return $this;
    }
}