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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Legacy;

class LegacyClassMappingTool
{
    private static $mappingClasses = [
        'OnlineShop\Plugin' => 'OnlineShop_Plugin',
        'OnlineShop\Framework\Environment' => 'OnlineShop_Framework_Impl_Environment',
        'OnlineShop\Framework\Factory' => 'OnlineShop_Framework_Factory',
        'OnlineShop\Framework\Exception\InvalidConfigException' => 'OnlineShop_Framework_Exception_InvalidConfigException',
        'OnlineShop\Framework\Exception\UnsupportedException' => 'OnlineShop_Framework_Exception_UnsupportedException',
        'OnlineShop\Framework\Exception\VoucherServiceException' => 'OnlineShop_Framework_Exception_VoucherServiceException',
        'OnlineShop\Framework\OfferTool\DefaultService' => 'OnlineShop_OfferTool_Impl_DefaultService',
        'OnlineShop\Framework\OfferTool\AbstractOffer' => 'OnlineShop_OfferTool_AbstractOffer',
        'OnlineShop\Framework\OfferTool\AbstractOfferItem' => 'OnlineShop_OfferTool_AbstractOfferItem',
        'OnlineShop\Framework\OfferTool\AbstractOfferToolProduct' => 'OnlineShop_OfferTool_AbstractOfferToolProduct',
        'OnlineShop\Framework\Tools\Config\HelperContainer' => 'OnlineShop_Framework_Config_HelperContainer',
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
        'OnlineShop\Framework\CartManager\CartPriceModificator\Discount' => 'OnlineShop_Framework_Impl_CartPriceModificator_Discount',
        'OnlineShop\Framework\CartManager\CartPriceModificator\Shipping' => 'OnlineShop_Framework_Impl_CartPriceModificator_Shipping',
        'OnlineShop\Framework\CartManager\CartPriceCalculator' => 'OnlineShop_Framework_Impl_CartPriceCalculator',
        'OnlineShop\Framework\PriceSystem\Price' => 'OnlineShop_Framework_Impl_Price',
        'OnlineShop\Framework\PriceSystem\ModificatedPrice' => 'OnlineShop_Framework_Impl_ModificatedPrice',
        'OnlineShop\Framework\PriceSystem\AttributePriceSystem' => 'OnlineShop_Framework_Impl_AttributePriceSystem',
        'OnlineShop\Framework\PriceSystem\AbstractPriceInfo' => 'OnlineShop_Framework_AbstractPriceInfo',
        'OnlineShop\Framework\PriceSystem\AttributePriceInfo' => 'OnlineShop_Framework_Impl_AttributePriceInfo',
        'OnlineShop\Framework\PriceSystem\LazyLoadingPriceInfo' => 'OnlineShop_Framework_Impl_LazyLoadingPriceInfo',
        'OnlineShop\Framework\AvailabilitySystem\AttributeAvailabilitySystem' => 'OnlineShop_Framework_Impl_AttributeAvailabilitySystem',
        'OnlineShop\Framework\PricingManager\PricingManager' => 'OnlineShop_Framework_Impl_PricingManager',
        'OnlineShop\Framework\PricingManager\Rule' => 'OnlineShop_Framework_Impl_Pricing_Rule',
        'OnlineShop\Framework\PricingManager\Rule\Listing\Dao' => 'OnlineShop_Framework_Impl_Pricing_Rule_List_Resource',
        'OnlineShop\Framework\PricingManager\Rule\Dao' => 'OnlineShop_Framework_Impl_Pricing_Rule_Resource',
        'OnlineShop\Framework\PricingManager\Rule\Listing' => 'OnlineShop_Framework_Impl_Pricing_Rule_List',
        'OnlineShop\Framework\PricingManager\PriceInfo' => 'OnlineShop_Framework_Impl_Pricing_PriceInfo',
        'OnlineShop\Framework\PricingManager\Environment' => 'OnlineShop_Framework_Impl_Pricing_Environment',
        'OnlineShop\Framework\PricingManager\Action\CartDiscount' => 'OnlineShop_Framework_Impl_Pricing_Action_CartDiscount',
        'OnlineShop\Framework\PricingManager\Action\FreeShipping' => 'OnlineShop_Framework_Impl_Pricing_Action_FreeShipping',
        'OnlineShop\Framework\PricingManager\Action\Gift' => 'OnlineShop_Framework_Impl_Pricing_Action_Gift',
        'OnlineShop\Framework\PricingManager\Action\ProductDiscount' => 'OnlineShop_Framework_Impl_Pricing_Action_ProductDiscount',
        'OnlineShop\Framework\PricingManager\Condition\Bracket' => 'OnlineShop_Framework_Impl_Pricing_Condition_Bracket',
        'OnlineShop\Framework\PricingManager\Condition\CartAmount' => 'OnlineShop_Framework_Impl_Pricing_Condition_CartAmount',
        'OnlineShop\Framework\PricingManager\Condition\CatalogCategory' => 'OnlineShop_Framework_Impl_Pricing_Condition_CatalogCategory',
        'OnlineShop\Framework\PricingManager\Condition\CatalogProduct' => 'OnlineShop_Framework_Impl_Pricing_Condition_CatalogProduct',
        'OnlineShop\Framework\PricingManager\Condition\ClientIp' => 'OnlineShop_Framework_Impl_Pricing_Condition_ClientIp',
        'OnlineShop\Framework\PricingManager\Condition\DateRange' => 'OnlineShop_Framework_Impl_Pricing_Condition_DateRange',
        'OnlineShop\Framework\PricingManager\Condition\Sales' => 'OnlineShop_Framework_Impl_Pricing_Condition_Sales',
        'OnlineShop\Framework\PricingManager\Condition\Sold' => 'OnlineShop_Framework_Impl_Pricing_Condition_Sold',
        'OnlineShop\Framework\PricingManager\Condition\Tenant' => 'OnlineShop_Framework_Impl_Pricing_Condition_Tenant',
        'OnlineShop\Framework\PricingManager\Condition\Token' => 'OnlineShop_Framework_Impl_Pricing_Condition_Token',
        'OnlineShop\Framework\PricingManager\Condition\VoucherToken' => 'OnlineShop_Framework_Impl_Pricing_Condition_VoucherToken',
        'OnlineShop\Framework\Model\AbstractCategory' => 'OnlineShop_Framework_AbstractCategory',
        'OnlineShop\Framework\Model\AbstractOrder' => 'OnlineShop_Framework_AbstractOrder',
        'OnlineShop\Framework\Model\AbstractOrderItem' => 'OnlineShop_Framework_AbstractOrderItem',
        'OnlineShop\Framework\Model\AbstractProduct' => 'OnlineShop_Framework_AbstractProduct',
        'OnlineShop\Framework\Model\AbstractSetProductEntry' => 'OnlineShop_Framework_AbstractSetProductEntry',
        'OnlineShop\Framework\Model\AbstractSetProduct' => 'OnlineShop_Framework_AbstractSetProduct',
        'OnlineShop\Framework\Model\AbstractVoucherTokenType' => 'OnlineShop_Framework_AbstractVoucherTokenType',
        'OnlineShop\Framework\VoucherService\Reservation\Dao' => 'OnlineShop_Framework_VoucherService_Reservation_Resource',
        'OnlineShop\Framework\VoucherService\Statistic\Dao' => 'OnlineShop_Framework_VoucherService_Statistic_Resource',
        'OnlineShop\Framework\VoucherService\Token\Listing\Dao' => 'OnlineShop_Framework_VoucherService_Token_List_Resource',
        'OnlineShop\Framework\VoucherService\Token\Listing' => 'OnlineShop_Framework_VoucherService_Token_List',
        'OnlineShop\Framework\VoucherService\Token\Dao' => 'OnlineShop_Framework_VoucherService_Token_Resource',
        'OnlineShop\Framework\VoucherService\Reservation' => 'OnlineShop_Framework_VoucherService_Reservation',
        'OnlineShop\Framework\VoucherService\Statistic' => 'OnlineShop_Framework_VoucherService_Statistic',
        'OnlineShop\Framework\VoucherService\Token' => 'OnlineShop_Framework_VoucherService_Token',
        'OnlineShop\Framework\VoucherService\DefaultService' => 'OnlineShop_Framework_VoucherService_Default',
        'OnlineShop\Framework\VoucherService\TokenManager\Single' => 'OnlineShop_Framework_VoucherService_TokenManager_Single',
        'OnlineShop\Framework\VoucherService\TokenManager\Pattern' => 'OnlineShop_Framework_VoucherService_TokenManager_Pattern',
        'OnlineShop\Framework\PaymentManager\Status' => 'OnlineShop_Framework_Payment_Status',
        'OnlineShop\Framework\PaymentManager\Payment\Datatrans' => 'OnlineShop_Framework_Impl_Payment_Datatrans',
        'OnlineShop\Framework\PaymentManager\Payment\Klarna' => 'OnlineShop_Framework_Impl_Payment_Klarna',
        'OnlineShop\Framework\PaymentManager\Payment\PayPal' => 'OnlineShop_Framework_Impl_Payment_PayPal',
        'OnlineShop\Framework\PaymentManager\Payment\QPay' => 'OnlineShop_Framework_Impl_Payment_QPay',
        'OnlineShop\Framework\PaymentManager\PaymentManager' => 'OnlineShop_Framework_Impl_PaymentManager',
        'OnlineShop\Framework\IndexService\IndexService' => 'OnlineShop_Framework_IndexService',
        'OnlineShop\Framework\IndexService\Getter\DefaultBrickGetterSequenceToMultiselect' => 'OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequenceToMultiselect',
        'OnlineShop\Framework\IndexService\Getter\DefaultBrickGetterSequence' => 'OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequence',
        'OnlineShop\Framework\IndexService\Getter\DefaultBrickGetter' => 'OnlineShop_Framework_IndexService_Getter_DefaultBrickGetter',
        'OnlineShop\Framework\IndexService\Interpreter\AssetId' => 'OnlineShop_Framework_IndexService_Interpreter_AssetId',
        'OnlineShop\Framework\IndexService\Interpreter\DefaultObjects' => 'OnlineShop_Framework_IndexService_Interpreter_DefaultObjects',
        'OnlineShop\Framework\IndexService\Interpreter\DefaultRelations' => 'OnlineShop_Framework_IndexService_Interpreter_DefaultRelations',
        'OnlineShop\Framework\IndexService\Interpreter\DefaultStructuredTable' => 'OnlineShop_Framework_IndexService_Interpreter_DefaultStructuredTable',
        'OnlineShop\Framework\IndexService\Interpreter\DimensionUnitField' => 'OnlineShop_Framework_IndexService_Interpreter_DimensionUnitField',
        'OnlineShop\Framework\IndexService\Interpreter\Numeric' => 'OnlineShop_Framework_IndexService_Interpreter_Numeric',
        'OnlineShop\Framework\IndexService\Interpreter\ObjectId' => 'OnlineShop_Framework_IndexService_Interpreter_ObjectId',
        'OnlineShop\Framework\IndexService\Interpreter\ObjectIdSum' => 'OnlineShop_Framework_IndexService_Interpreter_ObjectIdSum',
        'OnlineShop\Framework\IndexService\Interpreter\ObjectValue' => 'OnlineShop_Framework_IndexService_Interpreter_ObjectValue',
        'OnlineShop\Framework\IndexService\Interpreter\Round' => 'OnlineShop_Framework_IndexService_Interpreter_Round',
        'OnlineShop\Framework\IndexService\Interpreter\Soundex' => 'OnlineShop_Framework_IndexService_Interpreter_Soundex',
        'OnlineShop\Framework\IndexService\Interpreter\StructuredTable' => 'OnlineShop_Framework_IndexService_Interpreter_StructuredTable',
        'OnlineShop\Framework\IndexService\Tool\IndexUpdater' => 'OnlineShop_Framework_IndexService_Tool_IndexUpdater',
        'OnlineShop\Framework\IndexService\Worker\DefaultFactFinder' => 'OnlineShop_Framework_IndexService_Tenant_Worker_DefaultFactFinder',
        'OnlineShop\Framework\IndexService\Worker\DefaultFindologic' => 'OnlineShop_Framework_IndexService_Tenant_Worker_DefaultFindologic',
        'OnlineShop\Framework\IndexService\Worker\DefaultMysql' => 'OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql',
        'OnlineShop\Framework\IndexService\Worker\DefaultElasticSearch' => 'OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch',
        'OnlineShop\Framework\IndexService\Worker\OptimizedMysql' => 'OnlineShop_Framework_IndexService_Tenant_Worker_OptimizedMysql',
        'OnlineShop\Framework\IndexService\Config\DefaultFactFinder' => 'OnlineShop_Framework_IndexService_Tenant_Config_DefaultFactFinder',
        'OnlineShop\Framework\IndexService\Config\DefaultFindologic' => 'OnlineShop_Framework_IndexService_Tenant_Config_DefaultFindologic',
        'OnlineShop\Framework\IndexService\Config\DefaultMysql' => 'OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysql',
        'OnlineShop\Framework\IndexService\Config\DefaultMysqlInheritColumnConfig' => 'OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysqlInheritColumnConfig',
        'OnlineShop\Framework\IndexService\Config\DefaultMysqlSubTenantConfig' => 'OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysqlSubTenantConfig',
        'OnlineShop\Framework\IndexService\Config\ElasticSearch' => 'OnlineShop_Framework_IndexService_Tenant_Config_ElasticSearch',
        'OnlineShop\Framework\IndexService\Config\OptimizedMysql' => 'OnlineShop_Framework_IndexService_Tenant_Config_OptimizedMysql',
        'OnlineShop\Framework\Model\DefaultMockup' => 'OnlineShop_Framework_ProductList_DefaultMockup',
        'OnlineShop\Framework\IndexService\ProductList\DefaultMysql\Dao' => 'OnlineShop_Framework_ProductList_DefaultMysql_Resource',
        'OnlineShop\Framework\IndexService\ProductList\DefaultElasticSearch' => 'OnlineShop_Framework_ProductList_DefaultElasticSearch',
        'OnlineShop\Framework\IndexService\ProductList\DefaultFactFinder' => 'OnlineShop_Framework_ProductList_DefaultFactFinder',
        'OnlineShop\Framework\IndexService\ProductList\DefaultFindologic' => 'OnlineShop_Framework_ProductList_DefaultFindologic',
        'OnlineShop\Framework\IndexService\ProductList\DefaultMysql' => 'OnlineShop_Framework_ProductList_DefaultMysql',
        'OnlineShop\Framework\CheckoutManager\DeliveryAddress' => 'OnlineShop_Framework_Impl_Checkout_DeliveryAddress',
        'OnlineShop\Framework\CheckoutManager\DeliveryDate' => 'OnlineShop_Framework_Impl_Checkout_DeliveryDate',
        'OnlineShop\Framework\CheckoutManager\CheckoutManager' => 'OnlineShop_Framework_Impl_CheckoutManager',
        'OnlineShop\Framework\CheckoutManager\CommitOrderProcessor' => 'OnlineShop_Framework_Impl_CommitOrderProcessor',
        'OnlineShop\Framework\OrderManager\OrderManager' => 'OnlineShop\Framework\Impl\OrderManager',
        'OnlineShop\Framework\OrderManager\AbstractOrderList' => 'OnlineShop\Framework\Impl\OrderManager\AbstractOrderList',
        'OnlineShop\Framework\OrderManager\AbstractOrderListItem' => 'OnlineShop\Framework\Impl\OrderManager\AbstractOrderListItem',
        'OnlineShop\Framework\OrderManager\Order\Listing' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing',
        'OnlineShop\Framework\OrderManager\Order\Agent' => 'OnlineShop\Framework\Impl\OrderManager\Order\Agent',
        'OnlineShop\Framework\OrderManager\Order\Listing\Item' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Item',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\AbstractSearch' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractSearch',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\OrderDateTime' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\OrderDateTime',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\OrderSearch' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\OrderSearch',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Payment' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Payment',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Product' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Product',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\ProductType' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\ProductType',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\Customer' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search\Customer',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\CustomerEmail' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search\CustomerEmail',
        'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\PaymentReference' => 'OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search\PaymentReference',
        'OnlineShop\Framework\FilterService\FilterService' => 'OnlineShop_Framework_FilterService',
        'OnlineShop\Framework\FilterService\FilterType\Input' => 'OnlineShop_Framework_FilterService_Input',
        'OnlineShop\Framework\FilterService\FilterType\MultiSelect' => 'OnlineShop_Framework_FilterService_MultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\MultiSelectFromMultiSelect' => 'OnlineShop_Framework_FilterService_MultiSelectFromMultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\MultiSelectRelation' => 'OnlineShop_Framework_FilterService_MultiSelectRelation',
        'OnlineShop\Framework\FilterService\FilterType\NumberRange' => 'OnlineShop_Framework_FilterService_NumberRange',
        'OnlineShop\Framework\FilterService\FilterType\NumberRangeSelection' => 'OnlineShop_Framework_FilterService_NumberRangeSelection',
        'OnlineShop\Framework\FilterService\FilterType\ProxyFilter' => 'OnlineShop_Framework_FilterService_ProxyFilter',
        'OnlineShop\Framework\FilterService\FilterType\SelectRelation' => 'OnlineShop_Framework_FilterService_SelectRelation',
        'OnlineShop\Framework\FilterService\FilterType\SelectFromMultiSelect' => 'OnlineShop_Framework_FilterService_SelectFromMultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\SelectCategory' => 'OnlineShop_Framework_FilterService_SelectCategory',
        'OnlineShop\Framework\FilterService\FilterType\Select' => 'OnlineShop_Framework_FilterService_Select',
        'OnlineShop\Framework\FilterService\FilterGroupHelper' => 'OnlineShop_Framework_FilterService_FilterGroupHelper',
        'OnlineShop\Framework\FilterService\Helper' => 'OnlineShop_Framework_FilterService_Helper',
        'OnlineShop\Framework\FilterService\FilterType\FactFinder\MultiSelect' => 'OnlineShop_Framework_FilterService_FactFinder_MultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\FactFinder\NumberRange' => 'OnlineShop_Framework_FilterService_FactFinder_NumberRange',
        'OnlineShop\Framework\FilterService\FilterType\FactFinder\Select' => 'OnlineShop_Framework_FilterService_FactFinder_Select',
        'OnlineShop\Framework\FilterService\FilterType\FactFinder\SelectCategory' => 'OnlineShop_Framework_FilterService_FactFinder_SelectCategory',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\Input' => 'OnlineShop_Framework_FilterService_ElasticSearch_Input',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelect' => 'OnlineShop_Framework_FilterService_ElasticSearch_MultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelectFromMultiSelect' => 'OnlineShop_Framework_FilterService_ElasticSearch_MultiSelectFromMultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelectRelation' => 'OnlineShop_Framework_FilterService_ElasticSearch_MultiSelectRelation',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\NumberRange' => 'OnlineShop_Framework_FilterService_ElasticSearch_NumberRange',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\NumberRangeSelection' => 'OnlineShop_Framework_FilterService_ElasticSearch_NumberRangeSelection',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\Select' => 'OnlineShop_Framework_FilterService_ElasticSearch_Select',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectCategory' => 'OnlineShop_Framework_FilterService_ElasticSearch_SelectCategory',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectFromMultiSelect' => 'OnlineShop_Framework_FilterService_ElasticSearch_SelectFromMultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectRelation' => 'OnlineShop_Framework_FilterService_ElasticSearch_SelectRelation',
        'OnlineShop\Framework\FilterService\FilterType\Findologic\MultiSelect' => 'OnlineShop_Framework_FilterService_Findologic_MultiSelect',
        'OnlineShop\Framework\FilterService\FilterType\Findologic\MultiSelectRelation' => 'OnlineShop_Framework_FilterService_Findologic_MultiSelectRelation',
        'OnlineShop\Framework\FilterService\FilterType\Findologic\NumberRange' => 'OnlineShop_Framework_FilterService_Findologic_NumberRange',
        'OnlineShop\Framework\FilterService\FilterType\Findologic\NumberRangeSelection' => 'OnlineShop_Framework_FilterService_Findologic_NumberRangeSelection',
        'OnlineShop\Framework\FilterService\FilterType\Findologic\Select' => 'OnlineShop_Framework_FilterService_Findologic_Select',
        'OnlineShop\Framework\FilterService\FilterType\Findologic\SelectCategory' => 'OnlineShop_Framework_FilterService_Findologic_SelectCategory',
        'OnlineShop\Framework\FilterService\FilterType\Findologic\SelectRelation' => 'OnlineShop_Framework_FilterService_Findologic_SelectRelation',
    ];

