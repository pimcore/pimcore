# Update Notices
Please consider following update notices when updating the e-commerce framework.

## 0.11 - 0.12
- Add Interface `IProductDiscount` to all custom pricing manager actions that modify product prices
- [optional] Chance `name` attribute of `PricingRule` fieldcollection to localized field

## 0.10.1 - 0.11
- Gift items were added to OnlineShopOrder - if you are updating, you need to add a property giftItems (object relation
to OnlineShopOrderItem) to your OnlineShopOrder class. Otherwise a error log message will be issued on checkout.
- Tax Management was included:
   - Add FieldCollection
     - `TaxEntry`
   - Update FieldCollection
     - `OrderPriceModification`: add `netAmount`
   - Add Classes
     - `OnlineShopTaxClass`
   - Update Classes
      - `OnlineShopOrder`: add `subTotalNetPrice`, `totalNetPrice`, `taxInfo`
      - `OnlineShopOrderItem`: add `totalNetPrice`, `taxInfo`
   - Check custom implementations of `IPrice` > implement new methods if necessary, check behavoir of get/setAmount.
   - Check custom implementations of `IPriceSystem` > `createPriceInfoInstance` needs to calculate and set taxes/tax
      entries correctly and add implementation of `getTaxClassForProduct` and `getTaxClassForPriceModification`.
   - Check custom implementations of `OnlineShop\Framework\PricingManager\IPriceInfo` > `getTotalPrice` and `getPrice`
     need to set gross price and start recalculation of taxes.
   - Check custom implementations of `ICartPriceCalculator` > calculate method needs to consider gross and net prices.
   - Check custom implementations of `IOrderManager` > methods `getOrCreateOrderFromCart` and `createOrderItem` need to
     consider net prices if necessary.
   - Check custom implementations of `ICartPriceModificator` > `modify` method need to take care of net and gross prices.


## 0.9.8 - 0.10.0
- After updating the plugin execute `plugins/EcommerceFramework/cli/updateToPhpConfigs.php`.
  The old xml config files will stay untouched, but will no longer be in use.
