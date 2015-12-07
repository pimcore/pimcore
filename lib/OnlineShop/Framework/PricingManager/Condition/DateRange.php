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

namespace OnlineShop\Framework\PricingManager\Condition;

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
        $this->setStarting(new \Zend_Date(strtotime($json->starting)));
        $ending = new \Zend_Date(strtotime($json->ending));
        $ending->setHour(59)->setMinute(59)->setSecond(59);
        $this->setEnding( $ending );

        return $this;
    }
}