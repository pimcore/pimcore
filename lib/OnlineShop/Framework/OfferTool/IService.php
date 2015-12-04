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

namespace OnlineShop\Framework\OfferTool;



interface IService {

    const DISCOUNT_TYPE_PERCENT = "percent";
    const DISCOUNT_TYPE_AMOUNT = "amount";

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param \OnlineShop\Framework\CartManager\ICartItem[] $excludeItems
     * @return \OnlineShop_OfferTool_AbstractOffer
     */
    public function createNewOfferFromCart(\OnlineShop\Framework\CartManager\ICart $cart, array $excludeItems = array());

    /**
     * @param \OnlineShop_OfferTool_AbstractOffer $offer
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param array $excludeItems
     * @return \OnlineShop_OfferTool_AbstractOffer
     */
    public function updateOfferFromCart(\OnlineShop_OfferTool_AbstractOffer $offer, \OnlineShop\Framework\CartManager\ICart $cart, array $excludeItems = array());

    /**
     * @param \OnlineShop_OfferTool_AbstractOffer $offer
     * @return \OnlineShop_OfferTool_AbstractOffer
     */
    public function updateTotalPriceOfOffer(\OnlineShop_OfferTool_AbstractOffer $offer);

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return \OnlineShop_OfferTool_AbstractOffer[]
     */
    public function getOffersForCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * @return \OnlineShop_OfferTool_AbstractOfferItem
     */
    public function getNewOfferItemObject();

}