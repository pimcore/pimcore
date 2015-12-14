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

namespace OnlineShop\Framework\CartManager\CartPriceModificator;
use OnlineShop\Framework\CartManager\ICart;

/**
 * Class Shipping
 */
class Shipping implements IShipping
{
    /**
     * @var float
     */
    protected $charge = 0;

    /**
     * @param \Zend_Config $config
     */
    public function __construct(\Zend_Config $config = null)
    {
        if($config && $config->charge)
        {
            $this->charge = floatval($config->charge);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "shipping";
    }

    /**
     * @param \OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal
     * @param ICart  $cart
     *
     * @return \OnlineShop_Framework_IModificatedPrice
     */
    public function modify(\OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal, ICart $cart)
    {
        return new \OnlineShop\Framework\PriceSystem\ModificatedPrice($this->getCharge(), new \Zend_Currency(\OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrencyLocale()));
    }

    /**
     * @param float $charge
     *
     * @return \OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator
     */
    public function setCharge($charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * @return float
     */
    public function getCharge()
    {
        return $this->charge;
    }
}