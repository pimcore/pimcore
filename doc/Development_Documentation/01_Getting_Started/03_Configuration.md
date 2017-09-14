# Configuration

Pimcore's configuration can be found in several places:

* Configurations in `var/config/*.php` are written from the admin interface. For example the `system.php` file contains
  the settings written from [System Settings](../18_Tools_and_Features/25_System_Settings.md)
* The Symfony configuration tree (mainly distributed throughout `*.yml` files) contains all Symfony related configuration.
  This configuration is partially populated from settings coming from other config files (e.g. the database credentials
  stored in `system.php` are used to configure the [Doctrine Bundle](http://symfony.com/doc/master/bundles/DoctrineBundle/configuration.html#configuration-overview)
  configuration.
* A set of `PIMCORE_*` constants which are used to resolve various filesystem paths


## Symfony Configuration

Many aspects of Pimcore can be configured through the [Symfony Config](https://symfony.com/doc/current/bundles/configuration.html)
tree defined under the `pimcore` extension. These values can be changed through config files in `app/config` (e.g. `app/config/config.yml)`).

Pimcore additionally includes a set of standard configuration files which (in contrast to a standard Symfony project) are
not located in `app/config` but in the [PimcoreCoreBundle](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Bundle/CoreBundle/Resources/config/pimcore).
This gives us the possibility to ship and update default configurations without affecting project code in `app/`. See
[Auto loading config and routing definitions](../20_Extending_Pimcore/13_Bundle_Developers_Guide/03_Auto_Loading_Config_And_Routing_Definitions.md)
for details how this works).

Standard configs will be merged with your custom config in `app/config` to build the final config tree. You can debug the
values stored in the tree through the following command:

```bash
# this is a core Symfony command and works for every bundle, just omit the
# "pimcore" argument to get a list of all bundles
$ bin/console debug:config pimcore
```

In addition, you can print a reference of valid configuration sections with the following command:

```bash
$ bin/console config:dump-reference pimcore
```   


## Pimcore constants

Pimcore uses several constants for locating certain directories like logging, asses, versions etc. These constants are
defined in [`/pimcore/config/constants.php`](https://github.com/pimcore/pimcore/blob/master/pimcore/config/constants.php).

If you need to overwrite these constants, e.g. for using a special directory for assets or versions at an object storage
at AWS S3 you have multiple ways to do so:

* Create a file in `/app/constants.php` setting the constants you need. Pimcore will skip setting any constants which are 
  already defined.
* Define an environment variable named after the constant. When defining a constant, Pimcore will look if an env variable
  with the same name is defined and use that instead of the default value.
* Define an environment variable in a `/.env` file which will be automatically loaded through the [DotEnv](https://symfony.com/doc/current/components/dotenv.html)
  component if it exists. Environment variables defined here will have the same effect as "real" environment variables.


An example file (`constants.example.php`) is shipped with Pimcore installation and could look like: 

```php
<?php

// to use this file you have to rename it to constants.php
// you can use this file to overwrite the constants defined in /pimcore/config/constants.php

define("PIMCORE_ASSET_DIRECTORY", "/custom/path/to/assets");
define("PIMCORE_TEMPORARY_DIRECTORY", "/my/tmp/path");

```

Please see [`/pimcore/config/constants.php`](https://github.com/pimcore/pimcore/blob/master/pimcore/config/constants.php)
for a list of defined constants.


### The PIMCORE_PROJECT_ROOT constant

There is one special constant `PIMCORE_PROJECT_ROOT` which is used to resolve the root directory (see [Directory Structure](./02_Directory_Structure.md))
of the application.
In constrast to the remaining constants, this constant is not defined in `constants.php` as it is already needed to resolve
the path to the `constants.php` file itself. Instead, it is defined in Pimcore's entry points such as `app.php` in the following
way:

```php
<?php

if (!defined('PIMCORE_PROJECT_ROOT')) {
    define(
        'PIMCORE_PROJECT_ROOT',
        getenv('PIMCORE_PROJECT_ROOT')
            ?: getenv('REDIRECT_PIMCORE_PROJECT_ROOT')
            ?: realpath(__DIR__ . '/..')
    );
}
```

This means, you can influence the project root through an env variable (or by defining a constant before loading the entry
point) if needed and Pimcore will fall back to its standard value if not defined. If you use Pimcore's standard directory
layout as shipped in the zip file, you don't have to set anything, but if you need some kind of special setup you have full
control over the used paths here.

In contrast to the other constants, `PIMCORE_PROJECT_ROOT` can not be set via `.env` Pimcore doesn't know where to look
for a `.env` file at this point.
