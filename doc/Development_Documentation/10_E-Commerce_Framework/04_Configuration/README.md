# Configuration

The E-Commerce Framework is implemented as semantic bundle configuration which means that you can configure the framework
in any of the loaded config files (e.g. `app/config/config.yml` or `src/AppBundle/Resources/config/pimcore/config.yml`) by
adding configuration to the `pimcore_ecommerce_framework` node.

The configuration is built upon the [Symfony Config Component](https://symfony.com/doc/3.4/components/config.html) and
defines a well-known configuration tree which is validated against a defined configuration structure. While configuring
the framework you'll get instant feedback on configuration errors. As Pimcore defines a standard configuration for the 
E-Commerce Framework, your custom configuration will be merged into the standard configuration. You can dump the final
(merged, validated and normalized) configuration at any time with the following command:

    $ bin/console debug:config pimcore_ecommerce_framework 
    
A reference of available configuration entries can be found on [PimcoreEcommerceFrameworkBundle Configuration Reference](./01_PimcoreEcommerceFrameworkBundle_Configuration_Reference.md).

As the E-Commerce Framework makes heavy use of Symfony service definitions and you'll need to reference IDs of custom
services in the configuration it's advised that you are comfortable to work with [Symfony's Service Container](https://symfony.com/doc/3.4/service_container.html#creating-configuring-services-in-the-container]). 

## Tenant support and `_defaults` sections

As certain components of the E-Commerce Framework support different tenants, Pimcore adds a way to specify settings
which are common to all tenants in a special tenant named `_defaults`. If such a tenant exists, its values will be merged
into all other tenants and the `_defaults` tenant will be removed afterwards. The logic described below applies to all 
components supporting tenants.

As example, a simple `cart_manager` configuration section which defines a `default` and a `noShipping` tenant:  

```yaml
pimcore_ecommerce_framework:
    cart_manager:
        tenants:
            # _defaults will be automatically merged into every tenant and removed afterwards
            # the result will be a default and a noShipping
            _defaults:
                cart:
                    factory_id: CartFactoryId
                price_calculator:
                    factory_id: PriceCalculatorFactoryId
                    factory_options:
                        foo: bar

            default:
                price_calculator:
                    factory_options:
                        baz: 1234

            noShipping:
                price_calculator:
                    factory_options:
                        baz: 4321
```

In addition to the `_defaults` tenant, you can define additional tenants starting with `_defaults` to make use
of [YAML inheritance](https://learnxinyminutes.com/docs/yaml/).

```yaml
pimcore_ecommerce_framework:
    cart_manager:
        tenants:
            _defaults_foo:
                price_calculator:
                    factory_options: &defaults_foo_options # define an anchor
                        class: MyCommonCalculatorClass
                        
            default:
                price_calculator:
                    factory_options:
                        <<: *defaults_foo_options # import anchor
            
            anotherOne:
                price_calculator:
                    factory_options:
                        <<: *defaults_foo_options # import anchor
```

Of course you can still use the PHP config file format by importing a PHP config file from your `config.yml` to be
completely free how to merge common configuration entries.

## Service IDs and tenant specifics

When configuring tenant specific services, there are multiple configuration entries demanding a service ID as configuration
value. This means, the system expects the configured value to be available as service definition on the container. You can
read through the service definitions defined in [PimcoreEcommerceFrameworkBundle](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Resources/config)
to get an insight of default e-commerce services.

As an example let's take a look at a price system configuration:

```yaml
# when loading the "default" price system, use a service named "App\Ecommerce\PriceSystem\CustomPriceSystem" 
pimcore_ecommerce_framework:
    price_systems:
        default:
            # price system defined and configured as container service
            id: App\Ecommerce\PriceSystem\CustomPriceSystem
```

The configuration above expects something like this configured as service:

```yaml
services:
    App\Ecommerce\PriceSystem\CustomPriceSystem:
        arguments:
            - 'foo'
```

The "default" price system is just an alias to your existing service and when loading that price system, your service will
be directly loaded. Getting the "default" price system from the ecommerce framework factory will result in the same instance
as getting `App\Ecommerce\PriceSystem\CustomPriceSystem` directly from the container.


### Service IDs in tenant context

Now let's take a look at another example handling tenants:

```yaml
pimcore_ecommerce_framework:
    order_manager:
        tenants:
            default:
                order_manager_id: Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager
                options:
                    parent_order_folder: /orders/default/%%Y/%%m/%%d
            b2b:
                order_manager_id: Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager
                options:
                    parent_order_folder: /orders/b2b/%%Y/%%m/%%d 
```

As you can see we define 2 tenants `default` and `b2b` with the same order manager ID but with different options for
each tenant. In this case it's not possible to just alias the default or the b2b order manager to the given service ID as
we need multiple order manager instances based on this service definition. In this case the framework takes the configured
service ID as *template* to configure 2 independent child services by utilizing the configured service id as [parent service](https://symfony.com/doc/3.4/service_container/parent_services.html).

This means, that when you request the `Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager` service from the
container, it would be neither the default nor the b2b tenant. In fact, getting that service directly wouldn't work anyways
as it is missing dependencies. If you take a look at the [service definition](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Resources/config/order_manager.yml)
you can see that the definition is missing the `OrderAgentFactoryInterface` argument which will be resolved and set for each
tenant specific order manager.

In short, when configuring multiple tenant specific service to the same service id the framework will:

1) create a new child definition from the configured service
2) set tenant specific arguments (as the options above) to the tenant specific child service
3) register the child service under a another ID which can be resolved by the factory

To keep your services clean and give the service container more optimization (and performance) possibilities you should 
define your parent services as `private` (see order manager definition in the linked config above) as this will result
in the parent definition being removed from the compiled container. 

This child definition pattern applies to nearly all service IDs configured inside tenants with the exception of reusable 
services (e.g. getters and interpreters in the product index configuration) which do not have an internal state and therefore
can be reused throughout multiple tenants.