    private static $mappingAbstractClasses = [
        'OnlineShop\Framework\Model\AbstractFilterDefinition' => 'OnlineShop_Framework_AbstractFilterDefinition',
        'OnlineShop\Framework\Model\AbstractFilterDefinitionType' => 'OnlineShop_Framework_AbstractFilterDefinitionType',
        'OnlineShop\Framework\Model\CategoryFilterDefinitionType' => 'OnlineShop_Framework_CategoryFilterDefinitionType',
        'OnlineShop\Framework\Model\AbstractPaymentInformation' => 'OnlineShop_Framework_AbstractPaymentInformation',
        'OnlineShop\Framework\Model\AbstractVoucherSeries' => 'OnlineShop_Framework_AbstractVoucherSeries',
        'OnlineShop\Framework\CheckoutManager\AbstractStep' => 'OnlineShop_Framework_Impl_Checkout_AbstractStep',
        'OnlineShop\Framework\PricingManager\Condition\AbstractOrder' => 'OnlineShop_Framework_Impl_Pricing_Condition_AbstractOrder',
        'OnlineShop\Framework\VoucherService\TokenManager\AbstractTokenManager' => 'OnlineShop_Framework_VoucherService_AbstractTokenManager',
        'OnlineShop\Framework\CartManager\AbstractCartItem' => 'OnlineShop_Framework_AbstractCartItem',
        'OnlineShop\Framework\CartManager\AbstractCart' => 'OnlineShop_Framework_AbstractCart',
        'OnlineShop\Framework\CartManager\AbstractCartCheckoutData' => 'OnlineShop_Framework_AbstractCartCheckoutData',
        'OnlineShop\Framework\FilterService\FilterType\AbstractFilterType' => 'OnlineShop_Framework_FilterService_AbstractFilterType',
        'OnlineShop\Framework\IndexService\Config\AbstractConfig' => 'OnlineShop_Framework_IndexService_Tenant_Config_AbstractConfig',
        'OnlineShop\Framework\IndexService\Worker\AbstractWorker' => 'OnlineShop_Framework_IndexService_Tenant_Worker_Abstract',
        'OnlineShop\Framework\PriceSystem\AbstractPriceSystem' => 'OnlineShop_Framework_Impl_AbstractPriceSystem',
        'OnlineShop\Framework\PriceSystem\CachingPriceSystem' => 'OnlineShop_Framework_Impl_CachingPriceSystem',

    ];

