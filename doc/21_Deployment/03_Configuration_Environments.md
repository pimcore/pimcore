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

* <https://github.com/pimcore/demo/tree/11.x/config/pimcore> 
* <https://github.com/pimcore/demo/tree/11.x/var/config>

The environment specific config file has priority over the default config, so if your 
current environment is `dev` Pimcore first checks if e.g. `var/config/image-thumbnails_dev.php`
exists, if not the default config `var/config/image-thumbnails.php` is used. 

## Configuration Storage Locations & Fallbacks
For certain configurations which can be edited in the user interface, 
Pimcore provides the possibility to configure them using various storage types. 
Available storages are (in priority order): 
- Symfony Config (YAML, needs container rebuild)
- Pimcore [`SettingsStore`](../19_Development_Tools_and_Details/42_Settings_Store.md)
- Pimcore Legacy PHP Array Config (deprecated)

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


You can change the write target individually for each type by using environment variables.
The following options are available: 
- `symfony-config` 
  - write configs as Symfony Config as YAML files to the configured storage directory
- `settings-store` 
  - write configs to the `SettingsStore`
- `disabled` 
  - do not allow to edit/write configs at all

#### Storage directory for symfony Config files

The default storage directory for Symfony Config files is `/var/config/...`.

Available options for write targets and directory for Symfony Config files are: 
```yaml
pimcore:
    config_location:
        image_thumbnails:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/image-thumbnails'
        custom_reports:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/custom_reports'
        video_thumbnails:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/video-thumbnails'
        document_types:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/document_types'
        web_to_print:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/web_to_print'
        predefined_properties:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/predefined_properties'
        predefined_asset_metadata:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/predefined_asset_metadata'
        staticroutes:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/staticroutes'
        perspectives:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/perspectives'
        custom_views:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/custom_views'
        data_hub:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/data_hub'
        object_custom_layouts:
            target: 'symfony-config'
            options:
              directory: '/var/www/html/var/config/object_custom_layouts'
```

#### Production environment with `symfony-config`
When using `symfony-config` write target, configs are written to Symfony Config files (`yaml`), which are only getting revalidated in debug mode. So if you're
changing configs in production you won't see any update, because these configs are read only.

If you'd like to allow changes in production, switch to the alternate `settings-store` config storage. 
You can do so by adding the following to your `symfony-config`. e.g.:
```yaml
pimcore:
    config_location:
        custom_reports:
            target: 'settings-store'
```

#### Revalidate existing configuration on production
With `settings-store` target, one can update/change configurations in production environment, which in turn revalidates the generated files e.g. Image Thumbnails, Video thumbnails for subsequent requests.

This is not the case with `symfony-config` write target, as configurations are read-only and deployed from different environment. So we need to explicitly revalidate the generated files either through command or custom script. 

For example, to revalidate image or video thumbnails either run command `pimcore:thumbnails:clear` or call `Asset\Image\Thumbnail\Config::clearTempFiles()` after deploying changes on thumbnail configurations.
