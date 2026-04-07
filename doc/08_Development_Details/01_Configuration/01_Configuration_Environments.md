---
title: Configuration Environments
description: Environment-specific configurations and configuration storage locations.
---

# Configuration Environments

Pimcore supports different configurations for different environments (dev, test, stage, prod, etc.) as well
as custom configurations with a fallback mechanism.

Pimcore relies on Symfony's environments with some extras. All essential information is available in the
[Symfony Docs](https://symfony.com/doc/current/configuration.html#configuration-environments).

:::note
Require `symfony/dotenv` in your project to use Symfony's DotEnv component for
[configuring environment variables in `.env` files](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files).
In production environments you can configure everything via real environment variables instead.
:::

In addition to Symfony configurations, Pimcore supports environment-specific configs for:

* [https://github.com/pimcore/demo/tree/2026.x/config/pimcore](https://github.com/pimcore/demo/tree/2026.x/config/pimcore)
* [https://github.com/pimcore/demo/tree/2026.x/var/config](https://github.com/pimcore/demo/tree/2026.x/var/config)


## Configuration Storage Locations and Fallbacks (LocationAwareConfigRepository)

Pimcore's `LocationAwareConfigRepository` manages configuration storage for settings editable through
Pimcore Studio. It supports multiple storage backends with automatic fallback. Available storages in
priority order:

- Symfony Config (YAML, requires container rebuild)
- Pimcore [`SettingsStore`](../../09_Development_Tools/07_Settings_Store.md)

The following configurations currently support this feature:

- Custom reports
- Document types
- Image thumbnails
- Video thumbnails
- Web2Print Settings
- Predefined properties
- Predefined asset metadata
- Static Routes
- Perspectives
- Custom views
- DataObject Custom Layouts

Pimcore loads configuration data from the container first. If no data exists there, Pimcore tries to load it
from `settings-store`.

You can change the read/write target individually for each type using Symfony configuration. The following
options are available:

- `symfony-config`
  - Writes configs as Symfony Config YAML files to the configured storage directory.
- `settings-store`
  - Writes configs to the `SettingsStore`.
- `disabled` (write target only)
  - Prevents editing or writing configs entirely.

#### Storage Directory for Symfony Config Files

The default storage directory for Symfony Config files is defined by `PIMCORE_CONFIGURATION_DIRECTORY`.
If no read target is set, the write target configuration applies.

Available options for write targets, directories, and read targets for Symfony Config files:

```yaml
pimcore:
    config_location:
        image_thumbnails:
            write_target:
                type: 'symfony-config'
                options:
                    directory: '/var/www/html/var/config/image-thumbnails'
        video_thumbnails:
            write_target:
                type: 'disabled'
        document_types:
            write_target:
                type: 'disabled'
        predefined_properties:
            write_target:
                type: 'settings-store'
        predefined_asset_metadata:
            write_target:
                type: 'symfony-config'
                options:
                    directory: '/var/www/html/var/config/predefined_asset_metadata'
        perspectives:
            write_target:
                type: 'symfony-config'
                options:
                    directory: '/var/www/html/var/config/perspectives'
        custom_views:
            write_target:
                type: 'symfony-config'
                options:
                    directory: '/var/www/html/var/config/custom_views'
        object_custom_layouts:
            write_target:
                type: 'symfony-config'
                options:
                    directory: '/var/www/html/var/config/object_custom_layouts'
        select_options:
            write_target:
                type: 'symfony-config'
                options:
                    directory: '/var/www/html/var/config/select_options'
```

For some optional bundles:

```yaml
pimcore_custom_reports:
    config_location:
        custom_reports:
            write_target:
                type: 'symfony-config'

pimcore_static_routes:
    config_location:
        staticroutes:
            write_target:
                type: 'symfony-config'
       ...
```

#### Production Environment with `symfony-config`

When using the `symfony-config` write target, configs are written as Symfony Config YAML files that are only
revalidated in debug mode. Changing configs in production without debug mode has no visible effect because
these configs are read-only.

To allow changes in production, switch to the `settings-store` config storage:

```yaml
pimcore:
    config_location:
        predefined_properties:
            write_target:
                type: 'settings-store'
```

#### Revalidate Existing Configuration in Production

With the `settings-store` target, you can update configurations in the production environment, which
revalidates generated files (e.g. image thumbnails, video thumbnails) for subsequent requests.

This does not apply to the `symfony-config` write target, where configurations are read-only and deployed
from a different environment. You must explicitly revalidate generated files through a command or custom
script.

For example, to revalidate image or video thumbnails, run `pimcore:thumbnails:clear` or call
`Asset\Image\Thumbnail\Config::clearTempFiles()` after deploying thumbnail configuration changes.


#### Troubleshooting: Save Button Is Grayed Out

The save button is disabled (grayed out) under the following conditions:

- The `write_target` is set to `disabled`.
- The environment is production with `symfony-config` target and debug mode is off.
- The `write_target` is `symfony-config` and the YAML file does not exist or is not writable (file-system
  permissions).
- The `read_target` is set but does not match the `write_target`.
