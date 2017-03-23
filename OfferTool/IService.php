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
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem[] $excludeItems
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer
     */
    public function createNewOfferFromCart(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart, array $excludeItems = array());

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer $offer
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     * @param array $excludeItems
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer
     */
    public function updateOfferFromCart(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer $offer, \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart, array $excludeItems = array());

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer $offer
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer
     */
    public function updateTotalPriceOfOffer(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer $offer);

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOffer[]
     */
    public function getOffersForCart(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool\AbstractOfferItem
     */
    public function getNewOfferItemObject();

}