# Configuration Environments

Pimcore supports different configurations for different environments (dev, test, stage, prod, ...) as well as custom 
configurations including a fallback mechanism. 

Pimcore is relying on Symfony's environments, with some extras, however all the essential 
information can be found in the [Symfony Docs](https://symfony.com/doc/current/configuration.html#configuration-environments)

## Supported Configurations

In addition to Symfony configurations, Pimcore also supports environment specific configs for: 

* <https://github.com/pimcore/demo/tree/master/config/pimcore> 
* <https://github.com/pimcore/demo/tree/master/var/config>

The environment specific config file has priority over the default config, so if your 
current environment is `dev` Pimcore first checks if e.g. `var/config/image-thumbnails_dev.php`
exists, if not the default config `var/config/image-thumbnails.php` is used. 

