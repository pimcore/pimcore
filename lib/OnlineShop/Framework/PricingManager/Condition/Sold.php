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

class Sold extends AbstractOrder implements \OnlineShop\Framework\PricingManager\ICondition
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
     * @var bool
     */
    protected $countCart = false;


    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        $rule = $environment->getRule();
        if($rule)
        {
            $cartUsedCount = 0;

            if($this->isCountCart())
            {
                if($environment->getCart() && $environment->getCartItem())
                {
                    // cart view
                    $cartUsedCount = $this->getCartRuleCount($environment->getCart(), $rule, $environment->getCartItem());
                }
                else if(!$environment->getCart())
                {
                    // product view
                    $cart = $this->getCart();
                    $cartUsedCount = $this->getCartRuleCount($cart, $rule);
                }
            }

            return ($this->getSoldCount( $rule ) + $cartUsedCount) < $this->getCount();
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
            , 'countCart' => $this->isCountCart()
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setCount( $json->count );
        $this->setCountCart( (bool)$json->countCart );

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

    /**
     * @return boolean
     */
    public function isCountCart()
    {
        return $this->countCart;
    }

    /**
     * @param boolean $countCart
     *
     * @return $this
     */
    public function setCountCart($countCart)
    {
        $this->countCart = (bool)$countCart;
        return $this;
    }


    /**
     * @return \OnlineShop\Framework\CartManager\ICart|null
     */
    protected function getCart()
    {
        // use this in your own implementation
    }


    /**
     * return a count how often the rule is already uses in the cart
     * @param \OnlineShop\Framework\CartManager\ICart          $cart
     * @param \OnlineShop\Framework\PricingManager\IRule  $rule
     * @param \OnlineShop\Framework\CartManager\ICartItem|null $cartItem
     *
     * @return int
     */
    protected function getCartRuleCount(\OnlineShop\Framework\CartManager\ICart $cart, \OnlineShop\Framework\PricingManager\IRule $rule, \OnlineShop\Framework\CartManager\ICartItem $cartItem = null)
    {
        // init
        $counter = 0;

        foreach($cart->getItems() as $item)
        {
            $rules = [];

            if($cartItem && $item->getItemKey() == $cartItem)
            {
                // skip self if we are on a cartItem
            }
            else
            {
                // get rules
                $priceInfo = $item->getPriceInfo();
                if($priceInfo instanceof \OnlineShop\Framework\PricingManager\IPriceInfo)
                {
                    if(($cartItem && $priceInfo->hasRulesApplied()) || $cartItem === NULL)
                    {
                        $rules = $priceInfo->getRules();
                    }
                }
            }


            // search for current rule
            foreach($rules as $r)
            {
                if($r->getId() == $rule->getId())
                {
                    $counter++;
                    break;
                }
            }
        }

        return $counter;
    }
}