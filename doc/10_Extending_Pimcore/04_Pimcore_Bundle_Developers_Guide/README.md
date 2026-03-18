---
title: Bundle Developer's Guide
description: Build reusable Pimcore bundles with installers, config, and services.
---

# Bundle Developer's Guide

Pimcore uses the Symfony bundle system. A bundle can define services, routes,
controllers, views, and hook into the event system. Core Pimcore functionalities
are themselves implemented as bundles.

For Symfony bundle fundamentals, see the
[Symfony Bundle Documentation](https://symfony.com/doc/current/bundles.html),
including the recommended
[bundle directory structure](https://symfony.com/doc/current/bundles.html#bundle-directory-structure).

## Pimcore Bundles

Pimcore adds the `PimcoreBundleInterface` on top of standard Symfony bundles,
providing installation support and admin UI integration:

- The bundle shows up in `pimcore:bundle:list` with its install/uninstall status
- Install with `pimcore:bundle:install`, uninstall with `pimcore:bundle:uninstall`
  to trigger database setup, migrations, and other installation routines
- Register JS and CSS files for Pimcore Studio and editmode

See [Pimcore Bundles](./01_Pimcore_Bundles/README.md) for the interface details
and composer configuration.

## Recommended Reading Order

1. **[Pimcore Bundles](./01_Pimcore_Bundles/README.md)** -
   `PimcoreBundleInterface`, composer setup, and version management
2. **[Bundle Collection](./04_Bundle_Collection.md)** -
   register bundles with priorities, environments, and dependencies
3. **[Loading Service Definitions](./02_Loading_Service_Definitions.md)** -
   create DI extensions to load service configs from your bundle
4. **[Auto Loading Config and Routing](./03_Auto_Loading_Config_and_Routing.md)** -
   automatic config and routing loading from bundle directories
5. **[Installers](./01_Pimcore_Bundles/01_Installers.md)** -
   installation, uninstallation, and database migrations

## Common Tasks

### Service Configuration

Load custom services from your bundle by creating a DI extension.
See the [Symfony Extensions Documentation](https://symfony.com/doc/current/bundles/extension.html)
and the Pimcore-specific guide at [Loading Service Definitions](./02_Loading_Service_Definitions.md).

### Auto-Loading Config and Routing Definitions

Bundles provide config and routing definitions in `config/pimcore` (or `Resources/config/pimcore`)
for automatic loading. See
[Auto Loading Config and Routing](./03_Auto_Loading_Config_and_Routing.md).

### i18n / Translations

Store translation files in your bundle's `Resources/translations/` directory
in the format `locale.loader` (or `domain.locale.loader` for a specific translation domain).

Examples: `admin.en.yml`, `messages.en.yml`

See the Symfony
[Translation Component Documentation](https://symfony.com/doc/current/translation.html#translation-resource-file-names-and-locations)
for all supported locations and formats.

#### Translation Domains

Register custom translation domains in your configuration:

```yaml
pimcore:
    translations:
        domains:
            - site_1
            - site_2
```

Translations stored in a dedicated domain table (e.g. `translations_DOMAIN`)
are then used by the Pimcore translation service.

### Security / Authentication

Use the [Symfony Security Component](https://symfony.com/doc/current/security.html)
by auto-loading security configuration via the
[config auto-loading mechanism](./03_Auto_Loading_Config_and_Routing.md).
Define security rules in a dedicated `security.yaml` imported from your bundle's
`config.yaml`.

For further details, see
[Security](../../09_Development_Tools/03_Security_and_Authentication/README.md).

### Events

Hook into core functions by attaching to Pimcore events. Define event listener services
in your bundle and register them with the Symfony EventDispatcher.

- [Symfony Event Dispatcher](https://symfony.com/doc/current/event_dispatcher.html) -
  creating and registering event listeners
- [Pimcore Events](../01_Events/README.md) - available Pimcore events

### Local Storage for Your Bundle

For temporary data removed on Symfony cache clear, use the cache directory:

- `$kernel->getCacheDir()`
- `%kernel.cache_dir%` parameter

For persistent storage, create a unique directory in `PIMCORE_PRIVATE_VAR`,
e.g. `var/bundles/YourBundleName`.
