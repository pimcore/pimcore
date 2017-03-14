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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Legacy;

class LegacyClassMappingTool {

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
        'OnlineShop\Framework\CartManager\CartPriceModificator\Discount' => 'OnlineShop_Framework_Impl_CartPriceModificator_Discount',
        'OnlineShop\Framework\CartManager\CartPriceModificator\Shipping' => 'OnlineShop_Framework_Impl_CartPriceModificator_Shipping',
        'OnlineShop\Framework\CartManager\CartPriceCalculator' => 'OnlineShop_Framework_Impl_CartPriceCalculator',
        'OnlineShop\Framework\PriceSystem\Price' => 'OnlineShop_Framework_Impl_Price',
        'OnlineShop\Framework\PriceSystem\ModificatedPrice' => 'OnlineShop_Framework_Impl_ModificatedPrice',
        'OnlineShop\Framework\PriceSystem\AbstractPriceSystem' => 'OnlineShop_Framework_Impl_AbstractPriceSystem',
        'OnlineShop\Framework\PriceSystem\CachingPriceSystem' => 'OnlineShop_Framework_Impl_CachingPriceSystem',
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
        'OnlineShop\Framework\PricingManager\Condition\AbstractOrder' => 'OnlineShop_Framework_Impl_Pricing_Condition_AbstractOrder',
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
        'OnlineShop\Framework\Model\AbstractFilterDefinition' => 'OnlineShop_Framework_AbstractFilterDefinition',
        'OnlineShop\Framework\Model\AbstractFilterDefinitionType' => 'OnlineShop_Framework_AbstractFilterDefinitionType',
        'OnlineShop\Framework\Model\AbstractOrder' => 'OnlineShop_Framework_AbstractOrder',
        'OnlineShop\Framework\Model\AbstractOrderItem' => 'OnlineShop_Framework_AbstractOrderItem',
        'OnlineShop\Framework\Model\AbstractPaymentInformation' => 'OnlineShop_Framework_AbstractPaymentInformation',
        'OnlineShop\Framework\Model\AbstractProduct' => 'OnlineShop_Framework_AbstractProduct',
        'OnlineShop\Framework\Model\AbstractSetProductEntry' => 'OnlineShop_Framework_AbstractSetProductEntry',
        'OnlineShop\Framework\Model\AbstractSetProduct' => 'OnlineShop_Framework_AbstractSetProduct',
        'OnlineShop\Framework\Model\AbstractVoucherSeries' => 'OnlineShop_Framework_AbstractVoucherSeries',
        'OnlineShop\Framework\Model\AbstractVoucherTokenType' => 'OnlineShop_Framework_AbstractVoucherTokenType',
        'OnlineShop\Framework\Model\CategoryFilterDefinitionType' => 'OnlineShop_Framework_CategoryFilterDefinitionType',
        'OnlineShop\Framework\VoucherService\Reservation\Dao' => 'OnlineShop_Framework_VoucherService_Reservation_Resource',
        'OnlineShop\Framework\VoucherService\Statistic\Dao' => 'OnlineShop_Framework_VoucherService_Statistic_Resource',
        'OnlineShop\Framework\VoucherService\Token\Listing\Dao' => 'OnlineShop_Framework_VoucherService_Token_List_Resource',
        'OnlineShop\Framework\VoucherService\Token\Listing' => 'OnlineShop_Framework_VoucherService_Token_List',
        'OnlineShop\Framework\VoucherService\Token\Dao' => 'OnlineShop_Framework_VoucherService_Token_Resource',
        'OnlineShop\Framework\VoucherService\Reservation' => 'OnlineShop_Framework_VoucherService_Reservation',
        'OnlineShop\Framework\VoucherService\Statistic' => 'OnlineShop_Framework_VoucherService_Statistic',
        'OnlineShop\Framework\VoucherService\Token' => 'OnlineShop_Framework_VoucherService_Token',
        'OnlineShop\Framework\VoucherService\DefaultService' => 'OnlineShop_Framework_VoucherService_Default',
        'OnlineShop\Framework\VoucherService\TokenManager\AbstractTokenManager' => 'OnlineShop_Framework_VoucherService_AbstractTokenManager',
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
        'OnlineShop\Framework\IndexService\Worker\AbstractWorker' => 'OnlineShop_Framework_IndexService_Tenant_Worker_Abstract',
        'OnlineShop\Framework\IndexService\Worker\DefaultFactFinder' => 'OnlineShop_Framework_IndexService_Tenant_Worker_DefaultFactFinder',
        'OnlineShop\Framework\IndexService\Worker\DefaultFindologic' => 'OnlineShop_Framework_IndexService_Tenant_Worker_DefaultFindologic',
        'OnlineShop\Framework\IndexService\Worker\DefaultMysql' => 'OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql',
        'OnlineShop\Framework\IndexService\Worker\DefaultElasticSearch' => 'OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch',
        'OnlineShop\Framework\IndexService\Worker\OptimizedMysql' => 'OnlineShop_Framework_IndexService_Tenant_Worker_OptimizedMysql',
        'OnlineShop\Framework\IndexService\Config\AbstractConfig' => 'OnlineShop_Framework_IndexService_Tenant_Config_AbstractConfig',
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
        'OnlineShop\Framework\CheckoutManager\AbstractStep' => 'OnlineShop_Framework_Impl_Checkout_AbstractStep',
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
        'OnlineShop\Framework\FilterService\FilterType\AbstractFilterType' => 'OnlineShop_Framework_FilterService_AbstractFilterType',
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
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelection' => 'Pimcore\Model\Object\ClassDefinition\Data\IndexFieldSelection',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelectionCombo' => 'Pimcore\Model\Object\ClassDefinition\Data\IndexFieldSelectionCombo',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelectionField' => 'Pimcore\Model\Object\ClassDefinition\Data\IndexFieldSelectionField',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection' => 'Pimcore\Model\Object\Data\IndexFieldSelection',

        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinition' => 'OnlineShop\Framework\Model\AbstractFilterDefinition',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType' => 'OnlineShop\Framework\Model\AbstractFilterDefinitionType',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\CategoryFilterDefinitionType' => 'OnlineShop\Framework\Model\CategoryFilterDefinitionType',

        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory' => 'OnlineShop\Framework\Model\AbstractCategory',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder' => 'OnlineShop\Framework\Model\AbstractOrder',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrderItem' => 'OnlineShop\Framework\Model\AbstractOrderItem',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractPaymentInformation' => 'OnlineShop\Framework\Model\AbstractPaymentInformation',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct' => 'OnlineShop\Framework\Model\AbstractProduct',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractSetProduct' => 'OnlineShop\Framework\Model\AbstractSetProduct',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractSetProductEntry' => 'OnlineShop\Framework\Model\AbstractSetProductEntry',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractVoucherSeries' => 'OnlineShop\Framework\Model\AbstractVoucherSeries',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractVoucherTokenType' => 'OnlineShop\Framework\Model\AbstractVoucherTokenType',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\DefaultMockup' => 'OnlineShop\Framework\Model\DefaultMockup',

    ];

    private static $symfonyMappingInterfaces = [
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable' => 'OnlineShop\Framework\Model\ICheckoutable',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IIndexable' => 'OnlineShop\Framework\Model\IIndexable',
        'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct' => 'OnlineShop\Framework\Model\IProduct',
    ];


    public static function loadMapping() {

//        foreach(self::$mappingInterfaces as $withNamespace => $withoutNamespace) {
//            @class_alias($withNamespace, $withoutNamespace);
//        }
//
//        foreach(self::$mappingClasses as $withNamespace => $withoutNamespace) {
//            @class_alias($withNamespace, $withoutNamespace);
//        }


        foreach(self::$symfonyMappingInterfaces as $newClass => $oldClass) {
            @class_alias($newClass, $oldClass);
        }

        foreach(self::$symfonyMappingClasses as $newClass => $oldClass) {
            class_alias($newClass, $oldClass);
            if(self::$mappingClasses[$oldClass]) {
                class_alias($newClass, self::$mappingClasses[$oldClass]);
            }
        }


    }

    public static function createNamespaceCompatibilityFile() {
        $fileContent = "<?php \n";
        $fileContent .= '
/**
 * This file is only for IDE auto complete and deprecated visualization
 */';

        foreach(self::$mappingInterfaces as $interfaceNew => $interfaceOld) {

            $fileContent .= '
/**
 * @deprecated
 * Interface ' . $interfaceOld . '
 */
interface ' . $interfaceOld . ' extends \\' . $interfaceNew . ' {};
';

            $fileContent .= "\n\n";
        }

        foreach(self::$mappingClasses as $classNew => $classOld) {

            $fileContent .= '
/**
 * @deprecated
 * Class ' . $classOld . '
 */
class ' . $classOld . ' extends \\' . $classNew . ' {};
';

            $fileContent .= "\n\n";
        }

        file_put_contents(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/config/namespace_compatibility.php', $fileContent);
    }

    public static function generateMarkdownTable() {

        foreach(self::$mappingInterfaces as $withNamespace => $withoutNamespace) {
            echo "|" . $withoutNamespace . " | " . $withNamespace . " | \n";
        }

        foreach(self::$mappingClasses as $withNamespace => $withoutNamespace) {
            echo "|" . $withoutNamespace . " | " . $withNamespace . " | \n";
        }
    }

}