    private static $mappingInterfaces = [
        'OnlineShop\Framework\IComponent' => 'OnlineShop_Framework_IComponent',
        'OnlineShop\Framework\IEnvironment' => 'OnlineShop_Framework_IEnvironment',
        'OnlineShop\Framework\OfferTool\IService' => 'OnlineShop_OfferTool_IService',
        'OnlineShop\Framework\CartManager\ICartManager' => 'OnlineShop_Framework_ICartManager',
        'OnlineShop\Framework\CartManager\ICart' => 'OnlineShop_Framework_ICart',
        'OnlineShop\Framework\CartManager\ICartItem' => 'OnlineShop_Framework_ICartItem',
        'OnlineShop\Framework\CartManager\CartPriceModificator\IDiscount' => 'OnlineShop_Framework_CartPriceModificator_IDiscount',
        'OnlineShop\Framework\CartManager\CartPriceModificator\IShipping' => 'OnlineShop_Framework_CartPriceModificator_IShipping',
        'OnlineShop\Framework\CartManager\ICartPriceCalculator' => 'OnlineShop_Framework_ICartPriceCalculator',
        'OnlineShop\Framework\PriceSystem\IPrice' => 'OnlineShop_Framework_IPrice',
        'OnlineShop\Framework\PriceSystem\IModificatedPrice' => 'OnlineShop_Framework_IModificatedPrice',
        'OnlineShop\Framework\PriceSystem\IPriceSystem' => 'OnlineShop_Framework_IPriceSystem',
        'OnlineShop\Framework\PriceSystem\ICachingPriceSystem' => 'OnlineShop_Framework_ICachingPriceSystem',
        'OnlineShop\Framework\PriceSystem\IPriceInfo' => 'OnlineShop_Framework_IPriceInfo',
        'OnlineShop\Framework\AvailabilitySystem\IAvailability' => 'OnlineShop_Framework_IAvailability',
        'OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem' => 'OnlineShop_Framework_IAvailabilitySystem',
        'OnlineShop\Framework\PricingManager\IRule' => 'OnlineShop_Framework_Pricing_IRule',
        'OnlineShop\Framework\PricingManager\IPriceInfo' => 'OnlineShop_Framework_Pricing_IPriceInfo',
        'OnlineShop\Framework\PricingManager\IEnvironment' => 'OnlineShop_Framework_Pricing_IEnvironment',
        'OnlineShop\Framework\PricingManager\ICondition' => 'OnlineShop_Framework_Pricing_ICondition',
        'OnlineShop\Framework\PricingManager\IAction' => 'OnlineShop_Framework_Pricing_IAction',
        'OnlineShop\Framework\PricingManager\IPricingManager' => 'OnlineShop_Framework_IPricingManager',
        'OnlineShop\Framework\PricingManager\Action\IGift' => 'OnlineShop_Framework_Pricing_Action_IGift',
        'OnlineShop\Framework\PricingManager\Action\IDiscount' => 'OnlineShop_Framework_Pricing_Action_IDiscount',
        'OnlineShop\Framework\PricingManager\Condition\IBracket' => 'OnlineShop_Framework_Pricing_Condition_IBracket',
        'OnlineShop\Framework\PricingManager\Condition\ICartAmount' => 'OnlineShop_Framework_Pricing_Condition_ICartAmount',
        'OnlineShop\Framework\PricingManager\Condition\ICartProduct' => 'OnlineShop_Framework_Pricing_Condition_ICartProduct',
        'OnlineShop\Framework\PricingManager\Condition\ICatalogProduct' => 'OnlineShop_Framework_Pricing_Condition_ICatalogProduct',
        'OnlineShop\Framework\PricingManager\Condition\ICategory' => 'OnlineShop_Framework_Pricing_Condition_ICategory',
        'OnlineShop\Framework\PricingManager\Condition\IDateRange' => 'OnlineShop_Framework_Pricing_Condition_IDateRange',
        'OnlineShop\Framework\Model\IIndexable' => 'OnlineShop_Framework_ProductInterfaces_IIndexable',
        'OnlineShop\Framework\Model\ICheckoutable' => 'OnlineShop_Framework_ProductInterfaces_ICheckoutable',
        'OnlineShop\Framework\VoucherService\TokenManager\ITokenManager' => 'OnlineShop_Framework_VoucherService_ITokenManager',
        'OnlineShop\Framework\VoucherService\IVoucherService' => 'OnlineShop_Framework_IVoucherService',
        'OnlineShop\Framework\PaymentManager\Payment\IPayment' => 'OnlineShop_Framework_IPayment',
        'OnlineShop\Framework\PaymentManager\IPaymentManager' => 'OnlineShop_Framework_IPaymentManager',
        'OnlineShop\Framework\PaymentManager\IStatus' => 'OnlineShop_Framework_Payment_IStatus',
        'OnlineShop\Framework\IndexService\Getter\IGetter' => 'OnlineShop_Framework_IndexService_Getter',
        'OnlineShop\Framework\IndexService\Getter\IExtendedGetter' => 'OnlineShop_Framework_IndexService_ExtendedGetter',
        'OnlineShop\Framework\IndexService\Interpreter\IInterpreter' => 'OnlineShop_Framework_IndexService_Interpreter',
        'OnlineShop\Framework\IndexService\Interpreter\IRelationInterpreter' => 'OnlineShop_Framework_IndexService_RelationInterpreter',
        'OnlineShop\Framework\IndexService\Worker\IWorker' => 'OnlineShop_Framework_IndexService_Tenant_IWorker',
        'OnlineShop\Framework\IndexService\Worker\IBatchProcessingWorker' => 'OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker',
        'OnlineShop\Framework\IndexService\Config\IConfig' => 'OnlineShop_Framework_IndexService_Tenant_IConfig',
        'OnlineShop\Framework\IndexService\Config\IElasticSearchConfig' => 'OnlineShop_Framework_IndexService_Tenant_IElasticSearchConfig',
        'OnlineShop\Framework\IndexService\Config\IFactFinderConfig' => 'OnlineShop_Framework_IndexService_Tenant_IFactFinderConfig',
        'OnlineShop\Framework\IndexService\Config\IFindologicConfig' => 'OnlineShop_Framework_IndexService_Tenant_IFindologicConfig',
        'OnlineShop\Framework\IndexService\Config\IMockupConfig' => 'OnlineShop_Framework_IndexService_Tenant_IMockupConfig',
        'OnlineShop\Framework\IndexService\Config\IMysqlConfig' => 'OnlineShop_Framework_IndexService_Tenant_IMysqlConfig',
        'OnlineShop\Framework\IndexService\ProductList\IProductList' => 'OnlineShop_Framework_IProductList',
        'OnlineShop\Framework\CheckoutManager\ICheckoutStep' => 'OnlineShop_Framework_ICheckoutStep',
        'OnlineShop\Framework\CheckoutManager\ICheckoutManager' => 'OnlineShop_Framework_ICheckoutManager',
        'OnlineShop\Framework\CheckoutManager\ICommitOrderProcessor' => 'OnlineShop_Framework_ICommitOrderProcessor',
        'OnlineShop\Framework\OrderManager\IOrderManager' => 'OnlineShop\Framework\IOrderManager',
        'OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator' => 'OnlineShop_Framework_ICartPriceModificator',
    ];


