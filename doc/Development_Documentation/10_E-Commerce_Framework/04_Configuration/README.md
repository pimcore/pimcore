# Configuration

The E-Commerce Framework is implemented as semantic bundle configuration which means that you can configure the framework
in any of the loaded config files (e.g. `app/config/config.yml` or `src/AppBundle/Resources/config/pimcore/config.yml`) by
adding configuration to the `pimcore_ecommerce_framework` node.

The configuration is built upon the [Symfony Config Component](https://symfony.com/doc/current/components/config.html) and
defines a well-known configuration tree which is validated against a defined configuration structure. While configuring
the framework you'll get instant feedback on configuration errors. As Pimcore defines a standard configuration for the 
E-Commerce Framework, your custom configuration will be merged into the standard configuration. You can dump the final
(merged, validated and normalized) configuration at any time with the following command:

    $ bin/console debug:config pimcore_ecommerce_framework 

As the E-Commerce Framework makes heavy use of Symfony service definitions and you'll need to 
reference IDs of custom defined services in the configuration it's advised that you are comfortable with (Symfony's Service Container)[https://symfony.com/doc/current/service_container.html#creating-configuring-services-in-the-container]. 

## Tenant support and `_defaults` sections

As certain components of the E-Commerce Framework support different tenants, Pimcore adds a way of specifying settings
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
                    options:
                        foo: bar

            default:
                price_calculator:
                    options:
                        baz: 1234

            noShipping:
                price_calculator:
                    options:
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
                    options: &defaults_foo_options # define an anchor
                        class: MyCommonCalculatorClass
                        
            default:
                price_calculator:
                    options:
                        <<: *defaults_foo_options # import anchor
            
            anotherOne:
                price_calculator:
                    options:
                        <<: *defaults_foo_options # import anchor
```

Of course you can still use the PHP config file format by importing a PHP config file from your `config.yml` to be
completely free how to merge common configuration entries.
