# Ecommerce Framework Config/Signature Changes

The changes implemented in the pull request [pimcore/pimcore#1783](https://github.com/pimcore/pimcore/pull/1783) fundamentally
changed how framework components are configured and how the components are built from configuration. The changes focused
on the following points:

* Migrate E-Commerce Framework configuration to a Symfony Config tree exposed by the bundle
* Refactor components to be wired together via DI instead of relying on the Factory and instead of parsing/handling config
  inside each component
* Components are not aware of configurations or tenants anymore. The bundle extension/configuration takes care of handling
  config and transforming the config to service definitions. Examples:
  * instead of calling `Factory::getInstance()->getVoucherService()` somewhere inside the `OrderManager`, the order manager
    requests an `IVoucherService` constructor argument
  * instead of building payment providers from a config instance, the `PaymentManager` just gets a set of providers it can
    access by name
    
The changes mentioned above result in configuration/dependency management logic being removed from components, thus making
components only responsible for their business logic. This might make construction of a component more complex (as it adds)
more dependencies to an object, but this complexity can be handled by the service container/bundle extension instead of
the component. As end result, the changes result in more predictable services which are easier to test as all dependencies
are well-known.

This pages mainly focuses on the code/signature changes of single components. Parallel to the code changes, the configuration
was migrated from the `EcommerceFrameworkConfig.php` file to a Symfony Config tree which can be configured in any of the
loaded config files (e.g. `config.yml`) and which includes validation and normalization of config values as soon as the 
container is built (which gives you instant feedback on missing/invalid values). Regarding configuration, please see:

* [Configuration](../../10_E-Commerce_Framework/04_Configuration)
* Each component's documentation section in [E-Commerce Framework](./../../10_E-Commerce_Framework)
* The annotated [configuration](https://github.com/pimcore/demo-ecommerce/blob/master/src/AppBundle/Resources/config/pimcore/ecommerce/ecommerce-config.yml)
  from the `demo-ecommerce` install profile
