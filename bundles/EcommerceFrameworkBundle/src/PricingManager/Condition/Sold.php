<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;

class Sold extends AbstractOrder implements ConditionInterface
{
    protected int $count;

    /**
     * @var int[]
     */
    protected array $currentSoldCount = [];

    protected bool $countCart = false;

    public function check(EnvironmentInterface $environment): bool
    {
        $rule = $environment->getRule();
        if ($rule) {
            $cartUsedCount = 0;

            if ($this->isCountCart()) {
                if ($environment->getCart() && $environment->getCartItem()) {
                    // cart view
                    $cartUsedCount = $this->getCartRuleCount($environment->getCart(), $rule, $environment->getCartItem());
                } elseif (!$environment->getCart()) {
                    // product view
                    $cart = $this->getCart();
                    $cartUsedCount = $this->getCartRuleCount($cart, $rule);
                }
            }

            return ($this->getSoldCount($rule) + $cartUsedCount) < $this->getCount();
        } else {
            return false;
        }
    }

    public function toJSON(): string
    {
        // basic
        $json = [
            'type' => 'Sold', 'count' => $this->getCount(), 'countCart' => $this->isCountCart(),
        ];

        return json_encode($json);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);

        $this->setCount($json->count);
        $this->setCountCart((bool)$json->countCart);

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = (int)$count;
    }

    public function isCountCart(): bool
    {
        return $this->countCart;
    }

    public function setCountCart(bool $countCart): static
    {
        $this->countCart = (bool)$countCart;

        return $this;
    }

    protected function getCart(): ?CartInterface
    {
        // use this in your own implementation
        return null;
    }

    /**
     * Returns a count how often the rule is already used in the cart
     *
     * @param CartInterface $cart
     * @param RuleInterface $rule
     * @param CartItemInterface|null $cartItem
     *
     * @return int
     */
    protected function getCartRuleCount(CartInterface $cart, RuleInterface $rule, CartItemInterface $cartItem = null): int
    {
        // init
        $counter = 0;

        foreach ($cart->getItems() as $item) {
            $rules = [];

            if ($cartItem && $item->getItemKey() == $cartItem) {
                // skip self if we are on a cartItem
            } else {
                // get rules
                $priceInfo = $item->getPriceInfo();
                if ($priceInfo instanceof PriceInfoInterface) {
                    if (($cartItem && $priceInfo->hasRulesApplied()) || $cartItem === null) {
                        $rules = $priceInfo->getRules();
                    }
                }
            }

            // search for current rule
            foreach ($rules as $r) {
                if ($r->getId() == $rule->getId()) {
                    $counter++;

                    break;
                }
            }
        }

        return $counter;
    }
}
