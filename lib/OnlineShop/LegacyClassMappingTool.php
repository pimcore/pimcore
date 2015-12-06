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

namespace OnlineShop;

class LegacyClassMappingTool {

    private static $mappingClasses = [
        'OnlineShop\Plugin' => 'OnlineShop_Plugin',
        'OnlineShop\Framework\OfferTool\DefaultService' => 'OnlineShop_OfferTool_Impl_DefaultService',
        'OnlineShop\Framework\OfferTool\AbstractOffer' => 'OnlineShop_OfferTool_AbstractOffer',
        'OnlineShop\Framework\OfferTool\AbstractOfferItem' => 'OnlineShop_OfferTool_AbstractOfferItem',
        'OnlineShop\Framework\OfferTool\AbstractOfferToolProduct' => 'OnlineShop_OfferTool_AbstractOfferToolProduct',
        'OnlineShop\Framework\Tools\Config\HelperContainer' => 'OnlineShop_Framework_Config_HelperContainer',
        'OnlineShop\Framework\CartManager\AbstractCartItem' => 'OnlineShop_Framework_AbstractCartItem',
        'OnlineShop\Framework\CartManager\AbstractCart' => 'OnlineShop_Framework_AbstractCart',
        'OnlineShop\Framework\CartManager\AbstractCartCheckoutData' => 'OnlineShop_Framework_AbstractCartCheckoutData',
        'OnlineShop\Framework\CartManager\SessionCartItem' => 'OnlineShop_Framework_Impl_SessionCartItem',
        'OnlineShop\Framework\CartManager\SessionCartCheckoutData' => 'OnlineShop_Framework_Impl_SessionCartCheckoutData',
        'OnlineShop\Framework\CartManager\SessionCart' => 'OnlineShop_Framework_Impl_SessionCart',
        'OnlineShop\Framework\CartManager\CartItem' => 'OnlineShop_Framework_Impl_CartItem',
        'OnlineShop\Framework\CartManager\CartCheckoutData' => 'OnlineShop_Framework_Impl_CartCheckoutData',
        'OnlineShop\Framework\CartManager\Cart' => 'OnlineShop_Framework_Impl_Cart',
        'OnlineShop\Framework\CartManager\CartItem\Dao' => 'OnlineShop_Framework_Impl_CartItem_Resource',
        'OnlineShop\Framework\CartManager\CartItem\Listing' => 'OnlineShop_Framework_Impl_CartItem_List',
        'OnlineShop\Framework\CartManager\CartItem\Listing\Dao' => 'OnlineShop_Framework_Impl_CartItem_List_Resource',
        'OnlineShop\Framework\CartManager\CartCheckoutData\Listing\Dao' => 'OnlineShop_Framework_Impl_CartCheckoutData_List_Resource',
        'OnlineShop\Framework\CartManager\CartCheckoutData\Listing' => 'OnlineShop_Framework_Impl_CartCheckoutData_List',
        'OnlineShop\Framework\CartManager\CartCheckoutData\Dao' => 'OnlineShop_Framework_Impl_CartCheckoutData_Resource',
        'OnlineShop\Framework\CartManager\Cart\Listing\Dao' => 'OnlineShop_Framework_Impl_Cart_List_Resource',
        'OnlineShop\Framework\CartManager\Cart\Listing' => 'OnlineShop_Framework_Impl_Cart_List',
        'OnlineShop\Framework\CartManager\Cart\Dao' => 'OnlineShop_Framework_Impl_Cart_Resource',
        'OnlineShop\Framework\CartManager\MultiCartManager' => 'OnlineShop_Framework_Impl_MultiCartManager',
        'OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator' => 'OnlineShop_Framework_ICartPriceModificator',
        'OnlineShop\Framework\CartManager\CartPriceModificator\Discount' => 'OnlineShop_Framework_Impl_CartPriceModificator_Discount',
        'OnlineShop\Framework\CartManager\CartPriceModificator\Shipping' => 'OnlineShop_Framework_Impl_CartPriceModificator_Shipping',
        'OnlineShop\Framework\CartManager\CartPriceCalculator' => 'OnlineShop_Framework_Impl_CartPriceCalculator',
        'OnlineShop\Framework\PriceSystem\Price' => 'OnlineShop_Framework_Price',
        'OnlineShop\Framework\PriceSystem\ModificatedPrice' => 'OnlineShop_Framework_Impl_ModificatedPrice',
        'OnlineShop\Framework\PriceSystem\AbstractPriceSystem' => 'OnlineShop_Framework_Impl_AbstractPriceSystem',
        'OnlineShop\Framework\PriceSystem\CachingPriceSystem' => 'OnlineShop_Framework_Impl_CachingPriceSystem',
        'OnlineShop\Framework\PriceSystem\AttributePriceSystem' => 'OnlineShop_Framework_Impl_AttributePriceSystem',
        'OnlineShop\Framework\PriceSystem\AbstractPriceInfo' => 'OnlineShop_Framework_AbstractPriceInfo',
        'OnlineShop\Framework\PriceSystem\AttributePriceInfo' => 'OnlineShop_Framework_Impl_AttributePriceInfo',
        'OnlineShop\Framework\PriceSystem\LazyLoadingPriceInfo' => 'OnlineShop_Framework_Impl_LazyLoadingPriceInfo',
        'OnlineShop\Framework\AvailabilitySystem\AttributeAvailabilitySystem' => 'OnlineShop_Framework_Impl_AttributeAvailabilitySystem',


    ];

