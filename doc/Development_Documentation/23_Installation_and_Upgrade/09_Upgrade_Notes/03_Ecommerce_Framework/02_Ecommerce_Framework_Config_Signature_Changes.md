# E-Commerce Framework Config/Signature Changes

The changes implemented in the pull request [pimcore/pimcore#1783](https://github.com/pimcore/pimcore/pull/1783) fundamentally
changed how framework components are configured and how the components are built from configuration. The changes focused
on the following points:

* Migrate E-Commerce Framework configuration to a Symfony Config tree exposed by the bundle
* Refactor components to be wired together via DI and Symfony's service container instead of relying on the Factory and instead
  of parsing/handling config inside each component
* Components are not aware of configurations or tenants anymore. The bundle extension/configuration takes care of handling
  config and transforming the config to service definitions. Examples:
  * instead of calling `Factory::getInstance()->getVoucherService()` somewhere inside the `OrderManager`, the order manager
    requests an `IVoucherService` constructor argument
  * instead of building payment providers from a config instance, the `PaymentManager` just gets a set of providers it can
    access by name
    
The changes mentioned above result in configuration/dependency management logic being removed from components, thus making
components only responsible for their business logic. This might make construction of a component more complex (as it adds
more dependencies to an object), but this complexity can be handled by the service container/bundle extension instead of
the component. As end result, the changes result in more predictable services which are easier to test as all dependencies
are well-known.

This pages mainly focuses on the code/signature changes of single components. In addition to the code changes, the configuration
was migrated from the `EcommerceFrameworkConfig.php` file to a Symfony Config tree which can be configured in any of the
loaded config files (e.g. `config.yml`) and which includes validation and normalization of config values as soon as the 
container is built (which gives you instant feedback on missing/invalid values). Regarding configuration, please see:

* [Configuration](../../../10_E-Commerce_Framework/04_Configuration)
* Each component's documentation section in [E-Commerce Framework](./../../../10_E-Commerce_Framework)
* The annotated [configuration](https://github.com/pimcore/demo-ecommerce/blob/master/src/AppBundle/Resources/config/pimcore/ecommerce/ecommerce-config.yml)
  from the `demo-ecommerce` install profile
  
Most changes to signatures are some sort of:

* Constructor now gets a list of dependent services/values instead of a `Config` object
* If `options` can be passed in a config section, they're passed as `$options` array to the constructor and validated
  with Symfony's [OptionsResolver](http://symfony.com/doc/current/components/options_resolver.html) component in most cases. Most implementations specifc a custom method to configure
  the options resolver, so you can easily adapt the resolver configuration in child classes.
* If a component relies on tenant aware components, a component specific `locator` is passed which is able to resolve the
  desired component at runtime. E.g. a `CheckoutManager` has a dependency on an `IOrderManagerLocator` which exposes a
  `getOrderManager()` method to fetch the order manager matching the current tenant. The locator logic is needed as different
  components can have different tenants and we can't wire components together statically. However using component specific
  locators is a good compromise between not fetching services from a global object (`Factory::getInstance()->getOrderManager()`)
  to keep the code clean and testable and still keeping the flexibility of components being able to define their own set
  of tenants without having to specify all tenants for all components.
* Static calls to components were removed as much as possible - everything which can act as a service was now changed to
  be a service built by the service container. This brings the following advantages:
  * The developer has full control over the service definition, e.g. a price system can now depend on another service (e.g.
    an API client for a pricing API).
  * Services can be lazy loaded and their definitions (parsing config, wiring services together )are only built when building
    the container - resulting in less run-time logic and more performance.
  * Examples: price systems or index service getters are now defined as service and you have full control over how the 
    service is built.
    

## Changes that most likely need to be addressed during migration of E-Commerce Framework

- All `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IConfig` implementations
  - changes: 
    - `public function getAttributeConfig()` to `public function getAttributes(): array`
    - `public function getSearchAttributeConfig()` to `public function getSearchAttributes(): array`
  - added `public function setTenantWorker(IWorker $tenantWorker);` which is used primarily by the framework. 
  

- All `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\AbstractConfig` sub classes
  - changes
    - `public function __construct(string $tenantName, array $attributes, array $searchAttributes, array $filterTypes, array $options = [])`
    - `public function getAttributeConfig()` to `public function getAttributes(): array`
    - `public function getSearchAttributeConfig()` to `public function getSearchAttributes(): array`
  
- All `IGetter` implementations
  - getters are now registered as services and are no static classes anymore
  - `public function get($object, $config = null)` is not static anymore and does not get a` Zend_Config` or `Config` anymore
    but an simple array instead (as defined in `getter_options` in the configuration). Standard getters validate the config
    through Symfony's `OptionsResolver` component 
  
- All `IInterpreter` implementations
  - same changes as for getters - config is read from `interpreter_options` from configuration
  -  `public function interpret($value, $config = null)` - not static anymore and does not get a `Zend_Config` or `Config`
     anymore but an simple array instead 

- The `AttributeAvailabilitySystem` was renamed to `AvailabilitySystem` as is does not handle any attributes. In addition,
  it now returns an `Availability` instead of the product to match the interface definition.

- All `AbstractPriceSystem` sub classes
  - changed 
    - `public function __construct(IPricingManager $pricingManager)`
    - `Factory::getInstance()->getPricingManager()` to `$this->pricingManager`
  
- All `AttributePriceSystem` sub classes
  - changed
    - `public function __construct(IPricingManager $pricingManager, IEnvironment $environment, array $options = [])`
    - `Factory::getInstance()->getEnvironment()` to `$this->environment` 
	
- All `ICartPriceModificator` implementations like e.g. Shipping
  - changed constructor to `public function __construct(array $options = [])` and gets no 
    `Zend_Config` or `Config` any more but an simple array.
	
- All `ICheckoutStep` implementations like `AbstractStep`
  - changed constructor to `public function __construct(ICart $cart, array $options = [])` and gets no 
    `Zend_Config` or `Config` any more but an simple array.
	
- All `ICommitOrderProcessor` implementations
  - removed `public function setConfirmationMail($confirmationMail);`. Confirmation mail and other options can now be
    set via options (constructor) or by adding needed calls to your service definition.
  - in implementations changed: 
	- changed constructor to `public function __construct(IOrderManagerLocator $orderManagers, array $options = [])` 
    - `Factory::getInstance()->getOrderManager()` changed to `$this->orderManagers->getOrderManager()`

- All `AbstractFilterType` implementations
  - changed: 
    - `public function __construct(TranslatorInterface $translator, EngineInterface $templatingEngine, string  $template, array $options = [])`
    - `protected function render($template, array $parameters = [])`
  
  - added: 
    - `protected function getTemplate(AbstractFilterDefinitionType $filterDefinition)` which should be used for getting the template. 
	
- `CheckoutManager` does not support CheckoutManager names anymore, use tenants instead. 

- `OrderAgent`
  - changed: 
	- `public function __construct(Order $order, IEnvironment $environment, IPaymentManager $paymentManager)`
    - `$this->factory->getEnvironment()` to `$this->environment`
    - `$this->factory->getPaymentManager()` to `$this->paymentManager`

- `Factory`
  - removed: 
    - `public static function resetInstance($keepEnvironment = true)`. Is not necessary anymore.
	- `public function getConfig()`


## Further changes that should be most likely internal only but might need to be considered if subclassing affected classes: 

- `SessionCart`
  - changed `protected function getSession()` to `protected static function getSessionBag(): AttributeBagInterface` 

- `ICartPriceCalculator`
  - changed `__construct(IEnvironment $environment, ICart $cart, array $modificatorConfig = [])`

- `CartPriceCalculator`
  - changed: 
	- `__construct(IEnvironment $environment, ICart $cart, array $modificatorConfig = [])
	`- `Factory::getInstance()->getEnvironment()` changed to `$this->environment`
  
- `ICartManager`
  - changed: `public function createCart(array $params);`
  
- `MultiCartManager`
  - changed: 
	- `public function __construct(IEnvironment $environment, ICartFactory $cartFactory, ICartPriceCalculatorFactory $cartPriceCalculatorFactory, IOrderManagerLocator $orderManagers, LoggerInterface $logger)`
	- `public function createCart(array $params)`
	- `public function getCarts(): array`
	- `public function getCartPriceCalculator(ICart $cart): ICartPriceCalculator`
    - `Factory::getInstance()->getEnvironment()` to `$this->environment`
    - `Factory::getInstance()->getOrderManager()` to `$this->orderManagers->getOrderManager()`

  - removed: 
    - `public function getCartPriceCalcuator(ICart $cart): ICartPriceCalculator`
    
- `CheckoutManager`
  - changed: 
	- `public function __construct(ICart $cart, IEnvironment $environment, IOrderManagerLocator $orderManagers, ICommitOrderProcessorLocator $commitOrderProcessors, array $checkoutSteps, IPayment $paymentProvider = null)`
	- step initialization logic was moved from the constructor to multiple smaller methods which can be adapted if needed
	- payment is injected from outside and does not need to be resolved from config
	- the checkout manager now does not know about a confirmation mail - the commit order processor is now directly configured
	  instead as the config supports options which are directly passed to the processor's constructor
    - `Factory::getInstance()->getEnvironment()` to `$this->environment`
	- `Factory::getInstance()->getOrderManager()` to `$this->orderManagers->getOrderManager()`
	- `$this->getCommitOrderProcessor()` to `$this->commitOrderProcessors->getCommitOrderProcessor()`

  - removed `protected function getCommitOrderProcessor()`, use `$this->commitOrderProcessors->getCommitOrderProcessor()` instead
  
  
- `IEnvironment`
  - changed `public function getCustomItem($key, $defaultValue = null);`
  
- `Environment`
  - changed: 
    - `public function __construct(Locale $localeService, array $options = [])`
    - `public function getCustomItem($key, $defaultValue = null)`
    - Now without session storage implementation. Use `SessionEnvironment` instead which is also used by the framework by default. 
  
- `Factory`
  - changed: 
    - `public function __construct(ContainerInterface $container, ICartManagerLocator $cartManagers, IOrderManagerLocator $orderManagers, IPriceSystemLocator $priceSystems, IAvailabilitySystemLocator $availabilitySystems, ICheckoutManagerFactoryLocator $checkoutManagerFactories, ICommitOrderProcessorLocator $commitOrderProcessors, IFilterServiceLocator $filterServices)`
    - `public function getEnvironment(): IEnvironment`
    - `public function getCartManager(string $tenant = null): ICartManager`
    - `public function getOrderManager(string $tenant = null): IOrderManager`
    - `public function getPricingManager(): IPricingManager`
    - `public function getPriceSystem(string $name = null): IPriceSystem`
    - `public function getAvailabilitySystem(string $name = null): IAvailabilitySystem`
    - `public function getCheckoutManager(ICart $cart, string $tenant = null): ICheckoutManager`
    - `public function getCommitOrderProcessor(string $tenant = null): ICommitOrderProcessor`
    - `public function getPaymentManager(): IPaymentManager`
    - `public function getIndexService(): IndexService`
    - `public function getFilterService(string $tenant = null): FilterService`
    - `public function getAllTenants(): array`
    - `public function getOfferToolService(): IService`
    - `public function getVoucherService(): IVoucherService`
    - `public function getTokenManager(AbstractVoucherTokenType $configuration): ITokenManager`
    - `public function getTrackingManager(): ITrackingManager`
    - `public function getEnvironment(): IEnvironment`
    - `public static function getInstance(): self`
  
- `FilterGroupHelper`
  - changed: 
    - `protected function getColumnTypeForColumnGroup($columnGroup)` not static any more
    - `public function getGroupByValuesForFilterGroup($columnGroup, IProductList $productList, $field)` not static anymore
  
- `FilterService`
  - changed: 
    - `public function __construct(FilterGroupHelper $filterGroupHelper, array $filterTypes)`
    - `public function getFilterGroupHelper(): FilterGroupHelper`
    - `public function getFilterType(string $name): AbstractFilterType`
  
  
- All implementations of `IExtendedGetter` 
  - `public function get($object, $config = null, $subObjectId = null, IConfig $tenantConfig = null);` not static anymore. 
  
  
- `IndexService`
  - changed:
    -  `public function __construct(IEnvironment $environment, array $tenantWorkers = [], string $defaultTenant = 'default')`
    - `public function getTenantWorker(string $tenant): IWorker`
    - `public function getGeneralSearchColumns(string $tenant = null)`
    - `public function getGeneralSearchAttributes(string $tenant = null): array`
    - `public function getIndexAttributes(bool $considerHideInFieldList = false, string $tenant = null): array`
    - `public function getAllFilterGroups(string $tenant = null): array`
    - `public function getIndexAttributesByFilterGroup($filterType, string $tenant = null): array`
    - `public function getCurrentTenantWorker(): IWorker`
  
  
- `AbstractWorker` 
  - changed `public function __construct(IConfig $tenantConfig, Connection $db)`
  
- `DefaultElasticSearch`   
  - changed `public function __construct(IElasticSearchConfig $tenantConfig, Connection $db)`
  
- `DefaultFactFinder`   
  - changed `public function __construct(IFactFinderConfig $tenantConfig, Connection $db)`
  
- `DefaultFindologic` 
  - changed `public function __construct(IFindologicConfig $tenantConfig, Connection $db)`
  
- `DefaultMysql` 
  - changed `public function __construct(IMysqlConfig $tenantConfig, Connection $db)`
  
- `OptimizedMysql` 
  - changed `public function __construct(OptimizedMysqlConfig $tenantConfig, Connection $db)`


- `OfferTool\DefaultService`   
  - changed `public function __construct(string $offerClass, string $offerItemClass, string $parentFolderPath)`
  
  
- `IOrderAgent`
  - removed `public function __construct(Factory $factory, Order $order);`
  
  
- `OrderManager`
  - changed: 
    - `public function __construct(IEnvironment $environment, IOrderAgentFactory $orderAgentFactory, IVoucherService $voucherService, array $options = [])`
    - `Factory::getInstance()->getVoucherService()` to `$this->voucherService`
    - `Factory::getInstance()->getEnvironment()` to `$this->environment`
  
- `IPaymentManager`
  - changed `public function getProvider(string $name): IPayment;`
  
- `PaymentManager`
  - changed `public function __construct(PsrContainerInterface $providers)`
  - changed `public function getProvider(string $name): IPayment;` 
  - throws an exception if a provider is not known
  
- `IPayment`
  - removed constructor
  - all payment providers now get an options array as constructor argument and validate their options through Symfony's
    `OptionsResolver` component. Option names were normalized to `snake_case` througout all providers (to be considered
    when migrating config). 
  - payment providers supporting multiple endpoints (e.g. sandbox, live) specific a `mode` option which can be set to 
    the desired endpoint configuration (e.g. `live`).
  - payment providers are now registered as service - you can define additional constructor arguments or calls which should
    be called at construction through the provider's service definition (e.g. the form factory on datatrans)
  
- `Datatrans`, `QPay` 
  - changed `public function __construct(array $options, FormFactoryInterface $formFactory)` and gets no 
    `Zend_Config` or `Config` any more but an simple array. 
  
- `Klarna`, `PayPal`
  - changed `public function __construct(array $options)` and gets no 
    `Zend_Config` or `Config` any more but an simple array. 
  
- `WirecardSeamless`
  - changed `public function __construct(array $options, EngineInterface $templatingEngine, SessionInterface $session)` and gets no 
    `Zend_Config` or `Config` any more but an simple array. 
    
- `IPricingManager`
  - added 
    - `public function getActionMapping(): array;`
    - `public function getConditionMapping(): array;`

- `PricingManager`
  - changed `public function __construct(array $conditionMapping, array $actionMapping, SessionInterface $session, array $options = [])`
  - added 
    - `public function isEnabled(): bool`
    - `public function getActionMapping(): array;`
    - `public function getConditionMapping(): array;`

- `ITracker`
  - removed 
    - constructor
    - `public function getTrackingItemBuilder();`
    - `public function includeDependencies();`
  - trackers now get an array of options which can be used to specify the template prefix and extension if needed. this 
    can be used to specify own tracker templates from another bundle or with another extension (e.g. twig)
  - `getViewScriptPrefix()` and `getViewScript()` are now available as `$this->templatePrefix` and `getTemplatePath()`
  
- `TrackingManager`
  - changed
    - `public function __construct(array $trackers = [])`
    - `public function registerTracker(ITracker $tracker)`
    - `public function getTrackers(): array`
  
  - removed `public function ensureDependencies()` - the dependency logic was moven to the enahanced ecommerce tracker
 
- `VoucherService\IVoucherService`
  - removed constructor
 
- `VoucherService\DefaultService`
  - changed `public function __construct(array $options = [])` and gets no 
    `Zend_Config` or `Config` any more but an simple array. 
  

  
