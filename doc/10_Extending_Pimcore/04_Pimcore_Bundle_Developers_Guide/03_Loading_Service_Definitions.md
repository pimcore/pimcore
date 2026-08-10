---
title: Loading Service Definitions
description: Create DI extensions to load service definitions from bundles.
---

# Loading Service Definitions From Within a Bundle

To load services from your bundle instead of defining them in `config/services.yaml`,
create a dependency injection extension. See the Symfony
[Extensions Documentation](https://symfony.com/doc/current/bundles/extension.html)
for full details.

## Example

Create an extension for `MyBundle` that loads a `config/services.yaml` config file.

The extension class name follows a convention: it lives in the `DependencyInjection`
sub-namespace and replaces `Bundle` with `Extension` in the bundle class name.
For `MyBundle`, the extension is `MyBundleExtension`:

```php
<?php
// src/MyBundle/DependencyInjection/MyBundleExtension.php

namespace MyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MyBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // use XmlFileLoader instead for XML config files
        $loader = new YamlFileLoader(
            $container,
            // looks in src/MyBundle/Resources/config
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
    }
}
```

Next, create the config file at `src/MyBundle/Resources/config/services.yaml`:

```yaml
# src/MyBundle/Resources/config/services.yaml

services:
    my_custom_service:
        class: MyBundle\Custom\MyService
```

The extension automatically loads this file and registers the `my_custom_service`
service on the container.
