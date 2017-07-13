# Configuration

**Work in progress!** Currently contains random config examples - will be cleaned up later.

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