    private static $symfonyMappingClasses = [
        'Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelection' => 'Pimcore\Model\Object\ClassDefinition\Data\IndexFieldSelection',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelectionCombo' => 'Pimcore\Model\Object\ClassDefinition\Data\IndexFieldSelectionCombo',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelectionField' => 'Pimcore\Model\Object\ClassDefinition\Data\IndexFieldSelectionField',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection' => 'Pimcore\Model\Object\Data\IndexFieldSelection',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory' => 'OnlineShop\Framework\Model\AbstractCategory',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder' => 'OnlineShop\Framework\Model\AbstractOrder',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem' => 'OnlineShop\Framework\Model\AbstractOrderItem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct' => 'OnlineShop\Framework\Model\AbstractProduct',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProduct' => 'OnlineShop\Framework\Model\AbstractSetProduct',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry' => 'OnlineShop\Framework\Model\AbstractSetProductEntry',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType' => 'OnlineShop\Framework\Model\AbstractVoucherTokenType',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup' => 'OnlineShop\Framework\Model\DefaultMockup',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Environment' => 'OnlineShop\Framework\Environment',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\AbstractData' => 'OnlineShop\Framework\Tracking\AbstractData',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\AbstractProductData' => 'OnlineShop\Framework\Tracking\AbstractProductData',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductAction' => 'OnlineShop\Framework\Tracking\ProductAction',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductImpression' => 'OnlineShop\Framework\Tracking\ProductImpression',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Transaction' => 'OnlineShop\Framework\Tracking\Transaction',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder' => 'OnlineShop\Framework\Tracking\TrackingItemBuilder',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager' => 'OnlineShop\Framework\Tracking\TrackingManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\Ecommerce' => 'OnlineShop\Framework\Tracking\Tracker\Analytics\Ecommerce',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\EnhancedEcommerce' => 'OnlineShop\Framework\Tracking\Tracker\Analytics\EnhancedEcommerce',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics\UniversalEcommerce' => 'OnlineShop\Framework\Tracking\Tracker\Analytics\UniversalEcommerce',

        'Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOffer' => 'OnlineShop\Framework\OfferTool\AbstractOffer',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOfferItem' => 'OnlineShop\Framework\OfferTool\AbstractOfferItem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\AbstractOfferToolProduct' => 'OnlineShop\Framework\OfferTool\AbstractOfferToolProduct',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\DefaultService' => 'OnlineShop\Framework\OfferTool\DefaultService',

        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\DeliveryAddress' => 'OnlineShop\Framework\CheckoutManager\DeliveryAddress',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\DeliveryDate' => 'OnlineShop\Framework\CheckoutManager\DeliveryDate',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManager' => 'OnlineShop\Framework\CheckoutManager\CheckoutManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor' => 'OnlineShop\Framework\CheckoutManager\CommitOrderProcessor',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule' => 'OnlineShop\Framework\PricingManager\Rule',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Dao' => 'OnlineShop\Framework\PricingManager\Rule\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Listing' => 'OnlineShop\Framework\PricingManager\Rule\Listing',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Listing\Dao' => 'OnlineShop\Framework\PricingManager\Rule\Listing\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Environment' => 'OnlineShop\Framework\PricingManager\Environment',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfo' => 'OnlineShop\Framework\PricingManager\PriceInfo',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager' => 'OnlineShop\Framework\PricingManager\PricingManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\CartDiscount' => 'OnlineShop\Framework\PricingManager\Action\CartDiscount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\FreeShipping' => 'OnlineShop\Framework\PricingManager\Action\FreeShipping',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\Gift' => 'OnlineShop\Framework\PricingManager\Action\Gift',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductDiscount' => 'OnlineShop\Framework\PricingManager\Action\ProductDiscount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\AbstractObjectListCondition' => 'OnlineShop\Framework\PricingManager\Condition\AbstractObjectListCondition',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\Bracket' => 'OnlineShop\Framework\PricingManager\Condition\Bracket',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CartAmount' => 'OnlineShop\Framework\PricingManager\Condition\CartAmount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CatalogCategory' => 'OnlineShop\Framework\PricingManager\Condition\CatalogCategory',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CatalogProduct' => 'OnlineShop\Framework\PricingManager\Condition\CatalogProduct',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\ClientIp' => 'OnlineShop\Framework\PricingManager\Condition\ClientIp',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\DateRange' => 'OnlineShop\Framework\PricingManager\Condition\DateRange',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\Sales' => 'OnlineShop\Framework\PricingManager\Condition\Sales',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\Sold' => 'OnlineShop\Framework\PricingManager\Condition\Sold',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\Tenant' => 'OnlineShop\Framework\PricingManager\Condition\Tenant',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\Token' => 'OnlineShop\Framework\PricingManager\Condition\Token',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\VoucherToken' => 'OnlineShop\Framework\PricingManager\Condition\VoucherToken',

        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation' => 'OnlineShop\Framework\VoucherService\Reservation',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao' => 'OnlineShop\Framework\VoucherService\Reservation\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Statistic' => 'OnlineShop\Framework\VoucherService\Statistic',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Statistic\Dao' => 'OnlineShop\Framework\VoucherService\Statistic\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token' => 'OnlineShop\Framework\VoucherService\Token',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao' => 'OnlineShop\Framework\VoucherService\Token\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Listing' => 'OnlineShop\Framework\VoucherService\Token\Listing',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Listing\Dao' => 'OnlineShop\Framework\VoucherService\Token\Listing\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\DefaultService' => 'OnlineShop\Framework\VoucherService\DefaultService',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\Pattern' => 'OnlineShop\Framework\VoucherService\TokenManager\Pattern',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\Single' => 'OnlineShop\Framework\VoucherService\TokenManager\Single',

        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart' => 'OnlineShop\Framework\CartManager\Cart',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData' => 'OnlineShop\Framework\CartManager\CartCheckoutData',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem' => 'OnlineShop\Framework\CartManager\CartItem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart' => 'OnlineShop\Framework\CartManager\SessionCart',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCartCheckoutData' => 'OnlineShop\Framework\CartManager\SessionCartCheckoutData',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCartItem' => 'OnlineShop\Framework\CartManager\SessionCartItem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Dao' => 'OnlineShop\Framework\CartManager\Cart\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Listing' => 'OnlineShop\Framework\CartManager\Cart\Listing',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Listing\Dao' => 'OnlineShop\Framework\CartManager\Cart\Listing\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Dao' => 'OnlineShop\Framework\CartManager\CartCheckoutData\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Listing' => 'OnlineShop\Framework\CartManager\CartCheckoutData\Listing',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Listing\Dao' => 'OnlineShop\Framework\CartManager\CartCheckoutData\Listing\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Dao' => 'OnlineShop\Framework\CartManager\CartItem\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Listing' => 'OnlineShop\Framework\CartManager\CartItem\Listing',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Listing\Dao' => 'OnlineShop\Framework\CartManager\CartItem\Listing\Dao',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceCalculator' => 'OnlineShop\Framework\CartManager\CartPriceCalculator',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\MultiCartManager' => 'OnlineShop\Framework\CartManager\MultiCartManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Discount' => 'OnlineShop\Framework\CartManager\CartPriceModificator\Discount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Shipping' => 'OnlineShop\Framework\CartManager\CartPriceModificator\Shipping',

        'Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AttributeAvailabilitySystem' => 'OnlineShop\Framework\AvailabilitySystem\AttributeAvailabilitySystem',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException' => 'OnlineShop\Framework\Exception\InvalidConfigException',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException' => 'OnlineShop\Framework\Exception\UnsupportedException',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException' => 'OnlineShop\Framework\Exception\VoucherServiceException',

        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterGroupHelper' => 'OnlineShop\Framework\FilterService\FilterGroupHelper',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterService' => 'OnlineShop\Framework\FilterService\FilterService',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\Helper' => 'OnlineShop\Framework\FilterService\Helper',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Input' => 'OnlineShop\Framework\FilterService\FilterType\Input',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\MultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelectCategory' => 'OnlineShop\Framework\FilterService\FilterType\MultiSelectCategory',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectFromMultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\SelectFromMultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelectFromMultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\MultiSelectFromMultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelectRelation' => 'OnlineShop\Framework\FilterService\FilterType\MultiSelectRelation',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\NumberRange' => 'OnlineShop\Framework\FilterService\FilterType\NumberRange',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\NumberRangeSelection' => 'OnlineShop\Framework\FilterService\FilterType\NumberRangeSelection',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ProxyFilter' => 'OnlineShop\Framework\FilterService\FilterType\ProxyFilter',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Select' => 'OnlineShop\Framework\FilterService\FilterType\Select',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectCategory' => 'OnlineShop\Framework\FilterService\FilterType\SelectCategory',

        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectRelation' => 'OnlineShop\Framework\FilterService\FilterType\SelectRelation',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\Input' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\Input',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\MultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\MultiSelectFromMultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelectFromMultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\MultiSelectRelation' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelectRelation',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\NumberRange' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\NumberRange',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\NumberRangeSelection' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\NumberRangeSelection',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\Select' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\Select',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\SelectCategory' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectCategory',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\SelectFromMultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectFromMultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\SelectRelation' => 'OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectRelation',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\FactFinder\MultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\FactFinder\MultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\FactFinder\NumberRange' => 'OnlineShop\Framework\FilterService\FilterType\FactFinder\NumberRange',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\FactFinder\Select' => 'OnlineShop\Framework\FilterService\FilterType\FactFinder\Select',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\FactFinder\SelectCategory' => 'OnlineShop\Framework\FilterService\FilterType\FactFinder\SelectCategory',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\MultiSelect' => 'OnlineShop\Framework\FilterService\FilterType\Findologic\MultiSelect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\MultiSelectRelation' => 'OnlineShop\Framework\FilterService\FilterType\Findologic\MultiSelectRelation',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\NumberRange' => 'OnlineShop\Framework\FilterService\FilterType\Findologic\NumberRange',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\NumberRangeSelection' => 'OnlineShop\Framework\FilterService\FilterType\Findologic\NumberRangeSelection',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\Select' => 'OnlineShop\Framework\FilterService\FilterType\Findologic\Select',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\SelectCategory' => 'OnlineShop\Framework\FilterService\FilterType\Findologic\SelectCategory',
        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\SelectRelation' => 'OnlineShop\Framework\FilterService\FilterType\Findologic\SelectRelation',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\IndexService' => 'OnlineShop\Framework\IndexService\IndexService',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultFactFinder' => 'OnlineShop\Framework\IndexService\Config\DefaultFactFinder',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultFindologic' => 'OnlineShop\Framework\IndexService\Config\DefaultFindologic',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysql' => 'OnlineShop\Framework\IndexService\Config\DefaultMysql',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysqlInheritColumnConfig' => 'OnlineShop\Framework\IndexService\Config\DefaultMysqlInheritColumnConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysqlSubTenantConfig' => 'OnlineShop\Framework\IndexService\Config\DefaultMysqlSubTenantConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch' => 'OnlineShop\Framework\IndexService\Config\ElasticSearch',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\OptimizedMysql' => 'OnlineShop\Framework\IndexService\Config\OptimizedMysql',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetter' => 'OnlineShop\Framework\IndexService\Getter\DefaultBrickGetter',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetterSequence' => 'OnlineShop\Framework\IndexService\Getter\DefaultBrickGetterSequence',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetterSequenceToMultiselect' => 'OnlineShop\Framework\IndexService\Getter\DefaultBrickGetterSequenceToMultiselect',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\TagsGetter' => 'OnlineShop\Framework\IndexService\Getter\TagsGetter',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\AssetId' => 'OnlineShop\Framework\IndexService\Interpreter\AssetId',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultObjects' => 'OnlineShop\Framework\IndexService\Interpreter\DefaultObjects',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultRelations' => 'OnlineShop\Framework\IndexService\Interpreter\DefaultRelations',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultStructuredTable' => 'OnlineShop\Framework\IndexService\Interpreter\DefaultStructuredTable',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DimensionUnitField' => 'OnlineShop\Framework\IndexService\Interpreter\DimensionUnitField',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IdList' => 'OnlineShop\Framework\IndexService\Interpreter\IdList',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Numeric' => 'OnlineShop\Framework\IndexService\Interpreter\Numeric',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectId' => 'OnlineShop\Framework\IndexService\Interpreter\ObjectId',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectIdSum' => 'OnlineShop\Framework\IndexService\Interpreter\ObjectIdSum',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectValue' => 'OnlineShop\Framework\IndexService\Interpreter\ObjectValue',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Round' => 'OnlineShop\Framework\IndexService\Interpreter\Round',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Soundex' => 'OnlineShop\Framework\IndexService\Interpreter\Soundex',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\StructuredTable' => 'OnlineShop\Framework\IndexService\Interpreter\StructuredTable',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultElasticSearch' => 'OnlineShop\Framework\IndexService\ProductList\DefaultElasticSearch',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultFactFinder' => 'OnlineShop\Framework\IndexService\ProductList\DefaultFactFinder',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultFindologic' => 'OnlineShop\Framework\IndexService\ProductList\DefaultFindologic',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql' => 'OnlineShop\Framework\IndexService\ProductList\DefaultMysql',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql\Dao' => 'OnlineShop\Framework\IndexService\ProductList\DefaultMysql\Dao',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Tool\IndexUpdater' => 'OnlineShop\Framework\IndexService\Tool\IndexUpdater',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultElasticSearch' => 'OnlineShop\Framework\IndexService\Worker\DefaultElasticSearch',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFactFinder' => 'OnlineShop\Framework\IndexService\Worker\DefaultFactFinder',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFindologic' => 'OnlineShop\Framework\IndexService\Worker\DefaultFindologic',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultMysql' => 'OnlineShop\Framework\IndexService\Worker\DefaultMysql',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\OptimizedMysql' => 'OnlineShop\Framework\IndexService\Worker\OptimizedMysql',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\Helper\MySql' => 'OnlineShop\Framework\IndexService\Worker\Helper\MySql',

        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager' => 'OnlineShop\Framework\OrderManager\OrderManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Agent' => 'OnlineShop\Framework\OrderManager\Order\Agent',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing' => 'OnlineShop\Framework\OrderManager\Order\Listing',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Item' => 'OnlineShop\Framework\OrderManager\Order\Listing\Item',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\OrderDateTime' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\OrderDateTime',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\OrderSearch' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\OrderSearch',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\Payment' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Payment',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\Product' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Product',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\ProductType' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\ProductType',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\Search' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\Search\Customer' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\Customer',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\Search\CustomerEmail' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\CustomerEmail',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\Search\PaymentReference' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\PaymentReference',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManager' => 'OnlineShop\Framework\PaymentManager\PaymentManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status' => 'OnlineShop\Framework\PaymentManager\Status',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\Datatrans' => 'OnlineShop\Framework\PaymentManager\Payment\Datatrans',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\Klarna' => 'OnlineShop\Framework\PaymentManager\Payment\Klarna',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PayPal' => 'OnlineShop\Framework\PaymentManager\Payment\PayPal',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\QPay' => 'OnlineShop\Framework\PaymentManager\Payment\QPay',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless' => 'OnlineShop\Framework\PaymentManager\Payment\WirecardSeamless',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AbstractPriceInfo' => 'OnlineShop\Framework\PriceSystem\AbstractPriceInfo',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceInfo' => 'OnlineShop\Framework\PriceSystem\AttributePriceInfo',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem' => 'OnlineShop\Framework\PriceSystem\AttributePriceSystem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\LazyLoadingPriceInfo' => 'OnlineShop\Framework\PriceSystem\LazyLoadingPriceInfo',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPrice' => 'OnlineShop\Framework\PriceSystem\ModificatedPrice',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price' => 'OnlineShop\Framework\PriceSystem\Price',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService' => 'OnlineShop\Framework\PriceSystem\TaxManagement\TaxCalculationService',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry' => 'OnlineShop\Framework\PriceSystem\TaxManagement\TaxEntry',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config\HelperContainer' => 'OnlineShop\Framework\Tools\Config\HelperContainer',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Command\CleanupPendingOrdersCommand' => 'OnlineShop\Framework\Console\Command\CleanupPendingOrdersCommand',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Command\Voucher\CleanupReservationsCommand' => 'OnlineShop\Framework\Console\Command\Voucher\CleanupReservationsCommand',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Command\Voucher\CleanupStatisticsCommand' => 'OnlineShop\Framework\Console\Command\Voucher\CleanupStatisticsCommand',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Factory' => 'OnlineShop\Framework\Factory',
    ];