    private static $mappingInterfaces = [
        'OnlineShop\Framework\IComponent' => 'OnlineShop_Framework_IComponent',
        'OnlineShop\Framework\OfferTool\IService' => 'OnlineShop_OfferTool_IService',
        'OnlineShop\Framework\CartManager\ICartManager' => 'OnlineShop_Framework_ICartManager',
        'OnlineShop\Framework\CartManager\ICart' => 'OnlineShop_Framework_ICart',
        'OnlineShop\Framework\CartManager\ICartItem' => 'OnlineShop_Framework_ICartItem',
        'OnlineShop\Framework\CartManager\CartPriceModificator\IDiscount' => 'OnlineShop_Framework_CartPriceModificator_IDiscount',
        'OnlineShop\Framework\CartManager\CartPriceModificator\IShipping' => 'OnlineShop_Framework_CartPriceModificator_IShipping',
        'OnlineShop\Framework\CartManager\ICartPriceCalculator' => 'OnlineShop_Framework_ICartPriceCalculator',
        'OnlineShop\Framework\PriceSystem\IPrice' => 'OnlineShop_Framework_IPrice',
        'OnlineShop\Framework\PriceSystem\IModificatedPrice' => 'OnlineShop_Framework_Impl_IModificatedPrice',
        'OnlineShop\Framework\PriceSystem\IPriceSystem' => 'OnlineShop_Framework_IPriceSystem',
        'OnlineShop\Framework\PriceSystem\ICachingPriceSystem' => 'OnlineShop_Framework_ICachingPriceSystem',
        'OnlineShop\Framework\PriceSystem\IPriceInfo' => 'OnlineShop_Framework_IPriceInfo',
        'OnlineShop\Framework\AvailabilitySystem\IAvailability' => 'OnlineShop_Framework_IAvailability',
        'OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem' => 'OnlineShop_Framework_IAvailabilitySystem',


    ];



    public static function loadMapping() {
        foreach(self::$mappingInterfaces as $withNamespace => $withoutNamespace) {
            class_alias($withNamespace, $withoutNamespace);
//            class_alias($withoutNamespace, $withoutNamespace);
        }

        foreach(self::$mappingClasses as $withNamespace => $withoutNamespace) {
            class_alias($withNamespace, $withoutNamespace);
//            class_alias($withoutNamespace, $withoutNamespace);
        }

    }


}