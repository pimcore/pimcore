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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool;



interface IService {

    const DISCOUNT_TYPE_PERCENT = "percent";
    const DISCOUNT_TYPE_AMOUNT = "amount";

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param \OnlineShop\Framework\CartManager\ICartItem[] $excludeItems
     * @return \OnlineShop\Framework\OfferTool\AbstractOffer
     */
    public function createNewOfferFromCart(\OnlineShop\Framework\CartManager\ICart $cart, array $excludeItems = array());

    /**
     * @param \OnlineShop\Framework\OfferTool\AbstractOffer $offer
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param array $excludeItems
     * @return \OnlineShop\Framework\OfferTool\AbstractOffer
     */
    public function updateOfferFromCart(\OnlineShop\Framework\OfferTool\AbstractOffer $offer, \OnlineShop\Framework\CartManager\ICart $cart, array $excludeItems = array());

    /**
     * @param \OnlineShop\Framework\OfferTool\AbstractOffer $offer
     * @return \OnlineShop\Framework\OfferTool\AbstractOffer
     */
    public function updateTotalPriceOfOffer(\OnlineShop\Framework\OfferTool\AbstractOffer $offer);

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return \OnlineShop\Framework\OfferTool\AbstractOffer[]
     */
    public function getOffersForCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * @return \OnlineShop\Framework\OfferTool\AbstractOfferItem
     */
    public function getNewOfferItemObject();

}