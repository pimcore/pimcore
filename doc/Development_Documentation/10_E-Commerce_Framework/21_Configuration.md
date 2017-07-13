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
