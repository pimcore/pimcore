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

interface IService
{
    const DISCOUNT_TYPE_PERCENT = 'percent';
    const DISCOUNT_TYPE_AMOUNT = 'amount';

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem[] $excludeItems
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer
     */
    public function createNewOfferFromCart(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart, array $excludeItems = []);

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer $offer
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     * @param array $excludeItems
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer
     */
    public function updateOfferFromCart(\Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer $offer, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart, array $excludeItems = []);

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer $offer
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer
     */
    public function updateTotalPriceOfOffer(\Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer $offer);

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer[]
     */
    public function getOffersForCart(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOfferItem
     */
    public function getNewOfferItemObject();
}
