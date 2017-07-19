# Configuration

**Work in progress!** Currently contains random config examples - will be cleaned up later.

## Environment

```php
<?php

declare(strict_types=1);

namespace AppBundle\Ecommerce;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\SessionEnvironment;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Environment extends SessionEnvironment implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @required
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getDefaultCurrency(): Currency
    {
        $currency = parent::getDefaultCurrency();
        $this->logger->info('ENVENV Resolved default currency as {currency}', ['currency' => $currency->getShortName()]);

        return $currency;
    }
}
```

```yaml
# services.yml
services:
    AppBundle\Ecommerce\Environment:
        class: AppBundle\Ecommerce\Environment
        parent: Pimcore\Bundle\EcommerceFrameworkBundle\SessionEnvironment
```

```yaml
# config.yml

pimcore_ecommerce_framework:
    environment:
        environment_id: AppBundle\Ecommerce\Environment
        options:
            defaultCurrency: USD
```

## Cart manager

```yaml
pimcore_ecommerce_framework:
    cart_manager:
        tenants:
            # _defaults will be automatically merged into every tenant and removed. every other entry starting with _defaults
            # will be removed in the final config, but can be used for YAML inheritance
            _defaults:
                cart:
                    factory_id: SuperSuperFactory
                price_calculator:
                    factory_id: SuperSuperDuperDuperFactory
                    options:
                        foo: bar
                        modificators:
                            - foo
                            - bar

            _defaults_foo:
                price_calculator:
                    options: &defaults_foo_options
                        brr: bum

            default:
                cart:
                    factory_id: SuperDuperFactory
                price_calculator:
                    factory_id: DuperDuperFactory
                    options:
                        foo: baz
                        modificators:
                            - baz

            noShipping:
                price_calculator:
                    options:
                        <<: *defaults_foo_options
                        bar: foo
                        modificators:
                            - bazinga
```


## Product Index

```yaml
# automatically inherit from _defaults section
pimcore_ecommerce_framework:
    product_index:
        tenants:
            _defaults:
                search_attributes:
                    - name
                    - articleNumber
                attributes:
                    name:
                        locale: '%%locale%%'
                        type: varchar(255)
                    code:
                        type: varchar(255)
                    articleNumber:
                        type: varchar(255)
                    equipment:
                        type: bool
                    sorter:
                        type: blob
                        getter: \AppBundle\EcommerceFramework\IndexService\CategorySortingGetter
                        interpreter: \AppBundle\EcommerceFramework\IndexService\CategorySortingInterpreter
                        options:
                            locale: '%%locale%%'

            en:
                config_id: AppBundle\EcommerceFramework\IndexService\TenantConfigEn
                placeholders:
                    '%%locale%%': en
            en_US:
                config_id: AppBundle\EcommerceFramework\IndexService\TenantConfigEnUs
                placeholders:
                    '%%locale%%': en_US
            de:
                config_id: AppBundle\EcommerceFramework\IndexService\TenantConfigDe
                placeholders:
                    '%%locale%%': de
            de_DE:
                config_id: AppBundle\EcommerceFramework\IndexService\TenantConfigDeDe
                placeholders:
                    '%%locale%%': de_DE
```

```yaml
# inherit from custom _defaults_* sections
pimcore_ecommerce_framework:
    product_index:
        tenants:
            _defaults_foo: &_defaults_foo
                search_attributes:
                    - name
                    - articleNumber
                attributes:
                    name:
                        locale: '%%locale%%'
                        type: varchar(255)
                    code:
                        type: varchar(255)
                    articleNumber:
                        type: varchar(255)
                    equipment:
                        type: bool
                    sorter:
                        type: blob
                        getter: \AppBundle\EcommerceFramework\IndexService\CategorySortingGetter
                        interpreter: \AppBundle\EcommerceFramework\IndexService\CategorySortingInterpreter
                        options:
                            locale: '%%locale%%'

            en:
                <<: *_defaults_foo
                config_id: AppBundle\EcommerceFramework\IndexService\TenantConfigEn
                placeholders:
                    '%%locale%%': en
```


## Tracking manager

```yaml
# services.yml
services:
    AppBundle\Ecommerce\Tracking\TrackingManager:
        public: false

    AppBundle\Ecommerce\Tracking\SimpleTracker:
        public: false
        arguments:
            - FOO

    AppBundle\Ecommerce\Tracking\Tracker:
        public: false
        arguments:
            - '@Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder'
            - '@templating.engine.delegating'

    AppBundle\Ecommerce\Tracking\TrackingItemBuilder:
        public: false
        arguments: ['@pimcore.http.request_helper']

```

```yaml
# config.yml
pimcore_ecommerce_framework:
    tracking_manager:
        tracking_manager_id: AppBundle\Ecommerce\Tracking\TrackingManager

        trackers:
            enhanced_ecommerce:
                enabled: true
                item_builder_id: AppBundle\Ecommerce\Tracking\TrackingItemBuilder

            foo:
                id: AppBundle\Ecommerce\Tracking\Tracker
                enabled: false
                options:
                    template_extension: twig

            simple_foo:
                id: AppBundle\Ecommerce\Tracking\SimpleTracker
```
