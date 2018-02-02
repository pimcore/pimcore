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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;

interface IService
{
    const DISCOUNT_TYPE_PERCENT = 'percent';
    const DISCOUNT_TYPE_AMOUNT = 'amount';

    /**
     * @param ICart $cart
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem[] $excludeItems
     *
     * @return AbstractOffer
     */
    public function createNewOfferFromCart(ICart $cart, array $excludeItems = []);

    /**
     * @param AbstractOffer $offer
     * @param ICart $cart
     * @param array $excludeItems
     *
     * @return AbstractOffer
     */
    public function updateOfferFromCart(AbstractOffer $offer, ICart $cart, array $excludeItems = []);

    /**
     * @param AbstractOffer $offer
     *
     * @return AbstractOffer
     */
    public function updateTotalPriceOfOffer(AbstractOffer $offer);

    /**
     * @param ICart $cart
     *
     * @return AbstractOffer[]
     */
    public function getOffersForCart(ICart $cart);

    /**
     * @return AbstractOfferItem
     */
    public function getNewOfferItemObject();
}
