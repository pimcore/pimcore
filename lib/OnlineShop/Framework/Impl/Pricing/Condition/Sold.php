<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 11.04.13
 * Time: 10:27
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Condition_Sold extends OnlineShop_Framework_Impl_Pricing_Condition_AbstractOrder implements OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @var int
     */
    protected $count;

    /**
     * @var int[]
     */
    protected $currentSoldCount = [];


    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        $rule = $environment->getRule();
        if($rule)
        {
            return $this->getSoldCount( $rule ) < $this->getCount();
        }
        else
        {
            return false;
        }
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'Sold'
            , 'count' => $this->getCount()
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

        $this->setCount( $json->count );

        return $this;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = (int)$count;
    }
}