    private static $symfonyMappingAbstractClasses = [
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinition' => 'OnlineShop\Framework\Model\AbstractFilterDefinition',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType' => 'OnlineShop\Framework\Model\AbstractFilterDefinitionType',

        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCart' => 'OnlineShop\Framework\CartManager\AbstractCart',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCartCheckoutData' => 'OnlineShop\Framework\CartManager\AbstractCartCheckoutData',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCartItem' => 'OnlineShop\Framework\CartManager\AbstractCartItem',

        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\AbstractStep' => 'OnlineShop\Framework\CheckoutManager\AbstractStep',

        'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType' => 'OnlineShop\Framework\FilterService\FilterType\AbstractFilterType',

        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\AbstractOrderList' => 'OnlineShop\Framework\OrderManager\AbstractOrderList',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\AbstractOrderListItem' => 'OnlineShop\Framework\OrderManager\AbstractOrderListItem',

        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\AbstractSearch' => 'OnlineShop\Framework\OrderManager\Order\Listing\Filter\AbstractSearch',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\AbstractConfig' => 'OnlineShop\Framework\IndexService\Config\AbstractConfig',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractBatchProcessingWorker' => 'OnlineShop\Framework\IndexService\Worker\AbstractBatchProcessingWorker',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractMockupCacheWorker' => 'OnlineShop\Framework\IndexService\Worker\AbstractMockupCacheWorker',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractWorker' => 'OnlineShop\Framework\IndexService\Worker\AbstractWorker',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\CategoryFilterDefinitionType' => 'OnlineShop\Framework\Model\CategoryFilterDefinitionType',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation' => 'OnlineShop\Framework\Model\AbstractPaymentInformation',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AbstractPriceSystem' => 'OnlineShop\Framework\PriceSystem\AbstractPriceSystem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\CachingPriceSystem' => 'OnlineShop\Framework\PriceSystem\CachingPriceSystem',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\AbstractOrder' => 'OnlineShop\Framework\PricingManager\Condition\AbstractOrder',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker' => 'OnlineShop\Framework\Tracking\Tracker',

        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\AbstractTokenManager' => 'OnlineShop\Framework\VoucherService\TokenManager\AbstractTokenManager',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherSeries' => 'OnlineShop\Framework\Model\AbstractVoucherSeries',
    ];

