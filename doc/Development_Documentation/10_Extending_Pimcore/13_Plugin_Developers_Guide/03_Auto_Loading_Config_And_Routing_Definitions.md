## Auto loading config and routing definitions

By default, Symfony does not load configuration and/or routing definitions from bundles but expects you to define everything
in `app/config` (optionally importing config files from bundles or other locations). Pimcore extends the config loading
by trying to automatically load `config.yml` and `routing.yml` (`php`, and `xml` types are also supported) from every active
bundle if they are defined in the directory `Resources/config/pimcore`. By making use of this auto-loading, you can provide
routing and config from within your bundle.

When loading the configuration, Pimcore will first try to load an environment specific config file (e.g. `config_dev.yml`)
from each bundle and falls back to `config.yml` if no environment specific file was found. If multiple files with different
extensions exist, it will load all of them.

For example, if a bundle defines a `config_dev.yml` AND a `config_dev.php` both of them will be loaded, but there will be
no attempt to load a `config.yml` without the environment. 

From the `Pimcore\Config\BundleConfigLocator`:

```php
/**
 * Locates configs from bundles if Resources/config/pimcore exists.
 *
 * Will first try to locate <name>_<environment>.<suffix> and fall back to <name>.<suffix> if the
 * environment specific lookup didn't find anything. All known suffixes are searched, so e.g. if a config.yml
 * and a config.php exist, both will be used.
 *
 * Example: lookup for config will try to locate the following files from every bundle (will return all files it finds):
 *
 *  - Resources/config/pimcore/config_dev.php
 *  - Resources/config/pimcore/config_dev.yml
 *  - Resources/config/pimcore/config_dev.xml
 *
 * If the previous lookup didn't return any results, it will fall back to:
 *
 *  - Resources/config/pimcore/config.php
 *  - Resources/config/pimcore/config.yml
 *  - Resources/config/pimcore/config.xml
 */
class BundleConfigLocator {}
```
