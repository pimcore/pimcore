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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Condition;

class DateRange implements IDateRange
{
    /**
     * @var \Zend_Date
     */
    protected $starting;

    /**
     * @var \Zend_Date
     */
    protected $ending;

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        if($this->getStarting() && $this->getEnding())
        {
            return $this->getStarting()->isEarlier(time()) && $this->getEnding()->isLater(time());
        }

        return false;
    }

    /**
     * @param \Zend_Date $date
     *
     * @return IDateRange
     */
    public function setStarting(\Zend_Date $date)
    {
        $this->starting = $date;
        return $this;
    }

    /**
     * @param \Zend_Date $date
     *
     * @return IDateRange
     */
    public function setEnding(\Zend_Date $date)
    {
        $this->ending = $date;
        return $this;
    }

    /**
     * @return \Zend_Date
     */
    public function getStarting()
    {
        return $this->starting;
    }

    /**
     * @return \Zend_Date
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
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $starting = new \Zend_Date(strtotime($json->starting));
        $starting->setTime('00:00:00');

        $ending = new \Zend_Date(strtotime($json->ending));
        $ending->setTime('23:59:59');

        $this->setStarting($starting);
        $this->setEnding($ending);

        return $this;
    }
}