    private static $symfonyMappingInterfaces = [
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable' => 'OnlineShop\Framework\Model\ICheckoutable',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable' => 'OnlineShop\Framework\Model\IIndexable',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct' => 'OnlineShop\Framework\Model\IProduct',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment' => 'OnlineShop\Framework\IEnvironment',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IComponent' => 'OnlineShop\Framework\IComponent',

        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutStep' => 'OnlineShop\Framework\CheckoutManager\ICheckoutStep',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManager' => 'OnlineShop\Framework\CheckoutManager\ICheckoutManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessor' => 'OnlineShop\Framework\CheckoutManager\ICommitOrderProcessor',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ICachingPriceSystem' => 'OnlineShop\Framework\PriceSystem\ICachingPriceSystem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IModificatedPrice' => 'OnlineShop\Framework\PriceSystem\IModificatedPrice',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice' => 'OnlineShop\Framework\PriceSystem\IPrice',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo' => 'OnlineShop\Framework\PriceSystem\IPriceInfo',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem' => 'OnlineShop\Framework\PriceSystem\IPriceSystem',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule' => 'OnlineShop\Framework\PricingManager\IRule',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IAction' => 'OnlineShop\Framework\PricingManager\IAction',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition' => 'OnlineShop\Framework\PricingManager\ICondition',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment' => 'OnlineShop\Framework\PricingManager\IEnvironment',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPriceInfo' => 'OnlineShop\Framework\PricingManager\IPriceInfo',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManager' => 'OnlineShop\Framework\PricingManager\IPricingManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\IDiscount' => 'OnlineShop\Framework\PricingManager\Action\IDiscount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\IGift' => 'OnlineShop\Framework\PricingManager\Action\IGift',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\IProductDiscount' => 'OnlineShop\Framework\PricingManager\Action\IProductDiscount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\IBracket' => 'OnlineShop\Framework\PricingManager\Condition\IBracket',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\ICartAmount' => 'OnlineShop\Framework\PricingManager\Condition\ICartAmount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\ICartProduct' => 'OnlineShop\Framework\PricingManager\Condition\ICartProduct',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\ICatalogProduct' => 'OnlineShop\Framework\PricingManager\Condition\ICatalogProduct',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\ICategory' => 'OnlineShop\Framework\PricingManager\Condition\ICategory',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\IDateRange' => 'OnlineShop\Framework\PricingManager\Condition\IDateRange',

        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart' => 'OnlineShop\Framework\CartManager\ICart',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem' => 'OnlineShop\Framework\CartManager\ICartItem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartManager' => 'OnlineShop\Framework\CartManager\ICartManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartPriceCalculator' => 'OnlineShop\Framework\CartManager\ICartPriceCalculator',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator' => 'OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\IDiscount' => 'OnlineShop\Framework\CartManager\CartPriceModificator\IDiscount',
        'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\IShipping' => 'OnlineShop\Framework\CartManager\CartPriceModificator\IShipping',

        'Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailability' => 'OnlineShop\Framework\AvailabilitySystem\IAvailability',
        'Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailabilitySystem' => 'OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem',

        'Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\IService' => 'OnlineShop\Framework\OfferTool\IService',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IConfig' => 'OnlineShop\Framework\IndexService\Config\IConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IElasticSearchConfig' => 'OnlineShop\Framework\IndexService\Config\IElasticSearchConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IFactFinderConfig' => 'OnlineShop\Framework\IndexService\Config\IFactFinderConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IFindologicConfig' => 'OnlineShop\Framework\IndexService\Config\IFindologicConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IMockupConfig' => 'OnlineShop\Framework\IndexService\Config\IMockupConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IMysqlConfig' => 'OnlineShop\Framework\IndexService\Config\IMysqlConfig',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\IExtendedGetter' => 'OnlineShop\Framework\IndexService\Getter\IExtendedGetter',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\IGetter' => 'OnlineShop\Framework\IndexService\Getter\IGetter',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IInterpreter' => 'OnlineShop\Framework\IndexService\Interpreter\IInterpreter',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter' => 'OnlineShop\Framework\IndexService\Interpreter\IRelationInterpreter',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList' => 'OnlineShop\Framework\IndexService\ProductList\IProductList',

        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IBatchProcessingWorker' => 'OnlineShop\Framework\IndexService\Worker\IBatchProcessingWorker',
        'Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IWorker' => 'OnlineShop\Framework\IndexService\Worker\IWorker',

        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderAgent' => 'OnlineShop\Framework\OrderManager\IOrderAgent',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderList' => 'OnlineShop\Framework\OrderManager\IOrderList',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderListFilter' => 'OnlineShop\Framework\OrderManager\IOrderListFilter',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderListItem' => 'OnlineShop\Framework\OrderManager\IOrderListItem',
        'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManager' => 'OnlineShop\Framework\OrderManager\IOrderManager',

        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IPaymentManager' => 'OnlineShop\Framework\PaymentManager\IPaymentManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus' => 'OnlineShop\Framework\PaymentManager\IStatus',
        'Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment' => 'OnlineShop\Framework\PaymentManager\Payment\IPayment',

        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckout' => 'OnlineShop\Framework\Tracking\ICheckout',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckoutComplete' => 'OnlineShop\Framework\Tracking\ICheckoutComplete',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICheckoutStep' => 'OnlineShop\Framework\Tracking\ICheckoutStep',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductActionAdd' => 'OnlineShop\Framework\Tracking\IProductActionAdd',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductActionRemove' => 'OnlineShop\Framework\Tracking\IProductActionRemove',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductImpression' => 'OnlineShop\Framework\Tracking\IProductImpression',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\IProductView' => 'OnlineShop\Framework\Tracking\IProductView',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITracker' => 'OnlineShop\Framework\Tracking\ITracker',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITrackingItemBuilder' => 'OnlineShop\Framework\Tracking\ITrackingItemBuilder',
        'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITrackingManager' => 'OnlineShop\Framework\Tracking\ITrackingManager',

        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\IVoucherService' => 'OnlineShop\Framework\VoucherService\IVoucherService',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\IExportableTokenManager' => 'OnlineShop\Framework\VoucherService\TokenManager\IExportableTokenManager',
        'Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager' => 'OnlineShop\Framework\VoucherService\TokenManager\ITokenManager',
    ];


