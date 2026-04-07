---
title: Autoloading Config and Routing
description: Automatic config and routing loading from bundle directories.
---

# Autoloading Config and Routing Definitions

Symfony does not load configuration or routing definitions from bundles by default,
expecting everything in `config/`. Pimcore extends this by automatically loading
`config.yaml` and `routing.yaml` (`php` and `xml` types are also supported) from
every active bundle when placed in the `config/pimcore` directory.

The `BundleConfigLocator` checks two paths for each bundle:

1. `config/pimcore` (recommended for new bundles)
2. `Resources/config/pimcore` (legacy path, still supported)

## Environment-Specific Loading

Pimcore first tries to load an environment-specific config file (e.g. `config_dev.yaml`)
from each bundle and falls back to `config.yaml` if no environment-specific file exists.
If multiple files with different extensions exist, all of them load.

For example, if a bundle defines both `config_dev.yaml` and `config_dev.php`, both load,
but there is no fallback attempt to load `config.yaml` without the environment suffix.

## Lookup Order

For each bundle and each config type (config, routing), the `BundleConfigLocator`
searches in this order:

1. **Environment-specific files** (all extensions checked):
   - `config/pimcore/config_dev.php`
   - `config/pimcore/config_dev.yaml`
   - `config/pimcore/config_dev.xml`

2. **Fallback** (only if no environment-specific file was found):
   - `config/pimcore/config.php`
   - `config/pimcore/config.yaml`
   - `config/pimcore/config.xml`

If multiple files with different extensions exist at the same level (e.g. both
`config_dev.yaml` and `config_dev.php`), all of them load. Be careful not to
define conflicting configuration across different file formats.
