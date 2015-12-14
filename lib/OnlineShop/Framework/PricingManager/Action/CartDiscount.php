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

namespace OnlineShop\Framework\PricingManager\Action;

use OnlineShop\Framework\CartManager\CartPriceModificator\Discount;

class CartDiscount implements IDiscount
{
    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var float
     */
    protected $percent = 0;

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PricingManager\IAction
     */
    public function executeOnProduct(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        return $this;
    }

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PricingManager\IAction
     */
    public function executeOnCart(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        $priceCalculator = $environment->getCart()->getPriceCalculator();
        $modDiscount = new Discount($environment->getRule());


        $amount = round($this->getAmount() !== 0 ? $this->getAmount() : ($priceCalculator->getSubTotal()->getAmount() * ($this->getPercent() / 100)), 2);
        $modDiscount->setAmount( '-'.$amount );
        $priceCalculator->addModificator( $modDiscount );

        return $this;
    }


    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
                                'type' => 'CartDiscount',
                                'amount' => $this->getAmount(),
                                'percent' => $this->getPercent()
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
        if($json->amount)
            $this->setAmount( $json->amount );
        if($json->percent)
            $this->setPercent( $json->percent );
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $percent
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;
    }

    /**
     * @return float
     */
    public function getPercent()
    {
        return $this->percent;
    }
}
