# Configuration

Pimcore's configuration can be found in several places:

* Configurations in `var/config/*.(php|yml)` are written from the admin interface. For example the `system.yml` file contains the settings from [System Settings](../18_Tools_and_Features/25_System_Settings.md)
* The Symfony configuration tree (mainly distributed throughout `*.yml` files) contains all Symfony as well as most of the Pimcore related configurations.
* A set of `PIMCORE_*` constants which are used to resolve various filesystem paths


## Symfony Configuration

Many aspects of Pimcore can be configured through the [Symfony Config](https://symfony.com/doc/3.4/bundles/configuration.html)
tree defined under the `pimcore` and `pimcore_admin` extension. These values can be changed through config files in `app/config` (e.g. `app/config/config.yml)`).

Pimcore additionally includes a set of standard configuration files which, in contrast to a standard Symfony project, are
not located in `app/config`, but in the [PimcoreCoreBundle](https://github.com/pimcore/pimcore/tree/master/bundles/CoreBundle/Resources/config/pimcore).
This allows us to ship and update default configurations without affecting project code in `app/`. See
[Auto loading config and routing definitions](../20_Extending_Pimcore/13_Bundle_Developers_Guide/03_Auto_Loading_Config_And_Routing_Definitions.md)
for details how this works.

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

Pimcore uses several constants for locating certain directories like logging, assets, versions etc. These constants are
defined in [`lib/Bootstrap.php`](https://github.com/pimcore/pimcore/blob/master/lib/Bootstrap.php).

If you need to overwrite these constants (e.g. for using a special directory for assets or versions at an object storage
at AWS S3), you have multiple ways to do so:

* Create a file in `/app/constants.php` setting the constants you need. Pimcore will skip setting any constants which are 
  already defined.
* Define an environment variable named after the constant. When defining a constant, Pimcore will look if an env variable
  with the same name is defined and use that instead of the default value.
* Define an environment variable in a `/.env` file which will be automatically loaded through the [DotEnv](https://symfony.com/doc/3.4/components/dotenv.html)
  component if it exists. Environment variables defined here will have the same effect as "real" environment variables.


The [Pimcore Skeleton](https://github.com/pimcore/skeleton) repository contains an example file,
[`constants.example.php`](https://github.com/pimcore/skeleton/blob/master/app/constants.example.php).
The following file is an example of how you can overwrite some paths:

```php
<?php

// to use this file you have to rename it to constants.php
// you can use this file to overwrite the constants defined in lib/Bootstrap.php

define("PIMCORE_ASSET_DIRECTORY", "/custom/path/to/assets");
define("PIMCORE_TEMPORARY_DIRECTORY", "/my/tmp/path");

```

Please see [`lib/Bootstrap.php`](https://github.com/pimcore/pimcore/blob/master/lib/Bootstrap.php)
for a list of defined constants.


### The PIMCORE_PROJECT_ROOT constant

There is one special constant `PIMCORE_PROJECT_ROOT` which is used to resolve the root directory (see [Directory Structure](./02_Directory_Structure.md))
of the application.
In contrast to the remaining constants, this constant is not defined in `constants.php` as it is already needed to resolve
the path to the `constants.php` file. It is defined in Pimcore's bootstrapping class `\Pimcore\Bootstrap::setProjectRoot()` instead. 


You can change the project root through an env variable (or by defining a constant before loading the entry
point) if needed and Pimcore will fall back to its standard value if not defined. If you use Pimcore's standard directory
layout as shipped in the zip file, you don't have to set anything, but if you need some kind of special setup you have full
control over the used paths here.

In contrast to the other constants, `PIMCORE_PROJECT_ROOT` can not be set via `.env` Pimcore doesn't know where to look
for a `.env` file at this point.


## Adding logic to the startup process

If you need to execute code to influence Pimcore's startup process, you can do so by adding a file in `/app/startup.php`
which will be automatically included as part of the bootstrap process. Specifically, it will be loaded after all other
bootstrapping (loading the autoloader, parsing constants, ...) is done, but **before** the kernel is loaded and booted.
This gives you the possibility to reconfigure environment settings before they are used and to configure the system for
your needs. Examples:

* Defining the [Trusted Proxies](http://symfony.com/doc/3.4/deployment/proxies.html) configuration on the `Request` object
* Influencing the default [environment handling](../21_Deployment/03_Multi_Environment.md)

```php
<?php

// /app/startup.php

use \Symfony\Component\HttpFoundation\Request;

Request::setTrustedProxies(['192.0.0.1', '10.0.0.0/8'], Request::HEADER_X_FORWARDED_ALL);
```
