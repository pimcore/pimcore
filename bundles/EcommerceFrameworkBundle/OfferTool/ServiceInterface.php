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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;

interface ServiceInterface
{
    const DISCOUNT_TYPE_PERCENT = 'percent';
    const DISCOUNT_TYPE_AMOUNT = 'amount';

    /**
     * @param CartInterface $cart
     * @param CartItemInterface[] $excludeItems
     *
     * @return AbstractOffer
     */
    public function createNewOfferFromCart(CartInterface $cart, array $excludeItems = []);

    /**
     * @param AbstractOffer $offer
     * @param CartInterface $cart
     * @param array $excludeItems
     *
     * @return AbstractOffer
     */
    public function updateOfferFromCart(AbstractOffer $offer, CartInterface $cart, array $excludeItems = []);

    /**
     * @param AbstractOffer $offer
     *
     * @return AbstractOffer
     */
    public function updateTotalPriceOfOffer(AbstractOffer $offer);

    /**
     * @param CartInterface $cart
     *
     * @return AbstractOffer[]
     */
    public function getOffersForCart(CartInterface $cart);

    /**
     * @return AbstractOfferItem
     */
    public function getNewOfferItemObject();
}

class_alias(ServiceInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\IService');
