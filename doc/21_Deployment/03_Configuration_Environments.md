# Configuration

## Environment-Specific Configurations
Pimcore supports different configurations for different environments (dev, test, stage, prod, ...) as well as custom 
configurations including a fallback mechanism. 

Pimcore is relying on Symfony's environments, with some extras, however all the essential 
information can be found in the [Symfony Docs](https://symfony.com/doc/current/configuration.html#configuration-environments)

> Note: Require `symfony/dotenv` in your project to use Symfony's DotEnv component to allow you to 
[configure environment variables in `.env` files](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files), 
or (e.g. in *prod* environments) you can configure everything via real environment variables.

In addition to Symfony configurations, Pimcore also supports environment specific configs for: 

* [https://github.com/pimcore/demo/tree/11.x/config/pimcore](https://github.com/pimcore/demo/tree/11.x/config/pimcore) 
* [https://github.com/pimcore/demo/tree/11.x/var/config](https://github.com/pimcore/demo/tree/11.x/var/config)


## Configuration Storage Locations & Fallbacks
For certain configurations which can be edited in the user interface, 
Pimcore provides the possibility to configure them using various storage types. 
Available storages are (in priority order): 
- Symfony Config (YAML, needs container rebuild)
- Pimcore [`SettingsStore`](../19_Development_Tools_and_Details/42_Settings_Store.md)

This feature is currently supported by the following configurations: 
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

The data of configurations are loaded from the container and if there is no data pimcore try to load it from `settings-store`

You can change the read/write target individually for each type by using symfony configuration.
The following options are available: 
- `symfony-config` 
  - write configs as Symfony Config as YAML files to the configured storage directory
- `settings-store` 
  - write configs to the `SettingsStore`
- `disabled` (only write target) 
  - do not allow to edit/write configs at all

#### Storage directory for symfony Config files

The default storage directory for Symfony Config files is defined by `PIMCORE_CONFIGURATION_DIRECTORY`.
If there is no read target set, the config of write target is used.

Available options for write targets and directory & read targets and directory for Symfony Config files are: 
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

and for some specific optional bundles are:

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

#### Production environment with `symfony-config`
When using `symfony-config` write target, configs are written to Symfony Config files (`yaml`), which are only getting revalidated in debug mode. So if you're
changing configs in production you won't see any update, because these configs are read only.

If you'd like to allow changes in production, switch to the alternate `settings-store` config storage. 
You can do so by adding the following to your `symfony-config`. e.g.:
```yaml
pimcore:
    config_location:
        predefined_properties:
            write_target:
                type: 'settings-store'
```

#### Revalidate existing configuration on production
With `settings-store` target, one can update/change configurations in production environment, which in turn revalidates the generated files e.g. Image Thumbnails, Video thumbnails for subsequent requests.

This is not the case with `symfony-config` write target, as configurations are read-only and deployed from different environment. So we need to explicitly revalidate the generated files either through command or custom script. 

For example, to revalidate image or video thumbnails either run command `pimcore:thumbnails:clear` or call `Asset\Image\Thumbnail\Config::clearTempFiles()` after deploying changes on thumbnail configurations.
