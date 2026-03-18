---
title: Configuration
description: Configure Pimcore through Symfony config, constants, and startup hooks.
---

# Configuration

Pimcore uses the following configuration mechanisms:

* **Symfony configuration tree** - distributed throughout `*.yaml` files, covering all Symfony and most
  Pimcore-related settings.
* **Runtime-writable configs** in `var/config/*.(php|yaml)` - written from Pimcore Studio. For example,
  `system.yaml` stores the [System Settings](./02_System_Settings.md).
* **`PIMCORE_*` constants** - resolve filesystem paths at bootstrap time.


## Symfony Configuration

Configure many aspects of Pimcore through the
[Symfony Config](https://symfony.com/doc/current/bundles/configuration.html) tree defined under the `pimcore`
and other `pimcore_*` extensions. Place your overrides in config files under `config/`
(e.g. `config/config.yaml`).

Pimcore ships a set of standard configuration files located not in `config/` but in the
[PimcoreCoreBundle](https://github.com/pimcore/pimcore/tree/2026.x/bundles/CoreBundle/config/pimcore).
This allows shipping and updating default configurations without affecting project code. See
[Auto loading config and routing definitions](../../10_Extending_Pimcore/04_Pimcore_Bundle_Developers_Guide/03_Auto_Loading_Config_and_Routing.md)
for details on how this works.

Standard configs merge with your custom config in `config/` to build the final config tree. Debug the
resolved values with the following command:

```bash
# Core Symfony command - works for every bundle. Omit the
# "pimcore" argument to list all bundles.
bin/console debug:config pimcore
```

Print a reference of valid configuration sections:

```bash
bin/console config:dump-reference pimcore
```


## Pimcore Constants

Pimcore uses several constants for locating directories such as logging, assets, and versions. These
constants are defined in
[`lib/Bootstrap.php`](https://github.com/pimcore/pimcore/blob/2026.x/lib/Bootstrap.php).

To overwrite these constants (e.g. to point assets or versions at an AWS S3 object store), use one of the
following approaches:

* Create a file at `/config/pimcore/constants.php` that sets the constants you need. Pimcore skips any
  constant that is already defined.
* Define an environment variable named after the constant. When defining a constant, Pimcore checks for an
  env variable with the same name and uses it instead of the default value.
* Define an environment variable in a `/.env` file, which the
  [Symfony DotEnv](https://github.com/symfony/dotenv) component loads automatically. Environment variables
  defined here behave identically to real environment variables. See
  [Configuration Environments](./01_Configuration_Environments.md) for more details.

The [Pimcore Skeleton](https://github.com/pimcore/skeleton) repository contains an example file,
[`constants.example.php`](https://github.com/pimcore/skeleton/blob/2026.x/config/pimcore/constants.example.php).
The following snippet shows how to overwrite a path:

```php
<?php

// to use this file you have to rename it to constants.php
// you can use this file to overwrite the constants defined in lib/Bootstrap.php

define("PIMCORE_CLASS_DIRECTORY", "/my/tmp/path");

```

See [`lib/Bootstrap.php`](https://github.com/pimcore/pimcore/blob/2026.x/lib/Bootstrap.php) for a full list
of defined constants.


### The PIMCORE_PROJECT_ROOT Constant

The `PIMCORE_PROJECT_ROOT` constant resolves the application root directory (see
[Directory Structure](../../backlog/01_Getting_Started/04_Directory_Structure.md)). Unlike the other constants,
it is not defined in `constants.php` because Pimcore needs it to locate that file in the first place. Instead,
`\Pimcore\Bootstrap::setProjectRoot()` defines it during bootstrapping.

You can override the project root through an env variable (or by defining the constant before loading the
entry point). If unset, Pimcore falls back to its standard value. When using the standard directory layout
shipped with the skeleton, no changes are necessary. For non-standard layouts, you control all resolved
paths through this constant.

`PIMCORE_PROJECT_ROOT` cannot be set via `.env` because Pimcore does not yet know where to look for that
file at this point in the bootstrap sequence.


## Adding Logic to the Startup Process

To execute code that influences Pimcore's startup, add a file at `/config/pimcore/startup.php`. Pimcore
includes this file automatically as part of the bootstrap process - specifically after all other bootstrapping
(loading the autoloader, parsing constants, etc.) completes, but **before** the kernel loads and boots. This
lets you reconfigure environment settings before they take effect. Examples:

* Defining the [Trusted Proxies](https://symfony.com/doc/current/deployment/proxies.html) configuration on
  the `Request` object
* Influencing the default [environment handling](./01_Configuration_Environments.md)

```php
<?php

// /config/pimcore/startup.php

use \Symfony\Component\HttpFoundation\Request;

Request::setTrustedProxies(['192.0.0.1', '10.0.0.0/8'], Request::HEADER_X_FORWARDED_ALL);
```
