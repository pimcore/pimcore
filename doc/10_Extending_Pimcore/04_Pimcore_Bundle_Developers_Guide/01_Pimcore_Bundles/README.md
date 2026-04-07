---
title: Pimcore Bundles
description: PimcoreBundleInterface, composer setup, and version management.
---

# Pimcore Bundles

Pimcore bundles follow the same rules as standard Symfony bundles but implement
[`PimcoreBundleInterface`](https://github.com/pimcore/pimcore/blob/2026.x/lib/Extension/Bundle/PimcoreBundleInterface.php).
This interface adds:

- Visibility in the `pimcore:bundle:list` command with install/uninstall status
- Installation via `pimcore:bundle:install` and removal via `pimcore:bundle:uninstall`
  to trigger database setup, migrations, and other routines
- Methods to register JS and CSS files for Pimcore Studio and editmode

Extend `Pimcore\Extension\Bundle\AbstractPimcoreBundle` for a ready-made implementation
of all interface methods.

## Installer

By default, a Pimcore bundle does not define installation or update routines.
Override the `getInstaller()` method to return a
`Pimcore\Extension\Bundle\Installer\InstallerInterface` instance. When present,
`pimcore:bundle:install` uses this installer for installation and uninstallation.

The `install` method creates database tables and runs other initial tasks.
The `uninstall` method reverses those changes. The installer is also the right
place to check requirements such as minimum Pimcore version or filesystem permissions.

Read more in [Installers](./01_Installers.md).

### Composer Bundles

Bundles distributed via Composer are not automatically discovered. Set the package type
to `pimcore-bundle` to include your package in the scanned paths. Explicitly setting
the bundle class name through the `pimcore.bundles` composer extra config avoids
filesystem scanning and improves lookup performance.

:::note
Always set the bundle class name explicitly through the extra config when possible.
:::

An example of a `composer.json` defining a Pimcore bundle:

```json
{
    "name": "myVendor/myBundleName",
    "type": "pimcore-bundle",
    "autoload": {
        "psr-4": {
            "MyBundleName\\": ""
        }
    },
    "extra": {
        "pimcore": {
            "bundles": [
                "MyBundleName\\MyBundleName"
            ]
        }
    }
}
```

#### Returning the Composer Package Version

Include `Pimcore\Extension\Bundle\Traits\PackageVersionTrait` in your bundle to expose
version information from your `composer.json`. Override `getComposerPackageName()`
to return your package name (e.g. `company/foo-bundle`):

```php
<?php

declare(strict_types=1);

namespace Company\FooBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class FooBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    protected function getComposerPackageName(): string
    {
        // getVersion() will use this name to read the version from
        // PackageVersions and return a normalized value
        return 'company/foo-bundle';
    }
}
```