- Following method signatures changed:
  - `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IConfig::__construct`
    ```php
    //old
        /**
         * @param string $tenantName
         * @param $tenantConfigXml
         * @param null $totalConfigXml
         */
        public function __construct($tenantName, $tenantConfigXml, $totalConfigXml = null) {...}


    //new
        /**
         * @param string $tenantName
         * @param $tenantConfig
         * @param null $totalConfig
         */
        public function __construct($tenantName, $tenantConfig, $totalConfig = null) {...}
    ```

  - \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment::__construct`
    ```php
    //old
        /**
         * @param \Zend_Config $xml
         */
        public function __construct(\Zend_Config $xml);


    //new
        /**
         * @param \Zend_Config $config
         */
        public function __construct(\Zend_Config $config);
    ```



## 0.9.1 - 0.9.8
- Default Elasticserach worker has been renamed from Elasticsearch to DefaultElasticSearch (to be consistent) + Updates for Elasticserach 2.x
- the configuration tag "generalSettings" has been renamed to "clientConfig" to be consistent with other adapters
- The id field of the store tables has been renamed to o_id instead of id (consistency). In Addition o_virtualProductId has been added.
- FactFinder Adapter has been refactored. The data is now stored in separate columns -> export can be done witch one single sql-query.
 
## 0.9.0 - 0.9.1
This version contains some significant refactorings concerning committing orders. These refactorings made some changes necessary 
 that need to be addressed during an update:  

#### OnlineShopConfig.xml
The configuration for `orderstorage` and `parentorderfolder` moved from section `checkoutmanager` to section `ordermanager`. 
For details see [Sample OnlineShopConfig.xml](/config/OnlineShopConfig_sample.xml)

#### Class OnlineShopOrder
The object class OnlineShopOrder needs as additional number field `subTotalPrice`. In addition, lots of fields became not editable by default. 
For details see [Class Defintiion](/install/class_source/class_OnlineShopOrder_export.json)

#### Changes in combination with `IOrderManager`, `ICart`, `ICheckoutManager` and `ICommitOrderProcessor`
 
* `IOrderManager` is now responsible for creating and retrieving order objects. This has the consequence, that adding custom data to 
  an order object from the cart needs to be moved from the `ICommitOrderProcessor` to an extension of the `IOrderManager`. 
  Following methods were added (or moved from other classes): 
    * `setParentOrderFolder()`: added
    * `setOrderClass()`: added
    * `setOrderItemClass()`: added
    * `getOrCreateOrderFromCart()`: added
    * `getOrderFromCart()`: added
    * `protected applyCustomCheckoutDataToOrder()`: use this method for adding custom checkout information to order objects.
    
     For details see [IOrderManager](/lib/OnlineShop/Framework/OrderManager/IOrderManager.php) and [OrderManager](/lib/OnlineShop/Framework/OrderManager/OrderManager.php).



* `ICart` now has a new method `isCartReadOnly` which returns if cart is read only or not - is not based on the environment entry anymore. 

* `ICheckoutManager` should be the one-stop API for a checkout controller. Following methods/constants were added or removed:
    * `hasActivePayment()`: added, use this to check, if there is an active payment ongoing. If so, the best thing would be to redirect to the payment page. 
    * `cancelStartedOrderPayment()`: added, use this to to cancel payment when user cancels payment (keep in mind, here no request to payment is sent). 
    * `handlePaymentResponseAndCommitOrderPayment()`: added, combines handling payment response and committing order payment. use this in your controller. 
    * The constants `FINISHED`, `CART_READONLY_PREFIX` and `COMMITTED` were removed, use the getter methods instead. The information 
    for these states is now calcualted based on the order object.
    
     For details see [ICheckoutManager](/lib/OnlineShop/Framework/CheckoutManager/ICheckoutManager.php) and  [CheckoutManager](/lib/OnlineShop/Framework/CheckoutManager/CheckoutManager.php). 



* `ICommitOrderProcessor` is the only place for committing orders and now works without cart. This has the consequence, that the cart is not available in the 
  `ICommitOrderProcessor` anymore which might have some impact on custom implementations! As a positive consequence, the `CommitOrderProcessor` can now be 
   instantiated without a `CheckoutManager` and without a cart and therefore can be used for server-by-server payment commits. Following methods have changed:  
    * `getOrCreateOrder()`: and depending methods were moved to `IOrderManager`
    * `createOrderNumber()`: moved to `IOrderManager`
    * `getOrCreateActivePaymentInfo()`: removed
    * `updateOrderPayment()`: removed
    * `committedOrderWithSamePaymentExists()`: added, use this to check, if order with same payment is already committed. 
    * `handlePaymentResponseAndCommitOrderPayment()`: added, basically does the work of handling payment response and commit the order
    * `processOrder` and `sendConfirmationMail` do not have access to the cart anymore.  
    
     For details see [ICommitOrderProcessor](/lib/OnlineShop/Framework/CheckoutManager/ICommitOrderProcessor.php) and [CommitOrderProcessor](/lib/OnlineShop/Framework/CheckoutManager/CommitOrderProcessor.php)

#### Refactoring to Namespaces
See following table for changed class names. Theoretically, this change should be fully backwards compatible. 
For all changed class names, class aliases are added on plugin init (For details see [LegacyClassMappingTool](/lib/OnlineShop/LegacyClassMappingTool.php)). 


| Old | New | 
| --- | --- | 
|OnlineShop_Framework_IComponent | OnlineShop\Framework\IComponent |
|OnlineShop_Framework_IEnvironment | OnlineShop\Framework\IEnvironment |
|OnlineShop_OfferTool_IService | OnlineShop\Framework\OfferTool\IService |
|OnlineShop_Framework_ICartManager | OnlineShop\Framework\CartManager\ICartManager |
|OnlineShop_Framework_ICart | OnlineShop\Framework\CartManager\ICart |
|OnlineShop_Framework_ICartItem | OnlineShop\Framework\CartManager\ICartItem |
|OnlineShop_Framework_CartPriceModificator_IDiscount | OnlineShop\Framework\CartManager\CartPriceModificator\IDiscount |
|OnlineShop_Framework_CartPriceModificator_IShipping | OnlineShop\Framework\CartManager\CartPriceModificator\IShipping |
|OnlineShop_Framework_ICartPriceCalculator | OnlineShop\Framework\CartManager\ICartPriceCalculator |
|OnlineShop_Framework_IPrice | OnlineShop\Framework\PriceSystem\IPrice |
|OnlineShop_Framework_Impl_IModificatedPrice | OnlineShop\Framework\PriceSystem\IModificatedPrice |
|OnlineShop_Framework_IPriceSystem | OnlineShop\Framework\PriceSystem\IPriceSystem |
|OnlineShop_Framework_ICachingPriceSystem | OnlineShop\Framework\PriceSystem\ICachingPriceSystem |
|OnlineShop_Framework_IPriceInfo | OnlineShop\Framework\PriceSystem\IPriceInfo |
|OnlineShop_Framework_IAvailability | OnlineShop\Framework\AvailabilitySystem\IAvailability |
|OnlineShop_Framework_IAvailabilitySystem | OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem |
|OnlineShop_Framework_Pricing_IRule | OnlineShop\Framework\PricingManager\IRule |
|OnlineShop_Framework_Pricing_IPriceInfo | OnlineShop\Framework\PricingManager\IPriceInfo |
|OnlineShop_Framework_Pricing_IEnvironment | OnlineShop\Framework\PricingManager\IEnvironment |
|OnlineShop_Framework_Pricing_ICondition | OnlineShop\Framework\PricingManager\ICondition |
|OnlineShop_Framework_Pricing_IAction | OnlineShop\Framework\PricingManager\IAction |
|OnlineShop_Framework_IPricingManager | OnlineShop\Framework\PricingManager\IPricingManager |
|OnlineShop_Framework_Pricing_Action_IGift | OnlineShop\Framework\PricingManager\Action\IGift |
|OnlineShop_Framework_Pricing_Action_IDiscount | OnlineShop\Framework\PricingManager\Action\IDiscount |
|OnlineShop_Framework_Pricing_Condition_IBracket | OnlineShop\Framework\PricingManager\Condition\IBracket |
|OnlineShop_Framework_Pricing_Condition_ICartAmount | OnlineShop\Framework\PricingManager\Condition\ICartAmount |
|OnlineShop_Framework_Pricing_Condition_ICartProduct | OnlineShop\Framework\PricingManager\Condition\ICartProduct |
|OnlineShop_Framework_Pricing_Condition_ICatalogProduct | OnlineShop\Framework\PricingManager\Condition\ICatalogProduct |
|OnlineShop_Framework_Pricing_Condition_ICategory | OnlineShop\Framework\PricingManager\Condition\ICategory |
|OnlineShop_Framework_Pricing_Condition_IDateRange | OnlineShop\Framework\PricingManager\Condition\IDateRange |
|OnlineShop_Framework_ProductInterfaces_IIndexable | OnlineShop\Framework\Model\IIndexable |
|OnlineShop_Framework_ProductInterfaces_ICheckoutable | OnlineShop\Framework\Model\ICheckoutable |
|OnlineShop_Framework_VoucherService_ITokenManager | OnlineShop\Framework\VoucherService\TokenManager\ITokenManager |
|OnlineShop_Framework_IVoucherService | OnlineShop\Framework\VoucherService\IVoucherService |
|OnlineShop_Framework_IPayment | OnlineShop\Framework\PaymentManager\Payment\IPayment |
|OnlineShop_Framework_IPaymentManager | OnlineShop\Framework\PaymentManager\IPaymentManager |
|OnlineShop_Framework_Payment_IStatus | OnlineShop\Framework\PaymentManager\IStatus |
|OnlineShop_Framework_IndexService_Getter | OnlineShop\Framework\IndexService\Getter\IGetter |
|OnlineShop_Framework_IndexService_ExtendedGetter | OnlineShop\Framework\IndexService\Getter\IExtendedGetter |
|OnlineShop_Framework_IndexService_Interpreter | OnlineShop\Framework\IndexService\Interpreter\IInterpreter |
|OnlineShop_Framework_IndexService_RelationInterpreter | OnlineShop\Framework\IndexService\Interpreter\IRelationInterpreter |
|OnlineShop_Framework_IndexService_Tenant_IWorker | OnlineShop\Framework\IndexService\Worker\IWorker |
|OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker | OnlineShop\Framework\IndexService\Worker\IBatchProcessingWorker |
|OnlineShop_Framework_IndexService_Tenant_IConfig | OnlineShop\Framework\IndexService\Config\IConfig |
|OnlineShop_Framework_IndexService_Tenant_IElasticSearchConfig | OnlineShop\Framework\IndexService\Config\IElasticSearchConfig |
|OnlineShop_Framework_IndexService_Tenant_IFactFinderConfig | OnlineShop\Framework\IndexService\Config\IFactFinderConfig |
|OnlineShop_Framework_IndexService_Tenant_IFindologicConfig | OnlineShop\Framework\IndexService\Config\IFindologicConfig |
|OnlineShop_Framework_IndexService_Tenant_IMockupConfig | OnlineShop\Framework\IndexService\Config\IMockupConfig |
|OnlineShop_Framework_IndexService_Tenant_IMysqlConfig | OnlineShop\Framework\IndexService\Config\IMysqlConfig |
|OnlineShop_Framework_IProductList | OnlineShop\Framework\IndexService\ProductList\IProductList |
|OnlineShop_Framework_ICheckoutStep | OnlineShop\Framework\CheckoutManager\ICheckoutStep |
|OnlineShop_Framework_ICheckoutManager | OnlineShop\Framework\CheckoutManager\ICheckoutManager |
|OnlineShop_Framework_ICommitOrderProcessor | OnlineShop\Framework\CheckoutManager\ICommitOrderProcessor |
|OnlineShop\Framework\IOrderManager | OnlineShop\Framework\OrderManager\IOrderManager |
|OnlineShop_Framework_ICartPriceModificator | OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator |
|OnlineShop_Plugin | OnlineShop\Plugin |
|OnlineShop_Framework_Impl_Environment | OnlineShop\Framework\Environment |
|OnlineShop_Framework_Factory | OnlineShop\Framework\Factory |
|OnlineShop_Framework_Exception_InvalidConfigException | OnlineShop\Framework\Exception\InvalidConfigException |
|OnlineShop_Framework_Exception_UnsupportedException | OnlineShop\Framework\Exception\UnsupportedException |
|OnlineShop_Framework_Exception_VoucherServiceException | OnlineShop\Framework\Exception\VoucherServiceException |
|OnlineShop_OfferTool_Impl_DefaultService | OnlineShop\Framework\OfferTool\DefaultService |
|OnlineShop_OfferTool_AbstractOffer | OnlineShop\Framework\OfferTool\AbstractOffer |
|OnlineShop_OfferTool_AbstractOfferItem | OnlineShop\Framework\OfferTool\AbstractOfferItem |
|OnlineShop_OfferTool_AbstractOfferToolProduct | OnlineShop\Framework\OfferTool\AbstractOfferToolProduct |
|OnlineShop_Framework_Config_HelperContainer | OnlineShop\Framework\Tools\Config\HelperContainer |
|OnlineShop_Framework_AbstractCartItem | OnlineShop\Framework\CartManager\AbstractCartItem |
|OnlineShop_Framework_AbstractCart | OnlineShop\Framework\CartManager\AbstractCart |
|OnlineShop_Framework_AbstractCartCheckoutData | OnlineShop\Framework\CartManager\AbstractCartCheckoutData |
|OnlineShop_Framework_Impl_SessionCartItem | OnlineShop\Framework\CartManager\SessionCartItem |
|OnlineShop_Framework_Impl_SessionCartCheckoutData | OnlineShop\Framework\CartManager\SessionCartCheckoutData |
|OnlineShop_Framework_Impl_SessionCart | OnlineShop\Framework\CartManager\SessionCart |
|OnlineShop_Framework_Impl_CartItem | OnlineShop\Framework\CartManager\CartItem |
|OnlineShop_Framework_Impl_CartCheckoutData | OnlineShop\Framework\CartManager\CartCheckoutData |
|OnlineShop_Framework_Impl_Cart | OnlineShop\Framework\CartManager\Cart |
|OnlineShop_Framework_Impl_CartItem_Resource | OnlineShop\Framework\CartManager\CartItem\Dao |
|OnlineShop_Framework_Impl_CartItem_List | OnlineShop\Framework\CartManager\CartItem\Listing |
|OnlineShop_Framework_Impl_CartItem_List_Resource | OnlineShop\Framework\CartManager\CartItem\Listing\Dao |
|OnlineShop_Framework_Impl_CartCheckoutData_List_Resource | OnlineShop\Framework\CartManager\CartCheckoutData\Listing\Dao |
|OnlineShop_Framework_Impl_CartCheckoutData_List | OnlineShop\Framework\CartManager\CartCheckoutData\Listing |
|OnlineShop_Framework_Impl_CartCheckoutData_Resource | OnlineShop\Framework\CartManager\CartCheckoutData\Dao |
|OnlineShop_Framework_Impl_Cart_List_Resource | OnlineShop\Framework\CartManager\Cart\Listing\Dao |
|OnlineShop_Framework_Impl_Cart_List | OnlineShop\Framework\CartManager\Cart\Listing |
|OnlineShop_Framework_Impl_Cart_Resource | OnlineShop\Framework\CartManager\Cart\Dao |
|OnlineShop_Framework_Impl_MultiCartManager | OnlineShop\Framework\CartManager\MultiCartManager |
|OnlineShop_Framework_Impl_CartPriceModificator_Discount | OnlineShop\Framework\CartManager\CartPriceModificator\Discount |
|OnlineShop_Framework_Impl_CartPriceModificator_Shipping | OnlineShop\Framework\CartManager\CartPriceModificator\Shipping |
|OnlineShop_Framework_Impl_CartPriceCalculator | OnlineShop\Framework\CartManager\CartPriceCalculator |
|OnlineShop_Framework_Impl_Price | OnlineShop\Framework\PriceSystem\Price |
|OnlineShop_Framework_Impl_ModificatedPrice | OnlineShop\Framework\PriceSystem\ModificatedPrice |
|OnlineShop_Framework_Impl_AbstractPriceSystem | OnlineShop\Framework\PriceSystem\AbstractPriceSystem |
|OnlineShop_Framework_Impl_CachingPriceSystem | OnlineShop\Framework\PriceSystem\CachingPriceSystem |
|OnlineShop_Framework_Impl_AttributePriceSystem | OnlineShop\Framework\PriceSystem\AttributePriceSystem |
|OnlineShop_Framework_AbstractPriceInfo | OnlineShop\Framework\PriceSystem\AbstractPriceInfo |
|OnlineShop_Framework_Impl_AttributePriceInfo | OnlineShop\Framework\PriceSystem\AttributePriceInfo |
|OnlineShop_Framework_Impl_LazyLoadingPriceInfo | OnlineShop\Framework\PriceSystem\LazyLoadingPriceInfo |
|OnlineShop_Framework_Impl_AttributeAvailabilitySystem | OnlineShop\Framework\AvailabilitySystem\AttributeAvailabilitySystem |
|OnlineShop_Framework_Impl_PricingManager | OnlineShop\Framework\PricingManager\PricingManager |
|OnlineShop_Framework_Impl_Pricing_Rule | OnlineShop\Framework\PricingManager\Rule |
|OnlineShop_Framework_Impl_Pricing_Rule_List_Resource | OnlineShop\Framework\PricingManager\Rule\Listing\Dao |
|OnlineShop_Framework_Impl_Pricing_Rule_Resource | OnlineShop\Framework\PricingManager\Rule\Dao |
|OnlineShop_Framework_Impl_Pricing_Rule_List | OnlineShop\Framework\PricingManager\Rule\Listing |
|OnlineShop_Framework_Impl_Pricing_PriceInfo | OnlineShop\Framework\PricingManager\PriceInfo |
|OnlineShop_Framework_Impl_Pricing_Environment | OnlineShop\Framework\PricingManager\Environment |
|OnlineShop_Framework_Impl_Pricing_Action_CartDiscount | OnlineShop\Framework\PricingManager\Action\CartDiscount |
|OnlineShop_Framework_Impl_Pricing_Action_FreeShipping | OnlineShop\Framework\PricingManager\Action\FreeShipping |
|OnlineShop_Framework_Impl_Pricing_Action_Gift | OnlineShop\Framework\PricingManager\Action\Gift |
|OnlineShop_Framework_Impl_Pricing_Action_ProductDiscount | OnlineShop\Framework\PricingManager\Action\ProductDiscount |
|OnlineShop_Framework_Impl_Pricing_Condition_AbstractOrder | OnlineShop\Framework\PricingManager\Condition\AbstractOrder |
|OnlineShop_Framework_Impl_Pricing_Condition_Bracket | OnlineShop\Framework\PricingManager\Condition\Bracket |
|OnlineShop_Framework_Impl_Pricing_Condition_CartAmount | OnlineShop\Framework\PricingManager\Condition\CartAmount |
|OnlineShop_Framework_Impl_Pricing_Condition_CatalogCategory | OnlineShop\Framework\PricingManager\Condition\CatalogCategory |
|OnlineShop_Framework_Impl_Pricing_Condition_CatalogProduct | OnlineShop\Framework\PricingManager\Condition\CatalogProduct |
|OnlineShop_Framework_Impl_Pricing_Condition_ClientIp | OnlineShop\Framework\PricingManager\Condition\ClientIp |
|OnlineShop_Framework_Impl_Pricing_Condition_DateRange | OnlineShop\Framework\PricingManager\Condition\DateRange |
|OnlineShop_Framework_Impl_Pricing_Condition_Sales | OnlineShop\Framework\PricingManager\Condition\Sales |
|OnlineShop_Framework_Impl_Pricing_Condition_Sold | OnlineShop\Framework\PricingManager\Condition\Sold |
|OnlineShop_Framework_Impl_Pricing_Condition_Tenant | OnlineShop\Framework\PricingManager\Condition\Tenant |
|OnlineShop_Framework_Impl_Pricing_Condition_Token | OnlineShop\Framework\PricingManager\Condition\Token |
|OnlineShop_Framework_Impl_Pricing_Condition_VoucherToken | OnlineShop\Framework\PricingManager\Condition\VoucherToken |
|OnlineShop_Framework_AbstractCategory | OnlineShop\Framework\Model\AbstractCategory |
|OnlineShop_Framework_AbstractFilterDefinition | OnlineShop\Framework\Model\AbstractFilterDefinition |
|OnlineShop_Framework_AbstractFilterDefinitionType | OnlineShop\Framework\Model\AbstractFilterDefinitionType |
|OnlineShop_Framework_AbstractOrder | OnlineShop\Framework\Model\AbstractOrder |
|OnlineShop_Framework_AbstractOrderItem | OnlineShop\Framework\Model\AbstractOrderItem |
|OnlineShop_Framework_AbstractPaymentInformation | OnlineShop\Framework\Model\AbstractPaymentInformation |
|OnlineShop_Framework_AbstractProduct | OnlineShop\Framework\Model\AbstractProduct |
|OnlineShop_Framework_AbstractSetProductEntry | OnlineShop\Framework\Model\AbstractSetProductEntry |
|OnlineShop_Framework_AbstractSetProduct | OnlineShop\Framework\Model\AbstractSetProduct |
|OnlineShop_Framework_AbstractVoucherSeries | OnlineShop\Framework\Model\AbstractVoucherSeries |
|OnlineShop_Framework_AbstractVoucherTokenType | OnlineShop\Framework\Model\AbstractVoucherTokenType |
|OnlineShop_Framework_CategoryFilterDefinitionType | OnlineShop\Framework\Model\CategoryFilterDefinitionType |
|OnlineShop_Framework_VoucherService_Reservation_Resource | OnlineShop\Framework\VoucherService\Reservation\Dao |
|OnlineShop_Framework_VoucherService_Statistic_Resource | OnlineShop\Framework\VoucherService\Statistic\Dao |
|OnlineShop_Framework_VoucherService_Token_List_Resource | OnlineShop\Framework\VoucherService\Token\Listing\Dao |
|OnlineShop_Framework_VoucherService_Token_List | OnlineShop\Framework\VoucherService\Token\Listing |
|OnlineShop_Framework_VoucherService_Token_Resource | OnlineShop\Framework\VoucherService\Token\Dao |
|OnlineShop_Framework_VoucherService_Reservation | OnlineShop\Framework\VoucherService\Reservation |
|OnlineShop_Framework_VoucherService_Statistic | OnlineShop\Framework\VoucherService\Statistic |
|OnlineShop_Framework_VoucherService_Token | OnlineShop\Framework\VoucherService\Token |
|OnlineShop_Framework_VoucherService_Default | OnlineShop\Framework\VoucherService\DefaultService |
|OnlineShop_Framework_VoucherService_AbstractTokenManager | OnlineShop\Framework\VoucherService\TokenManager\AbstractTokenManager |
|OnlineShop_Framework_VoucherService_TokenManager_Single | OnlineShop\Framework\VoucherService\TokenManager\Single |
|OnlineShop_Framework_VoucherService_TokenManager_Pattern | OnlineShop\Framework\VoucherService\TokenManager\Pattern |
|OnlineShop_Framework_Payment_Status | OnlineShop\Framework\PaymentManager\Status |
|OnlineShop_Framework_Impl_Payment_Datatrans | OnlineShop\Framework\PaymentManager\Payment\Datatrans |
|OnlineShop_Framework_Impl_Payment_Klarna | OnlineShop\Framework\PaymentManager\Payment\Klarna |
|OnlineShop_Framework_Impl_Payment_PayPal | OnlineShop\Framework\PaymentManager\Payment\PayPal |
|OnlineShop_Framework_Impl_Payment_QPay | OnlineShop\Framework\PaymentManager\Payment\QPay |
|OnlineShop_Framework_Impl_PaymentManager | OnlineShop\Framework\PaymentManager\PaymentManager |
|OnlineShop_Framework_IndexService | OnlineShop\Framework\IndexService\IndexService |
|OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequenceToMultiselect | OnlineShop\Framework\IndexService\Getter\DefaultBrickGetterSequenceToMultiselect |
|OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequence | OnlineShop\Framework\IndexService\Getter\DefaultBrickGetterSequence |
|OnlineShop_Framework_IndexService_Getter_DefaultBrickGetter | OnlineShop\Framework\IndexService\Getter\DefaultBrickGetter |
|OnlineShop_Framework_IndexService_Interpreter_AssetId | OnlineShop\Framework\IndexService\Interpreter\AssetId |
|OnlineShop_Framework_IndexService_Interpreter_DefaultObjects | OnlineShop\Framework\IndexService\Interpreter\DefaultObjects |
|OnlineShop_Framework_IndexService_Interpreter_DefaultRelations | OnlineShop\Framework\IndexService\Interpreter\DefaultRelations |
|OnlineShop_Framework_IndexService_Interpreter_DefaultStructuredTable | OnlineShop\Framework\IndexService\Interpreter\DefaultStructuredTable |
|OnlineShop_Framework_IndexService_Interpreter_DimensionUnitField | OnlineShop\Framework\IndexService\Interpreter\DimensionUnitField |
|OnlineShop_Framework_IndexService_Interpreter_Numeric | OnlineShop\Framework\IndexService\Interpreter\Numeric |
|OnlineShop_Framework_IndexService_Interpreter_ObjectId | OnlineShop\Framework\IndexService\Interpreter\ObjectId |
|OnlineShop_Framework_IndexService_Interpreter_ObjectIdSum | OnlineShop\Framework\IndexService\Interpreter\ObjectIdSum |
|OnlineShop_Framework_IndexService_Interpreter_ObjectValue | OnlineShop\Framework\IndexService\Interpreter\ObjectValue |
|OnlineShop_Framework_IndexService_Interpreter_Round | OnlineShop\Framework\IndexService\Interpreter\Round |
|OnlineShop_Framework_IndexService_Interpreter_Soundex | OnlineShop\Framework\IndexService\Interpreter\Soundex |
|OnlineShop_Framework_IndexService_Interpreter_StructuredTable | OnlineShop\Framework\IndexService\Interpreter\StructuredTable |
|OnlineShop_Framework_IndexService_Tool_IndexUpdater | OnlineShop\Framework\IndexService\Tool\IndexUpdater |
|OnlineShop_Framework_IndexService_Tenant_Worker_Abstract | OnlineShop\Framework\IndexService\Worker\AbstractWorker |
|OnlineShop_Framework_IndexService_Tenant_Worker_DefaultFactFinder | OnlineShop\Framework\IndexService\Worker\DefaultFactFinder |
|OnlineShop_Framework_IndexService_Tenant_Worker_DefaultFindologic | OnlineShop\Framework\IndexService\Worker\DefaultFindologic |
|OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql | OnlineShop\Framework\IndexService\Worker\DefaultMysql |
|OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch | OnlineShop\Framework\IndexService\Worker\ElasticSearch |
|OnlineShop_Framework_IndexService_Tenant_Worker_OptimizedMysql | OnlineShop\Framework\IndexService\Worker\OptimizedMysql |
|OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing | OnlineShop\Framework\IndexService\Worker\WorkerTraits\BatchProcessing |
|OnlineShop_Framework_IndexService_Tenant_Worker_Traits_MockupCache | OnlineShop\Framework\IndexService\Worker\WorkerTraits\MockupCache |
|OnlineShop_Framework_IndexService_Tenant_Config_AbstractConfig | OnlineShop\Framework\IndexService\Config\AbstractConfig |
|OnlineShop_Framework_IndexService_Tenant_Config_DefaultFactFinder | OnlineShop\Framework\IndexService\Config\DefaultFactFinder |
|OnlineShop_Framework_IndexService_Tenant_Config_DefaultFindologic | OnlineShop\Framework\IndexService\Config\DefaultFindologic |
|OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysql | OnlineShop\Framework\IndexService\Config\DefaultMysql |
|OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysqlInheritColumnConfig | OnlineShop\Framework\IndexService\Config\DefaultMysqlInheritColumnConfig |
|OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysqlSubTenantConfig | OnlineShop\Framework\IndexService\Config\DefaultMysqlSubTenantConfig |
|OnlineShop_Framework_IndexService_Tenant_Config_ElasticSearch | OnlineShop\Framework\IndexService\Config\ElasticSearch |
|OnlineShop_Framework_IndexService_Tenant_Config_OptimizedMysql | OnlineShop\Framework\IndexService\Config\OptimizedMysql |
|OnlineShop_Framework_ProductList_DefaultMockup | OnlineShop\Framework\Model\DefaultMockup |
|OnlineShop_Framework_ProductList_DefaultMysql_Resource | OnlineShop\Framework\IndexService\ProductList\DefaultMysql\Dao |
|OnlineShop_Framework_ProductList_DefaultElasticSearch | OnlineShop\Framework\IndexService\ProductList\DefaultElasticSearch |
|OnlineShop_Framework_ProductList_DefaultFactFinder | OnlineShop\Framework\IndexService\ProductList\DefaultFactFinder |
|OnlineShop_Framework_ProductList_DefaultFindologic | OnlineShop\Framework\IndexService\ProductList\DefaultFindologic |
|OnlineShop_Framework_ProductList_DefaultMysql | OnlineShop\Framework\IndexService\ProductList\DefaultMysql |
|OnlineShop_Framework_Impl_Checkout_AbstractStep | OnlineShop\Framework\CheckoutManager\AbstractStep |
|OnlineShop_Framework_Impl_Checkout_DeliveryAddress | OnlineShop\Framework\CheckoutManager\DeliveryAddress |
|OnlineShop_Framework_Impl_Checkout_DeliveryDate | OnlineShop\Framework\CheckoutManager\DeliveryDate |
|OnlineShop_Framework_Impl_CheckoutManager | OnlineShop\Framework\CheckoutManager\CheckoutManager |
|OnlineShop_Framework_Impl_CommitOrderProcessor | OnlineShop\Framework\CheckoutManager\CommitOrderProcessor |
|OnlineShop\Framework\Impl\OrderManager | OnlineShop\Framework\OrderManager\OrderManager |
|OnlineShop\Framework\Impl\OrderManager\AbstractOrderList | OnlineShop\Framework\OrderManager\AbstractOrderList |
|OnlineShop\Framework\Impl\OrderManager\AbstractOrderListItem | OnlineShop\Framework\OrderManager\AbstractOrderListItem |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing | OnlineShop\Framework\OrderManager\Order\Listing |
|OnlineShop\Framework\Impl\OrderManager\Order\Agent | OnlineShop\Framework\OrderManager\Order\Agent |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Item | OnlineShop\Framework\OrderManager\Order\Listing\Item |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractSearch | OnlineShop\Framework\OrderManager\Order\Listing\Filter\AbstractSearch |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\OrderDateTime | OnlineShop\Framework\OrderManager\Order\Listing\Filter\OrderDateTime |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\OrderSearch | OnlineShop\Framework\OrderManager\Order\Listing\Filter\OrderSearch |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Payment | OnlineShop\Framework\OrderManager\Order\Listing\Filter\Payment |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Product | OnlineShop\Framework\OrderManager\Order\Listing\Filter\Product |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\ProductType | OnlineShop\Framework\OrderManager\Order\Listing\Filter\ProductType |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search | OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search\Customer | OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\Customer |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search\CustomerEmail | OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\CustomerEmail |
|OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\Search\PaymentReference | OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search\PaymentReference |
|OnlineShop_Framework_FilterService | OnlineShop\Framework\FilterService\FilterService |
|OnlineShop_Framework_FilterService_AbstractFilterType | OnlineShop\Framework\FilterService\FilterType\AbstractFilterType |
|OnlineShop_Framework_FilterService_Input | OnlineShop\Framework\FilterService\FilterType\Input |
|OnlineShop_Framework_FilterService_MultiSelect | OnlineShop\Framework\FilterService\FilterType\MultiSelect |
|OnlineShop_Framework_FilterService_MultiSelectFromMultiSelect | OnlineShop\Framework\FilterService\FilterType\MultiSelectFromMultiSelect |
|OnlineShop_Framework_FilterService_MultiSelectRelation | OnlineShop\Framework\FilterService\FilterType\MultiSelectRelation |
|OnlineShop_Framework_FilterService_NumberRange | OnlineShop\Framework\FilterService\FilterType\NumberRange |
|OnlineShop_Framework_FilterService_NumberRangeSelection | OnlineShop\Framework\FilterService\FilterType\NumberRangeSelection |
|OnlineShop_Framework_FilterService_ProxyFilter | OnlineShop\Framework\FilterService\FilterType\ProxyFilter |
|OnlineShop_Framework_FilterService_SelectRelation | OnlineShop\Framework\FilterService\FilterType\SelectRelation |
|OnlineShop_Framework_FilterService_SelectFromMultiSelect | OnlineShop\Framework\FilterService\FilterType\SelectFromMultiSelect |
|OnlineShop_Framework_FilterService_SelectCategory | OnlineShop\Framework\FilterService\FilterType\SelectCategory |
|OnlineShop_Framework_FilterService_Select | OnlineShop\Framework\FilterService\FilterType\Select |
|OnlineShop_Framework_FilterService_FilterGroupHelper | OnlineShop\Framework\FilterService\FilterGroupHelper |
|OnlineShop_Framework_FilterService_Helper | OnlineShop\Framework\FilterService\Helper |
|OnlineShop_Framework_FilterService_FactFinder_MultiSelect | OnlineShop\Framework\FilterService\FilterType\FactFinder\MultiSelect |
|OnlineShop_Framework_FilterService_FactFinder_NumberRange | OnlineShop\Framework\FilterService\FilterType\FactFinder\NumberRange |
|OnlineShop_Framework_FilterService_FactFinder_Select | OnlineShop\Framework\FilterService\FilterType\FactFinder\Select |
|OnlineShop_Framework_FilterService_FactFinder_SelectCategory | OnlineShop\Framework\FilterService\FilterType\FactFinder\SelectCategory |
|OnlineShop_Framework_FilterService_ElasticSearch_Input | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\Input |
|OnlineShop_Framework_FilterService_ElasticSearch_MultiSelect | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelect |
|OnlineShop_Framework_FilterService_ElasticSearch_MultiSelectFromMultiSelect | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelectFromMultiSelect |
|OnlineShop_Framework_FilterService_ElasticSearch_MultiSelectRelation | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\MultiSelectRelation |
|OnlineShop_Framework_FilterService_ElasticSearch_NumberRange | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\NumberRange |
|OnlineShop_Framework_FilterService_ElasticSearch_NumberRangeSelection | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\NumberRangeSelection |
|OnlineShop_Framework_FilterService_ElasticSearch_Select | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\Select |
|OnlineShop_Framework_FilterService_ElasticSearch_SelectCategory | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectCategory |
|OnlineShop_Framework_FilterService_ElasticSearch_SelectFromMultiSelect | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectFromMultiSelect |
|OnlineShop_Framework_FilterService_ElasticSearch_SelectRelation | OnlineShop\Framework\FilterService\FilterType\ElasticSearch\SelectRelation |
|OnlineShop_Framework_FilterService_Findologic_MultiSelect | OnlineShop\Framework\FilterService\FilterType\Findologic\MultiSelect |
|OnlineShop_Framework_FilterService_Findologic_MultiSelectRelation | OnlineShop\Framework\FilterService\FilterType\Findologic\MultiSelectRelation |
|OnlineShop_Framework_FilterService_Findologic_NumberRange | OnlineShop\Framework\FilterService\FilterType\Findologic\NumberRange |
|OnlineShop_Framework_FilterService_Findologic_NumberRangeSelection | OnlineShop\Framework\FilterService\FilterType\Findologic\NumberRangeSelection |
|OnlineShop_Framework_FilterService_Findologic_Select | OnlineShop\Framework\FilterService\FilterType\Findologic\Select |
|OnlineShop_Framework_FilterService_Findologic_SelectCategory | OnlineShop\Framework\FilterService\FilterType\Findologic\SelectCategory |
|OnlineShop_Framework_FilterService_Findologic_SelectRelation | OnlineShop\Framework\FilterService\FilterType\Findologic\SelectRelation |

