<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:41
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Condition_DateRange implements OnlineShop_Framework_Pricing_Condition_IDateRange
{
    /**
     * @var Zend_Date
     */
    protected $starting;

    /**
     * @var Zend_Date
     */
    protected $ending;

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        if($this->getStarting() && $this->getEnding())
        {
            return $this->getStarting()->isEarlier(time()) && $this->getEnding()->isLater(time());
        }

        return false;
    }

    /**
     * @param Zend_Date $date
     *
     * @return OnlineShop_Framework_Pricing_Condition_IDateRange
     */
    public function setStarting(Zend_Date $date)
    {
        $this->starting = $date;
        return $this;
    }

    /**
     * @param Zend_Date $date
     *
     * @return OnlineShop_Framework_Pricing_Condition_IDateRange
     */
    public function setEnding(Zend_Date $date)
    {
        $this->ending = $date;
        return $this;
    }

    /**
     * @return Zend_Date
     */
    public function getStarting()
    {
        return $this->starting;
    }

    /**
     * @return Zend_Date
     */
    public function getEnding()
    {
        return $this->ending;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
            'type' => 'DateRange',
            'starting' => $this->getStarting()->toValue(),
            'ending' => $this->getEnding()->toValue()
        ));
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $this->setStarting(new Zend_Date(strtotime($json->starting)));
        $ending = new Zend_Date(strtotime($json->ending));
        $ending->setHour(59)->setMinute(59)->setSecond(59);
        $this->setEnding( $ending );

        return $this;
    }
}