    public static function loadMapping()
    {
        foreach (self::$symfonyMappingInterfaces as $newClass => $oldClass) {
            class_alias($newClass, $oldClass);
            if (self::$mappingInterfaces[$oldClass]) {
                class_alias($newClass, self::$mappingInterfaces[$oldClass]);
            }
        }

        foreach (self::$symfonyMappingAbstractClasses as $newClass => $oldClass) {
            class_alias($newClass, $oldClass);
            if (self::$mappingAbstractClasses[$oldClass]) {
                class_alias($newClass, self::$mappingAbstractClasses[$oldClass]);
            }
        }

        foreach (self::$symfonyMappingClasses as $newClass => $oldClass) {
            class_alias($newClass, $oldClass);
            if (self::$mappingClasses[$oldClass]) {
                class_alias($newClass, self::$mappingClasses[$oldClass]);
            }
        }
    }

    public static function createNamespaceCompatibilityFile()
    {
        $generatedCode = [];
        foreach (self::$symfonyMappingInterfaces as $interfaceNew => $interfaceOld) {
            $parts = explode('\\', $interfaceOld);

            $className = array_pop($parts);
            $namespace = implode('\\', $parts);

            $generatedCode[$namespace][] = self::generateClass($className, $interfaceNew, 'interface');

            if (self::$mappingInterfaces[$interfaceOld]) {
                $interfaceOld = self::$mappingInterfaces[$interfaceOld];

                $parts = explode('\\', $interfaceOld);

                $className = array_pop($parts);
                $namespace = implode('\\', $parts);

                $generatedCode[$namespace][] = self::generateClass($className, $interfaceNew, 'interface');
            }
        }

        foreach (self::$symfonyMappingAbstractClasses as $classNew => $classOld) {
            $parts = explode('\\', $classOld);

            $className = array_pop($parts);
            $namespace = implode('\\', $parts);

            $generatedCode[$namespace][] = self::generateClass($className, $classNew, 'abstract class');

            if (self::$mappingAbstractClasses[$classOld]) {
                $classOld = self::$mappingAbstractClasses[$classOld];

                $parts = explode('\\', $classOld);

                $className = array_pop($parts);
                $namespace = implode('\\', $parts);

                $generatedCode[$namespace][] = self::generateClass($className, $classNew, 'abstract class');
            }
        }

        foreach (self::$symfonyMappingClasses as $classNew => $classOld) {
            $parts = explode('\\', $classOld);

            $className = array_pop($parts);
            $namespace = implode('\\', $parts);

            $generatedCode[$namespace][] = self::generateClass($className, $classNew, 'class');

            if (self::$mappingClasses[$classOld]) {
                $classOld = self::$mappingClasses[$classOld];

                $parts = explode('\\', $classOld);

                $className = array_pop($parts);
                $namespace = implode('\\', $parts);

                $generatedCode[$namespace][] = self::generateClass($className, $classNew, 'class');
            }
        }


        $fileContent = "<?php \n";
        $fileContent .= '
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
        ' . "\n\n";

        $fileContent .= '
/**
 * This file is only for IDE auto complete and deprecated visualization
 */' . "\n\n";


        ksort($generatedCode);

        foreach ($generatedCode as $namespace => $generatedCodeEntry) {
            $fileContent .= "namespace " . $namespace . " {\n";
            $fileContent .= implode("\n", $generatedCodeEntry);
            $fileContent .= "} \n\n// -- end namespace " . $namespace . " ---------------------------------------------------------------------------------- \n\n\n\n";
        }

        file_put_contents(PIMCORE_PATH . '/lib/Pimcore/Bundle/PimcoreEcommerceFrameworkBundle/config/namespace_compatibility.php', $fileContent);
    }

    protected static function generateClass($className, $parentClassName, $type)
    {
        $fileContent = '
/**
 * @deprecated
 * ' . $type . ' ' . $className . '
 */
' . $type . ' ' . $className . ' extends \\' . $parentClassName . ' {};
';

        $fileContent .= "\n\n";

        return $fileContent;
    }

    public static function generateMarkdownTable()
    {
        foreach (self::$mappingInterfaces as $withNamespace => $withoutNamespace) {
            echo "|" . $withoutNamespace . " | " . $withNamespace . " | \n";
        }

        foreach (self::$mappingClasses as $withNamespace => $withoutNamespace) {
            echo "|" . $withoutNamespace . " | " . $withNamespace . " | \n";
        }
    }
}
